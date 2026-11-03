<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\ReceiverAccount;
use App\Services\AuditLogger;
use App\Services\CollectionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicPaymentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_public_payments', only: ['index', 'show', 'searchPersons']),
            new Middleware('permission:verify_public_payments', only: ['verify', 'linkDevotee']),
            new Middleware('permission:reject_public_payments|verify_public_payments', only: ['reject']),
        ];
    }

    /**
     * Display a listing of public payment submissions.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax() || $request->wantsJson()) {
            $query = CollectionPayment::with(['payerPerson', 'event', 'receiverAccount', 'receipt'])
                ->where('is_public_submission', true);

            // Filter: status
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter: event
            if ($request->filled('event_id')) {
                $query->where('event_id', $request->integer('event_id'));
            }

            // Filter: receiver account
            if ($request->filled('receiver_account_id')) {
                $query->where('receiver_account_id', $request->integer('receiver_account_id'));
            }

            // Filter: payment method
            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Search
            if ($searchValue = $request->input('search.value')) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('payment_code', 'like', "%{$searchValue}%")
                        ->orWhere('transaction_reference', 'like', "%{$searchValue}%")
                        ->orWhere('public_donor_name', 'like', "%{$searchValue}%")
                        ->orWhere('public_donor_phone', 'like', "%{$searchValue}%")
                        ->orWhere('sender_mobile', 'like', "%{$searchValue}%")
                        ->orWhere('payer_name_override', 'like', "%{$searchValue}%")
                        ->orWhereHas('payerPerson', function ($pq) use ($searchValue) {
                            $pq->where('name_en', 'like', "%{$searchValue}%")
                                ->orWhere('name_bn', 'like', "%{$searchValue}%")
                                ->orWhere('phone', 'like', "%{$searchValue}%");
                        });
                });
            }

            $totalRecords = CollectionPayment::where('is_public_submission', true)->count();
            $filteredRecords = $query->count();

            // Ordering
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'desc');

            $columnMap = [
                0 => 'public_donor_name',
                1 => 'event_id',
                2 => 'amount',
                3 => 'transaction_reference',
                5 => 'status',
                6 => 'payment_date',
            ];

            if ($orderColumnIndex !== null && isset($columnMap[$orderColumnIndex])) {
                $query->orderBy($columnMap[$orderColumnIndex], $orderDirection);
            } else {
                $query->orderByDesc('id');
            }

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 15);
            $payments = $query->skip($start)->take($length)->get();

            $data = $payments->map(function (CollectionPayment $payment) {
                return [
                    'donor_info' => $this->formatDonorInfoColumn($payment),
                    'event_details' => $this->formatEventColumn($payment),
                    'amount_details' => $this->formatAmountColumn($payment),
                    'transaction_details' => $this->formatTransactionColumn($payment),
                    'proof' => $this->formatProofColumn($payment),
                    'status' => $this->formatStatusBadge($payment->status),
                    'submitted_at' => $payment->created_at ? $payment->created_at->format('d M Y, h:i A') : $payment->payment_date->format('d M Y'),
                    'actions' => $this->formatActionsColumn($payment),
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        $events = Event::orderBy('name_en')->get(['id', 'name_en', 'name_bn', 'event_code', 'event_year']);
        $receiverAccounts = ReceiverAccount::orderBy('display_order')->get();

        return view('portal.payments.index', compact('events', 'receiverAccounts'));
    }

    /**
     * Show details of a public payment submission.
     */
    public function show(CollectionPayment $payment): View
    {
        $payment->load([
            'payerPerson',
            'payerFamily',
            'event.category',
            'receiverAccount.financialAccount',
            'receipt',
            'submitter',
            'verifier',
            'rejecter',
            'pledge',
            'allocations.pledge',
            'allocations.event',
            'journalEntry.lines.account',
        ]);

        $pledge = $payment->linkedPledge;
        if (! $pledge && $payment->payer_person_id && $payment->event_id) {
            $pledge = Pledge::where('person_id', $payment->payer_person_id)
                ->where('event_id', $payment->event_id)
                ->where('status', '!=', Pledge::STATUS_CANCELLED)
                ->first();
        }

        // Look for candidate person matches by phone if not linked
        $suggestedPersons = collect();
        $donorPhone = $payment->public_donor_phone ?: ($payment->payer_phone_override ?: $payment->sender_mobile);
        if (! $payment->payer_person_id && $donorPhone) {
            $suggestedPersons = Person::where('phone', 'like', "%{$donorPhone}%")
                ->limit(5)
                ->get();
        }

        return view('portal.payments.show', compact('payment', 'suggestedPersons', 'pledge'));
    }

    /**
     * Verify and post public payment.
     */
    public function verify(Request $request, CollectionPayment $payment, CollectionPaymentService $paymentService): JsonResponse|RedirectResponse
    {
        $request->validate([
            'verification_note' => ['nullable', 'string', 'max:1000'],
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'pledge_id' => ['nullable', 'integer', 'exists:pledges,id'],
        ]);

        if ($payment->status !== CollectionPayment::STATUS_PENDING) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('portal.payments.already_processed')], 422);
            }

            return back()->with('error', __('portal.payments.already_processed'));
        }

        // Link person if provided
        if ($request->filled('person_id')) {
            $payment->update([
                'payer_person_id' => $request->integer('person_id'),
                'updated_by' => auth()->id(),
            ]);
        }

        // Public payment must be against a registered devotee profile
        if (! $payment->payer_person_id) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('portal.payments.payment_against_pledge_only')], 422);
            }

            return back()->with('error', __('portal.payments.payment_against_pledge_only'));
        }

        // Method-aware duplicate transaction reference prevention
        if ($payment->transaction_reference && in_array($payment->payment_method, [
            CollectionPayment::METHOD_BKASH,
            CollectionPayment::METHOD_NAGAD,
            CollectionPayment::METHOD_ROCKET,
            CollectionPayment::METHOD_BANK_TRANSFER,
        ])) {
            $existingTrx = CollectionPayment::where('id', '!=', $payment->id)
                ->where('payment_method', $payment->payment_method)
                ->where('transaction_reference', $payment->transaction_reference)
                ->whereIn('status', [CollectionPayment::STATUS_VERIFIED, CollectionPayment::STATUS_PENDING])
                ->exists();

            if ($existingTrx) {
                $errorMsg = __('portal.payments.duplicate_transaction_reference', [
                    'method' => strtoupper($payment->payment_method),
                    'trx' => $payment->transaction_reference,
                ]);

                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }

                return back()->with('error', $errorMsg);
            }
        }

        // Resolve associated pledge
        $pledgeId = $request->integer('pledge_id') ?: $payment->pledge_id;
        $pledge = null;
        if ($pledgeId) {
            $pledge = Pledge::find($pledgeId);
        }
        if (! $pledge && $payment->payer_person_id && $payment->event_id) {
            $pledge = Pledge::where('person_id', $payment->payer_person_id)
                ->where('event_id', $payment->event_id)
                ->where('status', '!=', Pledge::STATUS_CANCELLED)
                ->first();
        }

        if (! $pledge) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('portal.payments.no_active_pledge_found')], 422);
            }

            return back()->with('error', __('portal.payments.no_active_pledge_found'));
        }

        if ($payment->pledge_id !== $pledge->id) {
            $payment->update(['pledge_id' => $pledge->id]);
        }

        // Ensure active pledge allocation exists before verification
        if ($payment->allocations()->count() === 0) {
            PaymentAllocation::create([
                'collection_payment_id' => $payment->id,
                'target_type' => PaymentAllocation::TARGET_PLEDGE,
                'pledge_id' => $pledge->id,
                'event_id' => $payment->event_id,
                'amount' => $payment->amount,
                'is_active' => true,
            ]);
        }

        try {
            $verifiedPayment = $paymentService->verifyPayment(
                $payment,
                auth()->id(),
                $request->input('verification_note')
            );

            AuditLogger::log(
                'public_payment_verified',
                "Verified public payment {$payment->payment_code} against pledge {$pledge->pledge_code} (TrxID: {$payment->transaction_reference}) for ৳".number_format($payment->amount, 2),
                $payment
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('portal.payments.verified_successfully'),
                    'receipt_number' => $verifiedPayment->receipt?->receipt_number,
                ]);
            }

            return redirect()
                ->route('portal.payments.show', $payment)
                ->with('success', __('portal.payments.verified_successfully'));
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first(),
                ], 422);
            }

            return back()->withErrors($e->errors());
        }
    }

    /**
     * Reject public payment with reason.
     */
    public function reject(Request $request, CollectionPayment $payment, CollectionPaymentService $paymentService): JsonResponse|RedirectResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        if ($payment->status !== CollectionPayment::STATUS_PENDING) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('portal.payments.already_processed')], 422);
            }

            return back()->with('error', __('portal.payments.already_processed'));
        }

        try {
            $paymentService->rejectPayment(
                $payment,
                auth()->id(),
                $request->input('rejection_reason')
            );

            AuditLogger::log(
                'public_payment_rejected',
                "Rejected public payment {$payment->payment_code} (Reason: {$request->input('rejection_reason')})",
                $payment
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('portal.payments.rejected_successfully'),
                ]);
            }

            return redirect()
                ->route('portal.payments.show', $payment)
                ->with('success', __('portal.payments.rejected_successfully'));
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first(),
                ], 422);
            }

            return back()->withErrors($e->errors());
        }
    }

    /**
     * Link payment to an existing person in directory.
     */
    public function linkDevotee(Request $request, CollectionPayment $payment): JsonResponse|RedirectResponse
    {
        $request->validate([
            'person_id' => ['required', 'integer', 'exists:people,id'],
        ]);

        $person = Person::findOrFail($request->integer('person_id'));

        $payment->update([
            'payer_person_id' => $person->id,
            'updated_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'public_payment_linked_person',
            "Linked payment {$payment->payment_code} to person {$person->name_en} ({$person->person_code})",
            $payment
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.payments.linked_successfully'),
                'person_name' => $person->name_en,
            ]);
        }

        return back()->with('success', __('portal.payments.linked_successfully'));
    }

    /**
     * Search persons for Select2 autocomplete matching.
     */
    public function searchPersons(Request $request): JsonResponse
    {
        $term = $request->input('q');

        $persons = Person::where(function ($q) use ($term) {
            $q->where('name_en', 'like', "%{$term}%")
                ->orWhere('name_bn', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('person_code', 'like', "%{$term}%");
        })
            ->where('status', 'active')
            ->limit(20)
            ->get(['id', 'person_code', 'name_en', 'name_bn', 'phone']);

        $results = $persons->map(function ($p) {
            return [
                'id' => $p->id,
                'text' => "{$p->name_en} ({$p->person_code}) - {$p->phone}",
            ];
        });

        return response()->json(['results' => $results]);
    }

    // -------------------------------------------------------------
    // Formatters for DataTables
    // -------------------------------------------------------------

    protected function formatDonorInfoColumn(CollectionPayment $payment): string
    {
        $donorName = e($payment->payer_display_name);
        $phone = e($payment->public_donor_phone ?: ($payment->payer_phone_override ?: $payment->sender_mobile));
        $email = e($payment->public_donor_email);

        $html = '<div class="fw-semibold">'.$donorName.'</div>';

        if ($payment->payer_person_id && $payment->payerPerson) {
            $personUrl = route('people.show', $payment->payer_person_id);
            $html .= '<div class="small"><a href="'.$personUrl.'" class="badge bg-success-subtle text-success border text-decoration-none"><i class="bi bi-person-check me-1"></i>'.e($payment->payerPerson->person_code).'</a></div>';
        } else {
            $html .= '<div class="small"><span class="badge bg-light text-muted border"><i class="bi bi-person-dash me-1"></i>Guest</span></div>';
        }

        if ($phone) {
            $html .= '<div class="small text-muted"><i class="bi bi-telephone me-1"></i>'.$phone.'</div>';
        }
        if ($email) {
            $html .= '<div class="small text-muted"><i class="bi bi-envelope me-1"></i>'.$email.'</div>';
        }

        return $html;
    }

    protected function formatEventColumn(CollectionPayment $payment): string
    {
        if ($payment->event) {
            $eventUrl = route('portal.events.show', $payment->event_id);

            return '<div><a href="'.$eventUrl.'" class="text-decoration-none fw-semibold">'.e($payment->event->name).'</a>'
                .'<div class="small text-muted"><code>'.e($payment->event->event_code).'</code></div></div>';
        }

        return '<span class="badge bg-light text-dark border">'.__('portal.payments.general_temple_donation').'</span>';
    }

    protected function formatAmountColumn(CollectionPayment $payment): string
    {
        $amount = number_format((float) $payment->amount, 2);
        $methodBadge = match ($payment->payment_method) {
            'bkash' => '<span class="badge bg-danger">bKash</span>',
            'nagad' => '<span class="badge bg-warning text-dark">Nagad</span>',
            'rocket' => '<span class="badge bg-primary">Rocket</span>',
            'bank_transfer' => '<span class="badge bg-dark">Bank</span>',
            default => '<span class="badge bg-secondary">'.e(ucwords($payment->payment_method)).'</span>',
        };

        return '<div class="fw-bold fs-6 text-success">৳ '.$amount.'</div><div>'.$methodBadge.'</div>';
    }

    protected function formatTransactionColumn(CollectionPayment $payment): string
    {
        $trxId = e($payment->transaction_reference ?: '—');
        $receiverName = $payment->receiverAccount ? e($payment->receiverAccount->account_name.' ('.$payment->receiverAccount->account_number.')') : '—';
        $senderMobile = e($payment->sender_mobile ?: '—');

        return '<div><span class="small text-muted">TrxID:</span> <code class="fw-bold">'.$trxId.'</code></div>'
            .'<div class="small text-muted"><span class="fw-semibold">To:</span> '.$receiverName.'</div>'
            .'<div class="small text-muted"><span class="fw-semibold">From:</span> '.$senderMobile.'</div>';
    }

    protected function formatProofColumn(CollectionPayment $payment): string
    {
        if ($payment->payment_proof_path) {
            $url = e($payment->payment_proof_url);

            return '<a href="'.$url.'" target="_blank" title="'.__('portal.actions.preview').'">'
                .'<img src="'.$url.'" alt="Proof" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">'
                .'</a>';
        }

        return '<span class="badge bg-light text-muted border">'.__('portal.payments.no_proof').'</span>';
    }

    protected function formatStatusBadge(string $status): string
    {
        return match ($status) {
            CollectionPayment::STATUS_VERIFIED => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>'.__('donations.status_verified').'</span>',
            CollectionPayment::STATUS_REJECTED => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>'.__('donations.status_rejected').'</span>',
            CollectionPayment::STATUS_REVERSED => '<span class="badge bg-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>'.__('donations.status_reversed').'</span>',
            default => '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>'.__('donations.status_pending').'</span>',
        };
    }

    protected function formatActionsColumn(CollectionPayment $payment): string
    {
        $showUrl = route('portal.payments.show', $payment);
        $user = auth()->user();

        $html = '<div class="btn-group btn-group-sm" role="group">';
        $html .= '<a href="'.$showUrl.'" class="btn btn-outline-secondary" title="'.__('portal.actions.view').'"><i class="bi bi-eye"></i></a>';

        if ($payment->status === CollectionPayment::STATUS_PENDING && $user) {
            if ($user->can('verify_public_payments')) {
                $html .= '<button type="button" class="btn btn-outline-success btn-verify-payment" '
                    .'data-id="'.$payment->id.'" '
                    .'data-code="'.$payment->payment_code.'" '
                    .'data-amount="'.number_format((float) $payment->amount, 2).'" '
                    .'data-trx="'.e($payment->transaction_reference).'" '
                    .'data-url="'.route('portal.payments.verify', $payment).'" '
                    .'title="'.__('portal.payments.verify_and_post').'">'
                    .'<i class="bi bi-check-lg"></i>'
                    .'</button>';
            }

            if ($user->can('reject_public_payments') || $user->can('verify_public_payments')) {
                $html .= '<button type="button" class="btn btn-outline-danger btn-reject-payment" '
                    .'data-id="'.$payment->id.'" '
                    .'data-code="'.$payment->payment_code.'" '
                    .'data-url="'.route('portal.payments.reject', $payment).'" '
                    .'title="'.__('portal.payments.reject').'">'
                    .'<i class="bi bi-x-lg"></i>'
                    .'</button>';
            }
        }

        $html .= '</div>';

        return $html;
    }
}
