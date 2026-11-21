<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorePublicPaymentRequest;
use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\PaymentAllocation;
use App\Models\Pledge;
use App\Models\Receipt;
use App\Models\ReceiverAccount;
use App\Services\AuditLogger;
use App\Services\ReceiptService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PaymentSubmissionController extends Controller
{
    /**
     * Show the public payment submission form for a published event.
     */
    public function create(Request $request, string $slug): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('warning', __('portal.public.login_required_to_pay'));
        }

        $donor = auth()->user()->person;
        if (! $donor) {
            return redirect()->route('public.events.show', $slug)->with('error', __('portal.public.account_not_linked_error'));
        }

        $event = Event::published()
            ->where('slug', $slug)
            ->firstOrFail();

        if (! $event->isRunningForPayment()) {
            return redirect()->route('public.events.show', $slug)->with('error', __('portal.public.event_not_open_for_payment'));
        }

        // Validate event collection date boundaries
        $today = now(config('app.timezone'))->startOfDay();
        if ($event->collection_start_date && $today->lt($event->collection_start_date->copy()->startOfDay())) {
            return redirect()->route('public.events.show', $slug)->with('error', __('portal.public.collection_not_started', [
                'date' => $event->collection_start_date->format('d M Y'),
            ]));
        }
        if ($event->collection_end_date && $today->gt($event->collection_end_date->copy()->endOfDay())) {
            return redirect()->route('public.events.show', $slug)->with('error', __('portal.public.collection_already_ended', [
                'date' => $event->collection_end_date->format('d M Y'),
            ]));
        }

        // Active receiver accounts for this event or general
        $receiverAccounts = ReceiverAccount::publicReady()
            ->where(function ($q) use ($event) {
                $q->whereNull('event_id')->orWhere('event_id', $event->id);
            })
            ->orderBy('display_order')
            ->get();

        if ($receiverAccounts->isEmpty()) {
            $receiverAccounts = ReceiverAccount::publicReady()->orderBy('display_order')->get();
        }

        // Pledges by this donor for this event
        $pledges = Pledge::where('person_id', $donor->id)
            ->where('event_id', $event->id)
            ->where('status', '!=', Pledge::STATUS_CANCELLED)
            ->get();

        $selectedPledgeId = $request->integer('pledge_id');

        return view('public.payments.create', compact('event', 'donor', 'pledges', 'receiverAccounts', 'selectedPledgeId'));
    }

    /**
     * Store a submitted public payment record.
     */
    public function store(StorePublicPaymentRequest $request, string $slug): RedirectResponse
    {
        $event = Event::published()
            ->whereNotIn('status', [Event::STATUS_DRAFT, Event::STATUS_CLOSED])
            ->where('slug', $slug)
            ->firstOrFail();

        if (! $event->isRunningForPayment()) {
            abort(404, __('portal.public.event_not_open_for_payment'));
        }

        // Validate event collection date boundaries
        $today = now(config('app.timezone'))->startOfDay();
        if ($event->collection_start_date && $today->lt($event->collection_start_date->copy()->startOfDay())) {
            return back()->with('error', __('portal.public.collection_not_started', [
                'date' => $event->collection_start_date->format('d M Y'),
            ]));
        }
        if ($event->collection_end_date && $today->gt($event->collection_end_date->copy()->endOfDay())) {
            return back()->with('error', __('portal.public.collection_already_ended', [
                'date' => $event->collection_end_date->format('d M Y'),
            ]));
        }

        $donor = auth()->user()->person;
        if (! $donor) {
            return back()->with('error', __('portal.public.account_not_linked_error'));
        }

        $validated = $request->validated();
        $receiverAccount = ReceiverAccount::findOrFail($validated['receiver_account_id']);

        if ($receiverAccount->event_id !== null && (int) $receiverAccount->event_id !== (int) $event->id) {
            return back()->with('error', __('portal.public.receiver_account_event_mismatch'));
        }

        // Handle payment proof upload to private 'local' disk
        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'local');
        }

        // Resolve pledge
        $pledge = null;
        if (! empty($validated['pledge_id'])) {
            $pledge = Pledge::where('id', $validated['pledge_id'])
                ->where('person_id', $donor->id)
                ->where('event_id', $event->id)
                ->where('status', '!=', Pledge::STATUS_CANCELLED)
                ->first();
        }

        $paymentDate = Carbon::parse($validated['payment_date']);
        $year = (int) $paymentDate->year;

        $payment = DB::transaction(function () use ($donor, $event, $receiverAccount, $pledge, $validated, $paymentDate, $year, $proofPath) {
            // Concurrency-safe duplicate transaction reference check with row locking
            if (! empty($validated['transaction_reference'])) {
                $method = $validated['payment_method'];
                $trx = trim((string) $validated['transaction_reference']);

                $duplicate = CollectionPayment::where('payment_method', $method)
                    ->where('transaction_reference', $trx)
                    ->whereIn('status', [CollectionPayment::STATUS_VERIFIED, CollectionPayment::STATUS_PENDING])
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'transaction_reference' => __('portal.public.duplicate_transaction_error', [
                            'method' => strtoupper($method),
                        ]),
                    ]);
                }
            }

            $code = CollectionPayment::generatePaymentCode($year);
            $collectionType = $pledge ? CollectionPayment::TYPE_EVENT_PLEDGE : CollectionPayment::TYPE_EVENT_DONATION;

            $notes = $validated['payment_notes'] ?? null;
            if (! empty($validated['payment_time'])) {
                $timeNote = 'Payment Time: '.$validated['payment_time'];
                $notes = $notes ? $notes.' | '.$timeNote : $timeNote;
            }

            $payment = CollectionPayment::create([
                'payment_code' => $code,
                'payer_person_id' => $donor->id,
                'public_donor_name' => $donor->name_en,
                'public_donor_phone' => $donor->phone,
                'public_donor_email' => $donor->email,
                'collection_type' => $collectionType,
                'event_id' => $event->id,
                'pledge_id' => $pledge?->id,
                'receiver_account_id' => $receiverAccount->id,
                'payment_date' => $paymentDate->toDateString(),
                'payment_year' => $year,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'sender_mobile' => $validated['sender_mobile'] ?? null,
                'payment_notes' => $notes,
                'payment_proof_path' => $proofPath,
                'status' => CollectionPayment::STATUS_PENDING,
                'is_public_submission' => true,
                'submitted_by' => auth()->id(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            PaymentAllocation::create([
                'collection_payment_id' => $payment->id,
                'target_type' => $pledge ? PaymentAllocation::TARGET_PLEDGE : PaymentAllocation::TARGET_VOLUNTARY_EVENT,
                'pledge_id' => $pledge?->id,
                'event_id' => $event->id,
                'amount' => $payment->amount,
                'notes' => $notes,
                'is_active' => true,
            ]);

            AuditLogger::log(
                'public_payment_submitted',
                "Public payment {$payment->payment_code} of ৳{$payment->amount} submitted by donor {$donor->name_en} ({$donor->person_code}) for event {$event->name_en}",
                $payment,
                $payment->toArray()
            );

            return $payment;
        });

        return redirect()->route('public.payments.history')->with('success', __('portal.public.submission_success', ['code' => $payment->payment_code]));
    }

    /**
     * Display the authenticated donor's payment submission history.
     */
    public function history(Request $request): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $donor = auth()->user()->person;
        if (! $donor) {
            return redirect()->route('home')->with('error', __('portal.public.account_not_linked_error'));
        }

        $query = CollectionPayment::with([
            'event',
            'receiverAccount',
            'receipt',
            'pledge',
            'allocations.pledge',
            'allocations.event',
            'reverser',
            'rejecter',
        ])
            ->where('payer_person_id', $donor->id)
            ->where('is_public_submission', true);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->integer('event_id'));
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $payments = $query->orderByDesc('id')->paginate(10)->withQueryString();
        $events = Event::published()
            ->whereNotIn('status', [Event::STATUS_DRAFT])
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_bn', 'slug', 'event_year']);

        return view('public.payments.history', compact('payments', 'events', 'donor'));
    }

    /**
     * Display detailed payment view for an authorized donor.
     */
    public function show(Request $request, string $paymentCode): View|JsonResponse|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $donor = auth()->user()->person;
        if (! $donor) {
            abort(403, __('portal.public.account_not_linked_error'));
        }

        $payment = CollectionPayment::with([
            'event',
            'receiverAccount',
            'receipt',
            'pledge',
            'allocations.pledge',
            'allocations.event',
            'reverser',
            'rejecter',
        ])
            ->where('payment_code', $paymentCode)
            ->firstOrFail();

        $user = auth()->user();
        $isOwner = ((int) $payment->payer_person_id === (int) $donor->id)
            || ((int) $payment->submitted_by === (int) $user->id);

        if (! $isOwner && ! $user->can('view_public_payments')) {
            abort(403, __('portal.public.unauthorized_cross_donor'));
        }

        $pledge = $payment->pledge;
        if (! $pledge && $payment->payer_person_id && $payment->event_id) {
            $pledge = Pledge::where('person_id', $payment->payer_person_id)
                ->where('event_id', $payment->event_id)
                ->where('status', '!=', Pledge::STATUS_CANCELLED)
                ->first();
        }

        $allocations = $payment->allocations;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'payment' => [
                    'payment_code' => $payment->payment_code,
                    'status' => $payment->status,
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'transaction_reference' => $payment->transaction_reference,
                    'payment_date' => $payment->payment_date?->format('d M Y'),
                    'sender_mobile' => $payment->sender_mobile,
                    'event_name' => $payment->event?->name,
                    'receiver_account' => $payment->receiverAccount?->account_name,
                    'rejection_reason' => $payment->rejection_reason,
                    'reversal_reason' => $payment->reversal_reason,
                    'reversed_at' => $payment->reversed_at?->format('d M Y, h:i A'),
                    'receipt_number' => $payment->isVerified() && $payment->receipt && ! $payment->receipt->isVoided() ? $payment->receipt->receipt_number : null,
                    'pledge_code' => $pledge?->pledge_code,
                    'pledged_amount' => $pledge ? (float) $pledge->pledged_amount : null,
                    'verified_paid_amount' => $pledge ? (float) $pledge->verified_paid_amount : null,
                    'outstanding_amount' => $pledge ? (float) $pledge->outstanding_amount : null,
                ],
            ]);
        }

        return view('public.payments.show', compact('payment', 'donor', 'pledge', 'allocations'));
    }

    /**
     * View/Print official receipt for an authorized donor's verified payment.
     */
    public function receipt(string $receiptNumber, ReceiptService $receiptService): View
    {
        if (! auth()->check()) {
            abort(401);
        }

        $donor = auth()->user()->person;
        if (! $donor) {
            abort(403, __('portal.public.account_not_linked_error'));
        }

        $receipt = Receipt::with([
            'payment.payerPerson',
            'payment.payerFamily',
            'payment.event',
            'payment.allocations',
            'issuer',
            'voider',
        ])
            ->where('receipt_number', $receiptNumber)
            ->firstOrFail();

        // Enforce IDOR protection: only owner donor or authorized admin
        if ((int) $receipt->payment?->payer_person_id !== (int) $donor->id && ! auth()->user()->can('view_receipts')) {
            abort(403, __('portal.public.unauthorized_cross_donor'));
        }

        // Active receipt is only available if payment is verified and not voided
        if (! $receipt->payment || ! $receipt->payment->isVerified() || $receipt->isVoided()) {
            abort(403, __('portal.public.receipt_not_available_for_unverified_payment'));
        }

        $amountInWords = $receiptService->amountToWords((float) $receipt->payment->amount, app()->getLocale());

        return view('donations.receipts.print', [
            'receipt' => $receipt,
            'amountInWords' => $amountInWords,
            'isDownloadMode' => false,
        ]);
    }

    /**
     * Download official receipt for an authorized donor's verified payment.
     */
    public function downloadReceipt(string $receiptNumber, ReceiptService $receiptService): View
    {
        if (! auth()->check()) {
            abort(401);
        }

        $donor = auth()->user()->person;
        if (! $donor) {
            abort(403, __('portal.public.account_not_linked_error'));
        }

        $receipt = Receipt::with([
            'payment.payerPerson',
            'payment.payerFamily',
            'payment.event',
            'payment.allocations',
            'issuer',
            'voider',
        ])
            ->where('receipt_number', $receiptNumber)
            ->firstOrFail();

        // Enforce IDOR protection: only owner donor or authorized admin
        if ((int) $receipt->payment?->payer_person_id !== (int) $donor->id && ! auth()->user()->can('view_receipts')) {
            abort(403, __('portal.public.unauthorized_cross_donor'));
        }

        // Active receipt is only available if payment is verified and not voided
        if (! $receipt->payment || ! $receipt->payment->isVerified() || $receipt->isVoided()) {
            abort(403, __('portal.public.receipt_not_available_for_unverified_payment'));
        }

        $amountInWords = $receiptService->amountToWords((float) $receipt->payment->amount, app()->getLocale());

        return view('donations.receipts.print', [
            'receipt' => $receipt,
            'amountInWords' => $amountInWords,
            'isDownloadMode' => true,
        ]);
    }

    /**
     * AJAX endpoint: return active receiver accounts by payment method.
     */
    public function getAccountsByMethod(Request $request): JsonResponse
    {
        $method = $request->input('method');
        $eventId = $request->integer('event_id');

        $query = ReceiverAccount::publicReady();

        if ($method) {
            $query->where('payment_method', $method);
        }

        if ($eventId) {
            $query->where(function ($q) use ($eventId) {
                $q->whereNull('event_id')->orWhere('event_id', $eventId);
            });
        }

        $accounts = $query->orderBy('display_order')
            ->get()
            ->map(function (ReceiverAccount $account) {
                return [
                    'id' => $account->id,
                    'payment_method' => $account->payment_method,
                    'method_name' => $account->method_name,
                    'account_name' => $account->account_name,
                    'account_number' => $account->account_number,
                    'account_type' => $account->account_type,
                    'type_name' => $account->type_name,
                    'bank_name' => $account->bank_name,
                    'branch_name' => $account->branch_name,
                    'routing_number' => $account->routing_number,
                    'swift_code' => $account->swift_code,
                    'instructions' => $account->instructions,
                    'qr_code_url' => $account->qr_code_url,
                ];
            });

        return response()->json($accounts);
    }

    /**
     * Download or stream private payment proof for an authenticated, authorized donor or staff.
     */
    public function downloadProof(string $paymentCode): Response
    {
        if (! auth()->check()) {
            abort(401);
        }

        $payment = CollectionPayment::where('payment_code', $paymentCode)->firstOrFail();

        $user = auth()->user();
        $donor = $user->person;

        $isOwner = ($donor && (int) $payment->payer_person_id === (int) $donor->id)
            || ((int) $payment->submitted_by === (int) $user->id);

        if (! $isOwner && ! $user->can('view_public_payments')) {
            abort(403, __('portal.public.unauthorized_cross_donor'));
        }

        if (! $payment->payment_proof_path) {
            abort(404, __('portal.public.no_proof_file'));
        }

        $path = str_replace('\\', '/', $payment->payment_proof_path);
        if (str_contains($path, '..')) {
            abort(400, 'Invalid file path.');
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        abort(404, __('portal.public.no_proof_file'));
    }
}
