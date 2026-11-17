<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreDonorComplaintRequest;
use App\Models\CollectionPayment;
use App\Models\Complaint;
use App\Models\ComplaintActivity;
use App\Models\Event;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicComplaintController extends Controller
{
    /**
     * Display a listing of the authenticated donor's complaints.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $donor = auth()->user()->person;
        if (! $donor) {
            return redirect()->route('home')->with('error', __('portal.public.account_not_linked_error'));
        }

        $query = Complaint::with(['payment', 'event'])
            ->where('person_id', $donor->id);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $complaints = $query->orderByDesc('id')->paginate(10)->withQueryString();

        return view('public.complaints.index', compact('complaints', 'donor'));
    }

    /**
     * Show the complaint submission form for authenticated donor.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $donor = auth()->user()->person;
        if (! $donor) {
            return redirect()->route('home')->with('error', __('portal.public.account_not_linked_error'));
        }

        $donorPayments = CollectionPayment::where('payer_person_id', $donor->id)
            ->where('is_public_submission', true)
            ->orderByDesc('id')
            ->get(['id', 'payment_code', 'amount', 'event_id', 'payment_date', 'status', 'payment_method', 'transaction_reference']);

        $events = Event::published()
            ->whereNotIn('status', [Event::STATUS_DRAFT])
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_bn', 'slug', 'event_year']);

        $preselectedPaymentCode = $request->input('payment_code');
        $preselectedPayment = $preselectedPaymentCode
            ? $donorPayments->firstWhere('payment_code', $preselectedPaymentCode)
            : null;

        return view('public.complaints.create', compact('donor', 'donorPayments', 'events', 'preselectedPayment'));
    }

    /**
     * Store a newly submitted donor complaint.
     */
    public function store(StoreDonorComplaintRequest $request): RedirectResponse|JsonResponse
    {
        $donor = auth()->user()->person;
        if (! $donor) {
            return back()->with('error', __('portal.public.account_not_linked_error'));
        }

        $validated = $request->validated();

        $payment = null;
        if (! empty($validated['payment_code'])) {
            $payment = CollectionPayment::where('payment_code', trim($validated['payment_code']))
                ->where('payer_person_id', $donor->id)
                ->first();
        }

        $event = null;
        if ($payment && $payment->event) {
            $event = $payment->event;
        } elseif (! empty($validated['event_id'])) {
            $event = Event::published()
                ->whereNotIn('status', [Event::STATUS_DRAFT])
                ->find($validated['event_id']);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('complaints', 'local');
        }

        $categoryMap = [
            Complaint::CATEGORY_PAYMENT_NOT_UPDATED => __('portal.complaint_categories.payment_not_updated'),
            Complaint::CATEGORY_WRONG_PAID_AMOUNT => __('portal.complaint_categories.wrong_paid_amount'),
            Complaint::CATEGORY_WRONG_DUE_AMOUNT => __('portal.complaint_categories.wrong_due_amount'),
            Complaint::CATEGORY_OTHER => __('portal.complaint_categories.other'),
        ];
        $categoryLabel = $categoryMap[$validated['complaint_type']] ?? ucwords(str_replace('_', ' ', $validated['complaint_type']));
        $subject = "[{$categoryLabel}]".($event ? " — {$event->name}" : '').($payment ? " ({$payment->payment_code})" : '');

        $maxAttempts = 5;
        $complaint = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $trackingNumber = Complaint::generateDonorTrackingNumber();

            try {
                $complaint = DB::transaction(function () use ($donor, $payment, $event, $validated, $trackingNumber, $attachmentPath, $subject) {
                    $complaintRecord = Complaint::create([
                        'tracking_number' => $trackingNumber,
                        'complainant_name' => $donor->name_en,
                        'complainant_phone' => $donor->phone,
                        'complainant_email' => $donor->email,
                        'person_id' => $donor->id,
                        'event_id' => $event?->id,
                        'collection_payment_id' => $payment?->id,
                        'category' => $validated['complaint_type'],
                        'subject' => $subject,
                        'description' => trim($validated['description']),
                        'attachment_path' => $attachmentPath,
                        'status' => Complaint::STATUS_SUBMITTED,
                        'priority' => Complaint::PRIORITY_MEDIUM,
                    ]);

                    ComplaintActivity::create([
                        'complaint_id' => $complaintRecord->id,
                        'actor_id' => auth()->id(),
                        'activity_type' => ComplaintActivity::TYPE_SUBMISSION,
                        'previous_status' => null,
                        'new_status' => Complaint::STATUS_SUBMITTED,
                        'content' => trim($validated['description']),
                        'visibility' => ComplaintActivity::VISIBILITY_DONOR,
                    ]);

                    AuditLogger::log(
                        'donor_complaint_submitted',
                        "Donor {$donor->name_en} ({$donor->person_code}) submitted complaint {$complaintRecord->tracking_number}",
                        $complaintRecord
                    );

                    return $complaintRecord;
                });

                break;
            } catch (QueryException $e) {
                if ($attempt === $maxAttempts || (! str_contains($e->getMessage(), 'Duplicate entry') && ! str_contains($e->getMessage(), 'UNIQUE'))) {
                    throw $e;
                }
            }
        }

        $message = __('portal.public.complaint_submitted_success', ['tracking' => $complaint->tracking_number]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'tracking_number' => $complaint->tracking_number,
                'message' => $message,
            ]);
        }

        return redirect()->route('public.complaints.show', $complaint->tracking_number)
            ->with('success', $message);
    }

    /**
     * Display complaint details and donor-visible activity history.
     */
    public function show(Request $request, string $reference): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $donor = auth()->user()->person;
        if (! $donor) {
            abort(403, __('portal.public.account_not_linked_error'));
        }

        $complaint = Complaint::where('tracking_number', $reference)
            ->with([
                'payment.receiverAccount',
                'event',
                'donorVisibleActivities.actor',
            ])
            ->firstOrFail();

        // Enforce IDOR protection: only owner donor or authorized admin can view
        $isOwner = ((int) $complaint->person_id === (int) $donor->id);
        if (! $isOwner && ! auth()->user()->can('view_complaints')) {
            abort(403, __('portal.public.unauthorized_cross_donor'));
        }

        // STRICT SECURITY REQUIREMENT: Only donorVisibleActivities are loaded and sent to view.
        // Internal notes are never exposed.
        $activities = $complaint->donorVisibleActivities;

        return view('public.complaints.show', compact('complaint', 'donor', 'activities'));
    }

    /**
     * Securely download or stream complaint attachment for the owning donor or authorized staff.
     */
    public function downloadAttachment(string $reference): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $donor = $user->person;

        $complaint = Complaint::where('tracking_number', $reference)->firstOrFail();

        $isOwner = ($donor && (int) $complaint->person_id === (int) $donor->id);
        $hasPermission = $user->can('view_complaints') || $user->can('view_public_complaints');

        if (! $isOwner && ! $hasPermission) {
            abort(403, __('portal.public.unauthorized_cross_donor'));
        }

        if (! $complaint->attachment_path) {
            abort(404, __('portal.public.no_attachment_file'));
        }

        $path = str_replace('\\', '/', $complaint->attachment_path);
        if (str_contains($path, '..')) {
            abort(400, 'Invalid file path.');
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        abort(404, __('portal.public.no_attachment_file'));
    }
}
