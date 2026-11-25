<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\RejectPublicPaymentRequest;
use App\Http\Requests\PublicPortal\ReversePublicPaymentRequest;
use App\Http\Requests\PublicPortal\VerifyPublicPaymentRequest;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicPaymentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_public_payments', only: ['index', 'show', 'searchPersons', 'downloadProof']),
            new Middleware('permission:verify_public_payments', only: ['verify', 'linkDevotee']),
            new Middleware('permission:reject_public_payments|verify_public_payments', only: ['reject']),
            new Middleware('permission:reverse_payments|reverse_collections', only: ['reverse']),
        ];
    }

    /**
     * Display a listing of public payment submissions.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax() || $request->wantsJson()) {
            $query = CollectionPayment::with(['payerPerson', 'event', 'receiverAccount', 'receipt', 'pledge', 'journalEntry'])
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
            'reverser',
            'pledge',
            'allocations.pledge',
            'allocations.event',
            'journalEntry.lines.account',
            'journalEntry.reversedByJournal.lines.account',
        ]);

        $pledge = $payment->linkedPledge;
        if (! $pledge && $payment->collection_type === CollectionPayment::TYPE_EVENT_PLEDGE && $payment->payer_person_id && $payment->event_id) {
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
     * Download or stream payment proof for authorized administrator.
     */
    public function downloadProof(CollectionPayment $payment): Response
    {
        if (! $payment->payment_proof_path) {
            abort(404, __('portal.payments.no_proof'));
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

        abort(404, __('portal.payments.no_proof'));
    }

    /**
     * Verify and post public payment.
     */
    public function verify(VerifyPublicPaymentRequest $request, CollectionPayment $payment, CollectionPaymentService $paymentService): JsonResponse|RedirectResponse
    {
        return DB::transaction(function () use ($payment, $request, $paymentService) {
            /** @var CollectionPayment $lockedPayment */
            $lockedPayment = CollectionPayment::where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== CollectionPayment::STATUS_PENDING) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => __('portal.payments.already_processed')], 422);
                }

                return back()->with('error', __('portal.payments.already_processed'));
            }

            // Link person if provided in verify request
            if ($request->filled('person_id')) {
                $targetPerson = Person::findOrFail($request->integer('person_id'));
                if ($targetPerson->status !== 'active') {
                    if ($request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => __('portal.payments.inactive_or_invalid_person')], 422);
                    }

                    return back()->with('error', __('portal.payments.inactive_or_invalid_person'));
                }

                $lockedPayment->update([
                    'payer_person_id' => $targetPerson->id,
                    'updated_by' => auth()->id(),
                ]);
            }

            // Public payment must be against a registered devotee profile
            if (! $lockedPayment->payer_person_id) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => __('portal.payments.payment_against_pledge_only')], 422);
                }

                return back()->with('error', __('portal.payments.payment_against_pledge_only'));
            }

            // Method-aware duplicate transaction reference prevention
            if ($lockedPayment->transaction_reference && in_array($lockedPayment->payment_method, [
                CollectionPayment::METHOD_BKASH,
                CollectionPayment::METHOD_NAGAD,
                CollectionPayment::METHOD_ROCKET,
                CollectionPayment::METHOD_BANK_TRANSFER,
            ])) {
                $existingTrx = CollectionPayment::where('id', '!=', $lockedPayment->id)
                    ->where('payment_method', $lockedPayment->payment_method)
                    ->where('transaction_reference', $lockedPayment->transaction_reference)
                    ->whereIn('status', [CollectionPayment::STATUS_VERIFIED, CollectionPayment::STATUS_PENDING])
                    ->exists();

                if ($existingTrx) {
                    $errorMsg = __('portal.payments.duplicate_transaction_reference', [
                        'method' => strtoupper($lockedPayment->payment_method),
                        'trx' => $lockedPayment->transaction_reference,
                    ]);

                    if ($request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $errorMsg], 422);
                    }

                    return back()->with('error', $errorMsg);
                }
            }

            // Resolve associated pledge
            $pledgeId = $request->integer('pledge_id') ?: $lockedPayment->pledge_id;
            $pledge = null;
            if ($pledgeId) {
                $pledge = Pledge::where('id', $pledgeId)->lockForUpdate()->first();
                if ($pledge) {
                    if ($pledge->status === Pledge::STATUS_CANCELLED) {
                        $msg = __('donations.cannot_allocate_to_cancelled_pledge');
                        if ($request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $msg], 422);
                        }

                        return back()->with('error', $msg);
                    }
                    if ((int) $pledge->person_id !== (int) $lockedPayment->payer_person_id) {
                        $msg = __('portal.payments.pledge_donor_mismatch');
                        if ($request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $msg], 422);
                        }

                        return back()->with('error', $msg);
                    }
                    if ($lockedPayment->event_id && (int) $pledge->event_id !== (int) $lockedPayment->event_id) {
                        $msg = __('portal.payments.pledge_event_mismatch');
                        if ($request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $msg], 422);
                        }

                        return back()->with('error', $msg);
                    }
                }
            }

            if (! $pledge && $lockedPayment->collection_type === CollectionPayment::TYPE_EVENT_PLEDGE && $lockedPayment->payer_person_id && $lockedPayment->event_id) {
                $pledge = Pledge::where('person_id', $lockedPayment->payer_person_id)
                    ->where('event_id', $lockedPayment->event_id)
                    ->where('status', '!=', Pledge::STATUS_CANCELLED)
                    ->lockForUpdate()
                    ->first();
            }

            if ($pledge) {
                if ($lockedPayment->pledge_id !== $pledge->id || $lockedPayment->collection_type !== CollectionPayment::TYPE_EVENT_PLEDGE) {
                    $lockedPayment->update([
                        'pledge_id' => $pledge->id,
                        'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
                    ]);
                }

                // Ensure active pledge allocation exists
                $allocation = $lockedPayment->allocations()->where('is_active', true)->first();
                if (! $allocation) {
                    PaymentAllocation::create([
                        'collection_payment_id' => $lockedPayment->id,
                        'target_type' => PaymentAllocation::TARGET_PLEDGE,
                        'pledge_id' => $pledge->id,
                        'event_id' => $lockedPayment->event_id,
                        'amount' => $lockedPayment->amount,
                        'is_active' => true,
                    ]);
                } else {
                    $allocation->update([
                        'target_type' => PaymentAllocation::TARGET_PLEDGE,
                        'pledge_id' => $pledge->id,
                        'event_id' => $lockedPayment->event_id,
                    ]);
                }
            } else {
                // Voluntary event/general donation without requiring or creating a pledge
                $targetType = $lockedPayment->event_id ? PaymentAllocation::TARGET_VOLUNTARY_EVENT : PaymentAllocation::TARGET_VOLUNTARY_GENERAL;
                $collectionType = $lockedPayment->event_id ? CollectionPayment::TYPE_EVENT_DONATION : CollectionPayment::TYPE_GENERAL_DONATION;

                if ($lockedPayment->pledge_id !== null || $lockedPayment->collection_type !== $collectionType) {
                    $lockedPayment->update([
                        'pledge_id' => null,
                        'collection_type' => $collectionType,
                    ]);
                }

                // Ensure active voluntary allocation exists
                $allocation = $lockedPayment->allocations()->where('is_active', true)->first();
                if (! $allocation) {
                    PaymentAllocation::create([
                        'collection_payment_id' => $lockedPayment->id,
                        'target_type' => $targetType,
                        'pledge_id' => null,
                        'event_id' => $lockedPayment->event_id,
                        'amount' => $lockedPayment->amount,
                        'is_active' => true,
                    ]);
                } else {
                    $allocation->update([
                        'target_type' => $targetType,
                        'pledge_id' => null,
                        'event_id' => $lockedPayment->event_id,
                    ]);
                }
            }

            try {
                $verifiedPayment = $paymentService->verifyPayment(
                    $lockedPayment,
                    auth()->id(),
                    $request->input('verification_note')
                );

                $auditMsg = $pledge
                    ? "Verified public payment {$lockedPayment->payment_code} against pledge {$pledge->pledge_code} (TrxID: {$lockedPayment->transaction_reference}) for ৳".number_format($lockedPayment->amount, 2)
                    : ($lockedPayment->event_id
                        ? "Verified voluntary public event payment {$lockedPayment->payment_code} (TrxID: {$lockedPayment->transaction_reference}) for ৳".number_format($lockedPayment->amount, 2)
                        : "Verified voluntary public general payment {$lockedPayment->payment_code} (TrxID: {$lockedPayment->transaction_reference}) for ৳".number_format($lockedPayment->amount, 2));

                AuditLogger::log(
                    'public_payment_verified',
                    $auditMsg,
                    $lockedPayment
                );

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('portal.payments.verified_successfully'),
                        'receipt_number' => $verifiedPayment->receipt?->receipt_number,
                    ]);
                }

                return redirect()
                    ->route('portal.payments.show', $lockedPayment)
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
        });
    }

    /**
     * Reject public payment with reason.
     */
    public function reject(RejectPublicPaymentRequest $request, CollectionPayment $payment, CollectionPaymentService $paymentService): JsonResponse|RedirectResponse
    {
        return DB::transaction(function () use ($payment, $request, $paymentService) {
            /** @var CollectionPayment $lockedPayment */
            $lockedPayment = CollectionPayment::where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== CollectionPayment::STATUS_PENDING) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => __('portal.payments.already_processed')], 422);
                }

                return back()->with('error', __('portal.payments.already_processed'));
            }

            try {
                $paymentService->rejectPayment(
                    $lockedPayment,
                    auth()->id(),
                    $request->input('rejection_reason')
                );

                AuditLogger::log(
                    'public_payment_rejected',
                    "Rejected public payment {$lockedPayment->payment_code} (Reason: {$request->input('rejection_reason')})",
                    $lockedPayment
                );

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('portal.payments.rejected_successfully'),
                    ]);
                }

                return redirect()
                    ->route('portal.payments.show', $lockedPayment)
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
        });
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
        if ($person->status !== 'active') {
            $msg = __('portal.payments.inactive_or_invalid_person');
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        return DB::transaction(function () use ($payment, $person, $request) {
            /** @var CollectionPayment $lockedPayment */
            $lockedPayment = CollectionPayment::where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== CollectionPayment::STATUS_PENDING) {
                $msg = __('portal.payments.cannot_relink_processed_payment');
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }

                return back()->with('error', $msg);
            }

            $lockedPayment->update([
                'payer_person_id' => $person->id,
                'updated_by' => auth()->id(),
            ]);

            // If the payment had an attached pledge belonging to another person, unlink it to prevent cross-person contamination
            if ($lockedPayment->pledge_id) {
                $attachedPledge = Pledge::find($lockedPayment->pledge_id);
                if ($attachedPledge && (int) $attachedPledge->person_id !== (int) $person->id) {
                    $targetType = $lockedPayment->event_id ? PaymentAllocation::TARGET_VOLUNTARY_EVENT : PaymentAllocation::TARGET_VOLUNTARY_GENERAL;
                    $collectionType = $lockedPayment->event_id ? CollectionPayment::TYPE_EVENT_DONATION : CollectionPayment::TYPE_GENERAL_DONATION;

                    $lockedPayment->update([
                        'pledge_id' => null,
                        'collection_type' => $collectionType,
                    ]);

                    $lockedPayment->allocations()->where('is_active', true)->update([
                        'target_type' => $targetType,
                        'pledge_id' => null,
                    ]);
                }
            }

            AuditLogger::log(
                'public_payment_linked_person',
                "Linked payment {$lockedPayment->payment_code} to person {$person->name_en} ({$person->person_code})",
                $lockedPayment
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('portal.payments.linked_successfully'),
                    'person_name' => $person->name_en,
                ]);
            }

            return back()->with('success', __('portal.payments.linked_successfully'));
        });
    }

    /**
     * Reverse a verified public payment and post compensating inverse journal entry.
     */
    public function reverse(ReversePublicPaymentRequest $request, CollectionPayment $payment, CollectionPaymentService $paymentService): JsonResponse|RedirectResponse
    {
        try {
            $reason = (string) $request->validated('reversal_reason');
            $reversedPayment = $paymentService->reversePayment($payment, (int) auth()->id(), $reason);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('donations.payment_reversed_success'),
                    'payment' => [
                        'id' => $reversedPayment->id,
                        'payment_code' => $reversedPayment->payment_code,
                        'status' => $reversedPayment->status,
                    ],
                ]);
            }

            return redirect()
                ->route('portal.payments.show', $reversedPayment)
                ->with('success', __('donations.payment_reversed_success'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('common.error_occurred'),
                ], 500);
            }

            return back()->with('error', __('common.error_occurred'));
        }
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

        $locale = app()->getLocale();

        $results = $persons->map(function ($p) use ($locale) {
            $name = ($locale === 'bn' && ! empty($p->name_bn)) ? $p->name_bn : ($p->name_en ?: $p->name_bn);
            $phone = $p->phone ? " - {$p->phone}" : '';

            return [
                'id' => $p->id,
                'text' => "{$name}{$phone}",
                'name' => $name,
                'phone' => $p->phone,
                'person_code' => $p->person_code,
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
        $methodBadge = view('partials.payment-method-badge', ['method' => $payment->payment_method])->render();

        return '<div class="fw-bold fs-6 text-success mb-1">৳ '.$amount.'</div><div>'.$methodBadge.'</div>';
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
            $url = route('portal.payments.proof', $payment);

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

        if ($payment->status === CollectionPayment::STATUS_VERIFIED && $user) {
            if ($payment->receipt) {
                if ($user->can('view_receipts')) {
                    $html .= '<a href="'.route('receipts.show', $payment->receipt).'" class="btn btn-outline-primary" title="'.__('donations.view_receipt').'"><i class="bi bi-receipt"></i></a>';
                }
                if ($user->can('print_receipts')) {
                    $html .= '<a href="'.route('receipts.print', $payment->receipt).'" target="_blank" class="btn btn-outline-dark" title="'.__('donations.print_receipt').'"><i class="bi bi-printer"></i></a>';
                }
            }

            if (($user->hasPermission('reverse_payments') || $user->hasPermission('reverse_collections') || $user->hasRole('super_admin'))
                && ($payment->isFinancePosted() || $payment->finance_post_reference || $payment->journalEntry)) {
                $pledge = $payment->pledge;
                if (! $pledge && $payment->payer_person_id && $payment->event_id) {
                    $pledge = Pledge::where('person_id', $payment->payer_person_id)
                        ->where('event_id', $payment->event_id)
                        ->where('status', '!=', Pledge::STATUS_CANCELLED)
                        ->first();
                }

                $html .= '<button type="button" class="btn btn-outline-danger btn-reverse-payment" '
                    .'data-id="'.$payment->id.'" '
                    .'data-code="'.$payment->payment_code.'" '
                    .'data-amount="'.number_format((float) $payment->amount, 2).'" '
                    .'data-donor="'.e($payment->payer_display_name).'" '
                    .'data-event="'.e($payment->event?->name ?? __('portal.payments.general_temple_donation')).'" '
                    .'data-method="'.e(ucwords(str_replace('_', ' ', $payment->payment_method))).'" '
                    .'data-trx="'.e($payment->transaction_reference ?: '—').'" '
                    .'data-receipt="'.e($payment->receipt?->receipt_number ?? '—').'" '
                    .'data-type="'.e($payment->collection_type).'" '
                    .'data-pledge-code="'.e($pledge?->pledge_code ?? '').'" '
                    .'data-pledged="'.number_format((float) ($pledge?->pledged_amount ?? 0), 2).'" '
                    .'data-paid="'.number_format((float) ($pledge?->verified_paid_amount ?? 0), 2).'" '
                    .'data-due="'.number_format((float) ($pledge?->outstanding_amount ?? 0), 2).'" '
                    .'data-url="'.route('portal.payments.reverse', $payment).'" '
                    .'title="'.__('donations.reverse_payment').'">'
                    .'<i class="bi bi-arrow-counterclockwise"></i>'
                    .'</button>';
            }
        } elseif ($payment->status === CollectionPayment::STATUS_PENDING && $user) {
            if ($user->can('verify_public_payments')) {
                $pledge = $payment->pledge;
                if (! $pledge && $payment->payer_person_id && $payment->event_id) {
                    $pledge = Pledge::where('person_id', $payment->payer_person_id)
                        ->where('event_id', $payment->event_id)
                        ->where('status', '!=', Pledge::STATUS_CANCELLED)
                        ->first();
                }
                $html .= '<button type="button" class="btn btn-outline-success btn-verify-payment" '
                    .'data-id="'.$payment->id.'" '
                    .'data-code="'.$payment->payment_code.'" '
                    .'data-amount="'.number_format((float) $payment->amount, 2).'" '
                    .'data-donor="'.e($payment->payer_display_name).'" '
                    .'data-event="'.e($payment->event?->name ?? __('portal.payments.general_temple_donation')).'" '
                    .'data-method="'.e(ucwords(str_replace('_', ' ', $payment->payment_method))).'" '
                    .'data-trx="'.e($payment->transaction_reference ?: '—').'" '
                    .'data-proof="'.e($payment->payment_proof_path ? route('portal.payments.proof', $payment) : '').'" '
                    .'data-type="'.e($payment->collection_type).'" '
                    .'data-pledge-code="'.e($pledge?->pledge_code ?? '').'" '
                    .'data-pledged="'.number_format((float) ($pledge?->pledged_amount ?? 0), 2).'" '
                    .'data-paid="'.number_format((float) ($pledge?->verified_paid_amount ?? 0), 2).'" '
                    .'data-due="'.number_format((float) ($pledge?->outstanding_amount ?? 0), 2).'" '
                    .'data-url="'.route('portal.payments.verify', $payment).'" '
                    .'title="'.__('portal.payments.verify_and_post').'">'
                    .'<i class="bi bi-check-lg"></i>'
                    .'</button>';
            }

            if ($user->can('reject_public_payments') || $user->can('verify_public_payments')) {
                $html .= '<button type="button" class="btn btn-outline-danger btn-reject-payment" '
                    .'data-id="'.$payment->id.'" '
                    .'data-code="'.$payment->payment_code.'" '
                    .'data-donor="'.e($payment->payer_display_name).'" '
                    .'data-event="'.e($payment->event?->name ?? __('portal.payments.general_temple_donation')).'" '
                    .'data-amount="'.number_format((float) $payment->amount, 2).'" '
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
