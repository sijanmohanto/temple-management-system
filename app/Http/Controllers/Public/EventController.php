<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\PaymentAllocation;
use App\Models\Pledge;
use App\Services\EventCollectionReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Display a paginated listing of published events with optional filtering.
     */
    public function index(Request $request): View
    {
        $query = Event::with('category')
            ->published()
            ->whereNotIn('status', [Event::STATUS_DRAFT]);

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('event_category_id', $request->integer('category_id'));
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->where('event_year', $request->integer('year'));
        }

        // Filter by lifecycle status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === Event::STATUS_ACTIVE) {
                $query->whereIn('status', [Event::STATUS_ACTIVE, Event::STATUS_CLOSING]);
            } elseif (in_array($status, [Event::STATUS_UPCOMING, Event::STATUS_CLOSED], true)) {
                $query->where('status', $status);
            }
        }

        // Sort: Active/closing first, then upcoming by start date asc, then closed by end date desc
        $events = $query->orderByRaw("
            CASE
                WHEN status IN ('active', 'closing') THEN 1
                WHEN status = 'upcoming' THEN 2
                WHEN status = 'closed' THEN 3
                ELSE 4
            END ASC
        ")
            ->orderBy('start_date', 'asc')
            ->paginate(12)
            ->withQueryString();

        $categories = EventCategory::active()->orderBy('name_en')->get();
        $availableYears = Event::published()
            ->whereNotIn('status', [Event::STATUS_DRAFT])
            ->whereNotNull('event_year')
            ->distinct()
            ->orderByDesc('event_year')
            ->pluck('event_year');

        return view('public.events.index', compact('events', 'categories', 'availableYears'));
    }

    /**
     * Display the details of a single published event by its URL slug.
     */
    public function show(Request $request, string $slug, EventCollectionReportService $reportService): View
    {
        $event = Event::with('category')
            ->published()
            ->whereNotIn('status', [Event::STATUS_DRAFT])
            ->where('slug', $slug)
            ->firstOrFail();

        $summary = $reportService->getPublicSummary($event);

        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status', 'all'),
        ];

        $donorReport = $reportService->getDonorCollectionReport($event, $filters);

        return view('public.events.show', compact('event', 'summary', 'donorReport', 'filters'));
    }

    /**
     * View private payment history and receipts for an authorized donor.
     * Unauthenticated guests receive a security privacy notice.
     * Cross-donor access by authenticated non-owner non-admin returns 403.
     */
    public function donorDetails(Request $request, string $slug, string $code): JsonResponse
    {
        $event = Event::published()
            ->whereNotIn('status', [Event::STATUS_DRAFT])
            ->where('slug', $slug)
            ->firstOrFail();

        // Find pledge or voluntary allocation
        $pledge = Pledge::with(['person', 'family', 'allocations.payment.receipt'])
            ->where('event_id', $event->id)
            ->where('pledge_code', $code)
            ->first();

        $voluntaryAlloc = null;
        if (! $pledge) {
            $voluntaryAlloc = PaymentAllocation::with(['payment.receipt', 'payment.payerPerson', 'payment.payerFamily'])
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_EVENT)
                ->where(function ($q) use ($code) {
                    $q->whereHas('payment', fn ($pq) => $pq->where('payment_code', $code))
                        ->orWhere('id', str_replace('VOL-', '', $code));
                })
                ->first();
        }

        if (! $pledge && ! $voluntaryAlloc) {
            return response()->json([
                'authorized' => false,
                'message' => __('portal.public.donor_not_found'),
            ], 404);
        }

        // Security: Unauthenticated guests receive a clear security notice
        if (! auth()->check()) {
            return response()->json([
                'authorized' => false,
                'message' => __('portal.public.unauthorized_donor_notice'),
                'security_notice' => __('portal.public.unauthorized_donor_notice'),
            ], 200);
        }

        $user = auth()->user();
        $isOwner = false;
        if ($pledge && $pledge->person) {
            if (($user->phone && $user->phone === $pledge->person->phone) ||
                ($user->email && $user->email === $pledge->person->email)) {
                $isOwner = true;
            }
        } elseif ($voluntaryAlloc && $voluntaryAlloc->payment?->payerPerson) {
            $p = $voluntaryAlloc->payment->payerPerson;
            if (($user->phone && $user->phone === $p->phone) ||
                ($user->email && $user->email === $p->email)) {
                $isOwner = true;
            }
        }

        $isAdmin = $user->can('view_collections') || $user->can('view_pledges') || $user->hasRole('admin') || $user->hasRole('Super Admin');

        if (! $isOwner && ! $isAdmin) {
            abort(403, __('portal.public.unauthorized_cross_donor'));
        }

        // Build verified payment history
        $allocationsData = [];
        if ($pledge) {
            $displayName = $pledge->public_display_name;
            $pledgedAmount = (float) $pledge->pledged_amount;
            $verifiedPaid = (float) $pledge->verified_paid_amount;
            $dueAmount = max(0.00, round($pledgedAmount - $verifiedPaid, 2));

            foreach ($pledge->allocations->where('is_active', true) as $alloc) {
                if ($alloc->payment && $alloc->payment->isVerified()) {
                    $allocationsData[] = [
                        'amount' => (float) $alloc->amount,
                        'target_type' => $alloc->target_type,
                        'payment_date' => $alloc->payment->payment_date?->format('d M Y') ?? '—',
                        'payment_method' => $alloc->payment->payment_method ?? '—',
                        'receipt_number' => $alloc->payment->receipt?->receipt_number ?? '—',
                    ];
                }
            }
        } else {
            $payment = $voluntaryAlloc->payment;
            $displayName = $voluntaryAlloc->payment?->payer_display_name ?? __('portal.public.voluntary_devotee');
            $pledgedAmount = 0.00;
            $verifiedPaid = (float) $voluntaryAlloc->amount;
            $dueAmount = 0.00;

            if ($payment && $payment->isVerified()) {
                $allocationsData[] = [
                    'amount' => (float) $voluntaryAlloc->amount,
                    'target_type' => $voluntaryAlloc->target_type,
                    'payment_date' => $payment->payment_date?->format('d M Y') ?? '—',
                    'payment_method' => $payment->payment_method ?? '—',
                    'receipt_number' => $payment->receipt?->receipt_number ?? '—',
                ];
            }
        }

        return response()->json([
            'authorized' => true,
            'code' => $code,
            'donor_name' => $displayName,
            'pledged_amount' => $pledgedAmount,
            'verified_paid' => $verifiedPaid,
            'due_amount' => $dueAmount,
            'allocations' => $allocationsData,
        ]);
    }

    /**
     * Submit a public complaint or discrepancy report against a collection.
     * Strictly NEVER modifies financial records or ledgers directly.
     */
    public function storeComplaint(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $event = Event::published()
            ->whereNotIn('status', [Event::STATUS_DRAFT])
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'complainant_name' => ['required', 'string', 'max:255'],
            'complainant_phone' => ['required', 'string', 'max:30'],
            'complainant_email' => ['nullable', 'email', 'max:255'],
            'issue_type' => ['required', 'string', 'in:payment_not_updated,wrong_paid_amount,wrong_due_amount,other'],
            'pledge_code' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf,webp', 'max:5120'],
        ]);

        $pledge = null;
        if (! empty($validated['pledge_code'])) {
            $pledge = Pledge::where('event_id', $event->id)
                ->where('pledge_code', $validated['pledge_code'])
                ->first();
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('complaints', 'public');
        }

        $trackingNumber = Complaint::generateTrackingNumber();

        $issueLabels = [
            'payment_not_updated' => __('portal.public.complaint_type_not_updated'),
            'wrong_paid_amount' => __('portal.public.complaint_type_wrong_paid'),
            'wrong_due_amount' => __('portal.public.complaint_type_wrong_due'),
            'other' => __('portal.public.complaint_type_other'),
        ];
        $issueLabel = $issueLabels[$validated['issue_type']] ?? $validated['issue_type'];
        $subject = "[{$issueLabel}] - {$event->name}".(! empty($validated['pledge_code']) ? " ({$validated['pledge_code']})" : '');

        Complaint::create([
            'tracking_number' => $trackingNumber,
            'complainant_name' => $validated['complainant_name'],
            'complainant_phone' => $validated['complainant_phone'],
            'complainant_email' => $validated['complainant_email'] ?? null,
            'event_id' => $event->id,
            'person_id' => $pledge?->person_id ?? null,
            'category' => Complaint::CATEGORY_PAYMENT_ISSUE,
            'subject' => $subject,
            'description' => $validated['description'],
            'attachment_path' => $attachmentPath,
            'status' => Complaint::STATUS_SUBMITTED,
            'priority' => Complaint::PRIORITY_MEDIUM,
        ]);

        $message = __('portal.public.complaint_submitted_success', ['tracking' => $trackingNumber]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'tracking_number' => $trackingNumber,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
