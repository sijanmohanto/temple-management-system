<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectCollectionPaymentRequest;
use App\Http\Requests\ReverseCollectionPaymentRequest;
use App\Http\Requests\StoreCollectionPaymentRequest;
use App\Http\Requests\VerifyCollectionPaymentRequest;
use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\Family;
use App\Models\MonthlyCommitteeContribution;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Services\CollectionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CollectionPaymentController extends Controller implements HasMiddleware
{
    public function __construct(
        protected CollectionPaymentService $paymentService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_collections', only: ['index', 'show']),
            new Middleware('permission:record_collections', only: ['create', 'store', 'payerObligations']),
            new Middleware('permission:verify_collections', only: ['verify', 'reject']),
            new Middleware('permission:reverse_collections', only: ['reverse']),
        ];
    }

    /**
     * Display a listing of collection payments with server-side DataTables support.
     */
    public function index(Request $request): View|JsonResponse
    {
        $rawSearch = $request->input('search');
        $searchString = is_array($rawSearch) ? ($rawSearch['value'] ?? '') : (is_string($rawSearch) ? $rawSearch : '');
        $searchString = trim((string) $searchString);

        $filters = $request->only(['status', 'collection_type', 'payment_method', 'event_id', 'payment_year']);
        if (! empty($searchString)) {
            $filters['search'] = $searchString;
        }

        if (($request->ajax() || $request->wantsJson()) && $request->has('draw')) {
            return $this->dataTableResponse($request, $filters);
        }

        $payments = CollectionPayment::query()
            ->with(['payerPerson', 'payerFamily', 'event', 'receipt', 'submitter', 'verifier'])
            ->filter($filters)
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        $events = Event::orderByDesc('start_date')->get();
        $years = CollectionPayment::distinct()->orderByDesc('payment_year')->pluck('payment_year')->toArray();
        if (! in_array((int) date('Y'), $years, true)) {
            $years[] = (int) date('Y');
            rsort($years);
        }

        $pendingCount = CollectionPayment::where('status', CollectionPayment::STATUS_PENDING)->count();

        return view('donations.collections.index', compact('payments', 'filters', 'events', 'years', 'pendingCount'));
    }

    /**
     * Server-side DataTables JSON response.
     */
    protected function dataTableResponse(Request $request, array $filters): JsonResponse
    {
        $draw = $request->integer('draw');
        $start = $request->integer('start', 0);
        $length = $request->integer('length', 15);
        $searchValue = trim($request->input('search.value', ''));

        $query = CollectionPayment::query()->with(['payerPerson', 'payerFamily', 'event', 'receipt', 'submitter', 'verifier']);
        $totalRecords = CollectionPayment::count();

        unset($filters['search']);
        $query->filter($filters);

        if (! empty($searchValue)) {
            $query->search($searchValue);
        }

        $filteredRecords = $query->count();

        $orderColumnIndex = $request->has('order.0.column') ? (int) $request->input('order.0.column') : null;
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columnMap = [
            0 => 'payment_code',
            2 => 'payment_date',
            4 => 'amount',
            5 => 'status',
        ];

        if ($orderColumnIndex !== null && isset($columnMap[$orderColumnIndex])) {
            $query->orderBy($columnMap[$orderColumnIndex], $orderDir);
        } else {
            $query->latest('payment_date');
        }

        $records = $query->skip($start)->take($length)->get();
        $canVerify = $request->user()?->hasPermission('verify_collections') ?? false;
        $canSeeSensitive = $request->user()?->hasPermission('view_sensitive_payment_details') ?? false;

        $data = $records->map(function (CollectionPayment $payment) use ($canVerify, $canSeeSensitive) {
            $codeHtml = '<span class="badge bg-light text-dark border font-monospace">'.e($payment->payment_code).'</span>';
            if ($payment->receipt) {
                $badgeClass = $payment->receipt->isValid() ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                $codeHtml .= '<div class="mt-1"><a href="'.route('receipts.show', $payment->receipt).'" class="badge '.$badgeClass.' text-decoration-none" title="'.e(__('donations.view_receipt')).'">'
                    .'<i class="bi bi-receipt me-1"></i>'.e($payment->receipt->receipt_number).'</a></div>';
            }

            $payerHtml = '<div class="fw-bold text-dark">'.e($payment->payer_display_name).'</div>';
            if ($payment->payerPerson && $payment->payerPerson->phone) {
                $payerHtml .= '<small class="text-muted"><i class="bi bi-telephone me-1"></i>'.e($payment->payerPerson->phone).'</small>';
            }

            $contextHtml = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mb-1" style="font-size: 0.72rem;">'
                .e(__('donations.type_'.$payment->collection_type)).'</span>';
            if ($payment->event) {
                $contextHtml .= '<div class="small text-dark fw-semibold">'.e($payment->event->name).'</div>';
            }
            $contextHtml .= '<div class="small text-muted"><i class="bi bi-calendar3 me-1"></i>'.e($payment->payment_date->format('d M Y')).'</div>';

            $methodHtml = '<div class="fw-semibold text-dark"><i class="bi bi-credit-card me-1 text-muted"></i>'.e(__('donations.method_'.$payment->payment_method)).'</div>';
            if ($payment->transaction_reference) {
                $ref = $canSeeSensitive ? e($payment->transaction_reference) : substr(e($payment->transaction_reference), 0, 4).'****';
                $methodHtml .= '<div class="small text-muted font-monospace"><i class="bi bi-hash me-1"></i>'.$ref.'</div>';
            }

            $amountHtml = '<div class="fs-6 fw-bold text-dark">৳'.number_format((float) $payment->amount, 2).'</div>';

            $statusBadge = match ($payment->status) {
                CollectionPayment::STATUS_VERIFIED => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                CollectionPayment::STATUS_PENDING => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                CollectionPayment::STATUS_REJECTED => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                CollectionPayment::STATUS_REVERSED => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                default => 'bg-secondary bg-opacity-10 text-secondary',
            };

            $statusHtml = '<span class="badge '.$statusBadge.' px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>'.e(__('donations.status_'.$payment->status)).'</span>';

            $actionsHtml = '<div class="btn-group btn-group-sm text-end">'
                .'<a href="'.route('collections.show', $payment).'" class="btn btn-outline-primary" title="'.e(__('common.view')).'"><i class="bi bi-eye"></i></a>';

            if ($payment->isPending() && $canVerify) {
                $actionsHtml .= '<a href="'.route('collections.show', $payment).'" class="btn btn-outline-success" title="'.e(__('donations.verify_payment')).'"><i class="bi bi-check2-circle"></i></a>';
            }

            if ($payment->receipt && $payment->receipt->isValid()) {
                $actionsHtml .= '<a href="'.route('receipts.print', $payment->receipt).'" target="_blank" class="btn btn-outline-secondary" title="'.e(__('donations.print_receipt')).'"><i class="bi bi-printer"></i></a>';
            }

            $actionsHtml .= '</div>';

            return [
                $codeHtml,
                $payerHtml,
                $contextHtml,
                $methodHtml,
                $amountHtml,
                $statusHtml,
                $actionsHtml,
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
     * Show form for submitting a payment.
     */
    public function create(Request $request): View
    {
        $events = Event::whereIn('status', [Event::STATUS_ACTIVE, Event::STATUS_UPCOMING, Event::STATUS_CLOSING])
            ->orderBy('start_date')
            ->get();

        $pledgeId = old('pledge_id', $request->input('pledge_id'));
        $selectedPledge = null;
        if ($pledgeId) {
            $selectedPledge = Pledge::with(['event', 'person', 'family'])->find($pledgeId);
        }

        $contributionId = old('monthly_contribution_id', $request->input('monthly_contribution_id'));
        $selectedMonthlyContribution = null;
        if ($contributionId) {
            $selectedMonthlyContribution = MonthlyCommitteeContribution::with(['membership.person', 'membership.committee'])->find($contributionId);
        }

        $selectedPerson = null;
        $personId = old('payer_person_id', $request->input('person_id'));
        if ($personId) {
            $selectedPerson = Person::find($personId);
        } elseif ($selectedPledge?->person) {
            $selectedPerson = $selectedPledge->person;
        } elseif ($selectedMonthlyContribution?->membership?->person) {
            $selectedPerson = $selectedMonthlyContribution->membership->person;
        }

        $selectedFamily = null;
        $familyId = old('payer_family_id', $request->input('family_id'));
        if ($familyId) {
            $selectedFamily = Family::find($familyId);
        } elseif ($selectedPledge?->family) {
            $selectedFamily = $selectedPledge->family;
        }

        $selectedEventId = old('event_id', $request->input('event_id', $selectedPledge?->event_id));

        return view('donations.collections.create', compact(
            'events',
            'selectedPledge',
            'selectedMonthlyContribution',
            'selectedPerson',
            'selectedFamily',
            'selectedEventId'
        ));
    }

    /**
     * Return outstanding obligations for a selected payer via AJAX.
     */
    public function payerObligations(Request $request): JsonResponse
    {
        $personId = $request->integer('person_id');
        $familyId = $request->integer('family_id');

        $pledges = Pledge::query()
            ->with('event')
            ->whereIn('status', [Pledge::STATUS_UNPAID, Pledge::STATUS_PARTIAL])
            ->when($personId, fn ($q) => $q->where('person_id', $personId))
            ->when($familyId, fn ($q) => $q->where('family_id', $familyId))
            ->when(! $personId && ! $familyId, fn ($q) => $q->whereRaw('1 = 0'))
            ->latest('pledged_on')
            ->get()
            ->map(fn (Pledge $p) => [
                'id' => $p->id,
                'pledge_code' => $p->pledge_code,
                'event_name' => $p->event?->name ?? 'General Pledge',
                'event_id' => $p->event_id,
                'pledged_amount' => (float) $p->pledged_amount,
                'outstanding_amount' => $p->outstanding_amount,
            ]);

        $contributions = [];
        if ($personId) {
            $contributions = MonthlyCommitteeContribution::query()
                ->with(['membership.committee'])
                ->whereIn('status', [MonthlyCommitteeContribution::STATUS_UNPAID, MonthlyCommitteeContribution::STATUS_PARTIAL])
                ->whereHas('membership', fn ($mq) => $mq->where('person_id', $personId))
                ->latest('contribution_month')
                ->get()
                ->map(fn (MonthlyCommitteeContribution $c) => [
                    'id' => $c->id,
                    'contribution_code' => $c->contribution_code,
                    'month_name' => $c->month_name,
                    'committee_name' => $c->membership?->committee?->name ?? 'Committee',
                    'expected_amount' => (float) $c->expected_amount,
                    'outstanding_amount' => $c->outstanding_amount,
                ]);
        }

        return response()->json([
            'pledges' => $pledges,
            'contributions' => $contributions,
        ]);
    }

    /**
     * Store submitted collection payment.
     */
    public function store(StoreCollectionPaymentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Server-side validation: ensure submitted payer matches the preselected obligation owner
        if (! empty($validated['pledge_id'])) {
            $pledge = Pledge::findOrFail($validated['pledge_id']);
            if ($pledge->person_id && (int) ($validated['payer_person_id'] ?? 0) !== (int) $pledge->person_id) {
                throw ValidationException::withMessages([
                    'payer_person_id' => __('donations.payer_must_match_pledge_owner'),
                ]);
            }
            if ($pledge->family_id && (int) ($validated['payer_family_id'] ?? 0) !== (int) $pledge->family_id) {
                throw ValidationException::withMessages([
                    'payer_family_id' => __('donations.payer_must_match_pledge_owner'),
                ]);
            }
        }

        if (! empty($validated['monthly_contribution_id'])) {
            $contrib = MonthlyCommitteeContribution::with('membership')->findOrFail($validated['monthly_contribution_id']);
            if ((int) ($validated['payer_person_id'] ?? 0) !== (int) $contrib->membership?->person_id) {
                throw ValidationException::withMessages([
                    'payer_person_id' => __('donations.payer_must_match_contribution_member'),
                ]);
            }
        }

        // Build allocations if not supplied as an explicit array in the request
        $allocationsData = $validated['allocations'] ?? [];
        if (empty($allocationsData)) {
            $type = $validated['collection_type'];
            $amount = (float) $validated['amount'];

            if ($type === CollectionPayment::TYPE_EVENT_PLEDGE) {
                $allocationsData[] = [
                    'target_type' => PaymentAllocation::TARGET_PLEDGE,
                    'pledge_id' => $validated['pledge_id'] ?? null,
                    'event_id' => $validated['event_id'] ?? null,
                    'amount' => $amount,
                ];
            } elseif ($type === CollectionPayment::TYPE_MONTHLY_CONTRIBUTION) {
                $allocationsData[] = [
                    'target_type' => PaymentAllocation::TARGET_MONTHLY_CONTRIBUTION,
                    'monthly_contribution_id' => $validated['monthly_contribution_id'] ?? null,
                    'amount' => $amount,
                ];
            } elseif ($type === CollectionPayment::TYPE_EVENT_DONATION) {
                $allocationsData[] = [
                    'target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT,
                    'event_id' => $validated['event_id'] ?? null,
                    'amount' => $amount,
                ];
            } else {
                $allocationsData[] = [
                    'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                    'amount' => $amount,
                ];
            }
        }

        $payment = $this->paymentService->submitPayment($validated, $allocationsData, Auth::id());

        return redirect()
            ->route('collections.show', $payment)
            ->with('success', __('donations.payment_submitted_success'));
    }

    /**
     * Display payment details, allocation history, and actions.
     */
    public function show(CollectionPayment $payment): View
    {
        $payment->load([
            'payerPerson',
            'payerFamily',
            'event',
            'submitter',
            'verifier',
            'rejecter',
            'reverser',
            'allocations.pledge.event',
            'allocations.monthlyContribution.membership.person',
            'receipt.issuer',
            'receipt.voider',
        ]);

        return view('donations.collections.show', compact('payment'));
    }

    /**
     * Verify payment.
     */
    public function verify(VerifyCollectionPaymentRequest $request, CollectionPayment $payment): RedirectResponse
    {
        $this->paymentService->verifyPayment($payment, Auth::id(), $request->verification_note);

        return redirect()
            ->route('collections.show', $payment)
            ->with('success', __('donations.payment_verified_success'));
    }

    /**
     * Reject payment submission.
     */
    public function reject(RejectCollectionPaymentRequest $request, CollectionPayment $payment): RedirectResponse
    {
        $this->paymentService->rejectPayment($payment, Auth::id(), $request->rejection_reason);

        return redirect()
            ->route('collections.show', $payment)
            ->with('success', __('donations.payment_rejected_success'));
    }

    /**
     * Reverse verified payment.
     */
    public function reverse(ReverseCollectionPaymentRequest $request, CollectionPayment $payment): RedirectResponse
    {
        $this->paymentService->reversePayment($payment, Auth::id(), $request->reversal_reason);

        return redirect()
            ->route('collections.show', $payment)
            ->with('success', __('donations.payment_reversed_success'));
    }
}
