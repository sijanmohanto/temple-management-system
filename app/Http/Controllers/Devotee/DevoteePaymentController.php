<?php

namespace App\Http\Controllers\Devotee;

use App\Http\Controllers\Controller;
use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\PaymentAllocation;
use App\Models\Pledge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DevoteePaymentController extends Controller
{
    /**
     * Display the My Payments & Contribution History page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $person = auth()->user()->person;

        if ($request->ajax() || $request->wantsJson()) {
            return $this->dataTableResponse($request, $person->id);
        }

        $events = Event::published()
            ->orderByDesc('event_year')
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_bn', 'slug', 'event_year']);

        return view('devotee.payments.index', compact('events', 'person'));
    }

    /**
     * Server-side DataTables JSON response for devotee payments.
     */
    protected function dataTableResponse(Request $request, int $personId): JsonResponse
    {
        $draw = $request->integer('draw');
        $start = $request->integer('start', 0);
        $length = $request->integer('length', 10);
        $searchValue = trim((string) $request->input('search.value', ''));

        $query = CollectionPayment::with([
            'event',
            'receiverAccount',
            'receipt',
            'allocations',
        ])
            ->where('payer_person_id', $personId);

        // Filter: Event
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->integer('event_id'));
        }

        // Filter: Status
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Filter: Payment Method
        if ($request->filled('payment_method') && $request->input('payment_method') !== 'all') {
            $query->where('payment_method', $request->input('payment_method'));
        }

        // Filter: Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->input('date_to'));
        }

        $totalRecords = CollectionPayment::where('payer_person_id', $personId)->count();

        // Search
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('payment_code', 'like', "%{$searchValue}%")
                    ->orWhere('transaction_reference', 'like', "%{$searchValue}%")
                    ->orWhere('amount', 'like', "%{$searchValue}%")
                    ->orWhereHas('event', function ($eq) use ($searchValue) {
                        $eq->where('name_en', 'like', "%{$searchValue}%")
                            ->orWhere('name_bn', 'like', "%{$searchValue}%");
                    });
            });
        }

        $filteredRecords = $query->count();

        $records = $query->orderByDesc('id')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $records->map(function (CollectionPayment $payment) {
            // Column 0: Event Title + Clickable Payment Code below
            $eventName = '<div class="fw-bold text-dark">'.($payment->event ? e($payment->event->name) : '—').'</div>';
            $eventName .= '<div class="small mt-1"><a href="javascript:void(0)" class="view-details-btn font-monospace fw-bold text-primary text-decoration-none" data-code="'.e($payment->payment_code).'" title="'.__('common.view').'">'.e($payment->payment_code).'</a></div>';

            // Column 1: Payment allocation type
            $firstAlloc = $payment->allocations->first();
            $typeText = match ($firstAlloc?->target_type) {
                PaymentAllocation::TARGET_VOLUNTARY_EVENT => __('donations.event_donation'),
                PaymentAllocation::TARGET_VOLUNTARY_GENERAL => __('donations.general_donation'),
                default => __('donations.pledge'),
            };
            $typeHtml = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">'.$typeText.'</span>';

            // Column 2: Amount
            $amountHtml = '<div class="fw-bold text-dark text-end">৳'.number_format((float) $payment->amount, 2).'</div>';

            // Column 3: Payment Method with Transaction Reference below
            $methodBadge = view('partials.payment-method-badge', ['method' => $payment->payment_method])->render();
            if ($payment->transaction_reference) {
                $methodBadge .= '<div class="small text-muted font-monospace mt-1"><i class="bi bi-hash"></i>'.e($payment->transaction_reference).'</div>';
            }

            // Column 4: Date
            $dateHtml = '<div class="small text-muted font-monospace">'.($payment->payment_date ? $payment->payment_date->format('d M Y') : '—').'</div>';

            // Column 5: Status Badge
            $statusBadge = match ($payment->status) {
                CollectionPayment::STATUS_VERIFIED => '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="bi bi-check-circle me-1"></i>'.__('common.status_verified').'</span>',
                CollectionPayment::STATUS_PENDING => '<span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25"><i class="bi bi-clock me-1"></i>'.__('common.status_pending').'</span>',
                CollectionPayment::STATUS_REJECTED => '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="bi bi-x-circle me-1"></i>'.__('common.status_rejected').'</span>',
                CollectionPayment::STATUS_REVERSED => '<span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25"><i class="bi bi-arrow-counterclockwise me-1"></i>'.__('donations.status_reversed').'</span>',
                default => '<span class="badge bg-secondary">'.e($payment->status).'</span>',
            };

            // Column 6: Actions
            $actions = '<div class="d-inline-flex gap-1 justify-content-end">';
            $actions .= '<button type="button" class="table-action-btn table-action-btn-view view-details-btn" data-code="'.e($payment->payment_code).'" title="'.__('common.view').'">';
            $actions .= '<i class="bi bi-eye"></i></button>';

            if ($payment->isVerified() && $payment->receipt && ! $payment->receipt->isVoided()) {
                $actions .= '<a href="'.route('public.payments.receipt', $payment->receipt->receipt_number).'" target="_blank" class="table-action-btn table-action-btn-edit text-success" title="'.__('donations.receipt').'">';
                $actions .= '<i class="bi bi-receipt"></i></a>';

                $actions .= '<a href="'.route('public.payments.receipt.download', $payment->receipt->receipt_number).'" class="table-action-btn table-action-btn-edit text-primary" title="'.__('common.download').'">';
                $actions .= '<i class="bi bi-download"></i></a>';
            }
            $actions .= '</div>';

            return [
                $eventName,
                $typeHtml,
                $amountHtml,
                $methodBadge,
                $dateHtml,
                $statusBadge,
                $actions,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Get payment details for Offcanvas view.
     */
    public function details(Request $request, string $paymentCode): JsonResponse
    {
        $person = auth()->user()->person;

        $payment = CollectionPayment::with([
            'event',
            'receiverAccount',
            'receipt',
            'allocations.pledge',
        ])
            ->where('payment_code', $paymentCode)
            ->firstOrFail();

        // Strict ownership check
        if ((int) $payment->payer_person_id !== (int) $person->id && (int) $payment->submitted_by !== (int) auth()->id()) {
            abort(403, __('portal.public.unauthorized_cross_donor'));
        }

        $pledge = Pledge::where('person_id', $person->id)
            ->where('event_id', $payment->event_id)
            ->where('status', '!=', Pledge::STATUS_CANCELLED)
            ->first();

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
                'verified_at' => $payment->verified_at?->format('d M Y, h:i A'),
                'reversed_at' => $payment->reversed_at?->format('d M Y, h:i A'),
                'receipt_number' => $payment->isVerified() && $payment->receipt && ! $payment->receipt->isVoided() ? $payment->receipt->receipt_number : null,
                'receipt_url' => $payment->isVerified() && $payment->receipt && ! $payment->receipt->isVoided() ? route('public.payments.receipt', $payment->receipt->receipt_number) : null,
                'pledge_code' => $pledge?->pledge_code,
                'pledged_amount' => $pledge ? (float) $pledge->pledged_amount : null,
                'verified_paid_amount' => $pledge ? (float) $pledge->verified_paid_amount : null,
                'outstanding_amount' => $pledge ? (float) $pledge->outstanding_amount : null,
                'allocations' => $payment->allocations->map(fn ($a) => [
                    'target_type' => $a->target_type,
                    'amount' => (float) $a->amount,
                    'notes' => $a->notes,
                ]),
            ],
        ]);
    }
}
