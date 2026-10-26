<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePledgeRequest;
use App\Http\Requests\UpdatePledgeRequest;
use App\Models\Event;
use App\Models\Family;
use App\Models\Person;
use App\Models\Pledge;
use App\Services\PledgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PledgeController extends Controller implements HasMiddleware
{
    public function __construct(
        protected PledgeService $pledgeService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_pledges', only: ['index', 'show']),
            new Middleware('permission:manage_pledges', except: ['index', 'show']),
        ];
    }

    /**
     * Display a listing of pledges with server-side DataTables support.
     */
    public function index(Request $request): View|JsonResponse
    {
        $rawSearch = $request->input('search');
        $searchString = is_array($rawSearch) ? ($rawSearch['value'] ?? '') : (is_string($rawSearch) ? $rawSearch : '');
        $searchString = trim((string) $searchString);

        $filters = $request->only(['event_id', 'pledge_year', 'status', 'person_id', 'family_id']);
        if (! empty($searchString)) {
            $filters['search'] = $searchString;
        }

        if (($request->ajax() || $request->wantsJson()) && $request->has('draw')) {
            return $this->dataTableResponse($request, $filters);
        }

        $pledges = Pledge::query()
            ->with(['event', 'person', 'family', 'allocations.payment'])
            ->filter($filters)
            ->latest('pledged_on')
            ->paginate(15)
            ->withQueryString();

        $events = Event::orderByDesc('start_date')->get();
        $years = Pledge::distinct()->orderByDesc('pledge_year')->pluck('pledge_year')->toArray();
        if (! in_array((int) date('Y'), $years, true)) {
            $years[] = (int) date('Y');
            rsort($years);
        }

        return view('donations.pledges.index', compact('pledges', 'filters', 'events', 'years'));
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

        $query = Pledge::query()->with(['event', 'person', 'family', 'allocations.payment']);
        $totalRecords = Pledge::count();

        unset($filters['search']);
        $query->filter($filters);

        if (! empty($searchValue)) {
            $query->search($searchValue);
        }

        $filteredRecords = $query->count();

        $orderColumnIndex = $request->has('order.0.column') ? (int) $request->input('order.0.column') : null;
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columnMap = [
            0 => 'pledge_code',
            3 => 'pledged_amount',
            6 => 'status',
        ];

        if ($orderColumnIndex !== null && isset($columnMap[$orderColumnIndex])) {
            $query->orderBy($columnMap[$orderColumnIndex], $orderDir);
        } else {
            $query->latest('pledged_on');
        }

        $records = $query->skip($start)->take($length)->get();
        $canManage = $request->user()?->hasPermission('manage_pledges') ?? false;
        $canCollect = $request->user()?->hasPermission('record_collections') ?? false;

        $data = $records->map(function (Pledge $pledge) use ($canManage, $canCollect) {
            $codeHtml = '<span class="badge bg-light text-dark border font-monospace">'.e($pledge->pledge_code).'</span>'
                .'<div class="small text-muted mt-1">'.e($pledge->pledged_on->format('d M Y')).'</div>';

            $ownerHtml = '<div class="fw-bold text-dark">'.e($pledge->owner_name).'</div>';
            if ($pledge->person && $pledge->person->phone) {
                $ownerHtml .= '<small class="text-muted"><i class="bi bi-telephone me-1"></i>'.e($pledge->person->phone).'</small>';
            }

            $eventHtml = '<div class="fw-semibold text-dark">'.e($pledge->event?->name ?? '—').'</div>'
                .'<span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">'
                .e($pledge->pledge_year).'</span>';
            if ($pledge->purpose) {
                $eventHtml .= '<div class="small text-muted">'.e($pledge->purpose).'</div>';
            }

            $pledgedAmountHtml = '<div class="fw-bold text-dark">৳'.number_format((float) $pledge->pledged_amount, 2).'</div>';
            $paidAmountHtml = '<div class="text-success fw-semibold">৳'.number_format($pledge->verified_paid_amount, 2).'</div>';
            $outstandingHtml = '<div class="fw-bold '.($pledge->outstanding_amount > 0 ? 'text-danger' : 'text-muted').'">৳'.number_format($pledge->outstanding_amount, 2).'</div>';

            $statusBadge = match ($pledge->status) {
                Pledge::STATUS_PAID => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                Pledge::STATUS_PARTIAL => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                Pledge::STATUS_UNPAID => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                Pledge::STATUS_CANCELLED => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                default => 'bg-secondary bg-opacity-10 text-secondary',
            };

            $statusHtml = '<span class="badge '.$statusBadge.' px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>'.e(__('donations.status_'.$pledge->status)).'</span>';

            $actionsHtml = '<div class="btn-group btn-group-sm text-end">'
                .'<a href="'.route('pledges.show', $pledge).'" class="btn btn-outline-primary" title="'.e(__('common.view')).'"><i class="bi bi-eye"></i></a>';

            if ($canManage && $pledge->status !== Pledge::STATUS_CANCELLED && $pledge->status !== Pledge::STATUS_PAID) {
                $actionsHtml .= '<a href="'.route('pledges.edit', $pledge).'" class="btn btn-outline-secondary" title="'.e(__('common.edit')).'"><i class="bi bi-pencil"></i></a>';
            }

            if ($canCollect && $pledge->outstanding_amount > 0 && $pledge->status !== Pledge::STATUS_CANCELLED) {
                $actionsHtml .= '<a href="'.route('collections.create', ['pledge_id' => $pledge->id]).'" class="btn btn-outline-success" title="'.e(__('donations.collect_payment')).'"><i class="bi bi-cash"></i></a>';
            }

            $actionsHtml .= '</div>';

            return [
                $codeHtml,
                $ownerHtml,
                $eventHtml,
                $pledgedAmountHtml,
                $paidAmountHtml,
                $outstandingHtml,
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
     * Show form for creating a new pledge.
     */
    public function create(Request $request): View
    {
        $events = Event::whereIn('status', [Event::STATUS_ACTIVE, Event::STATUS_UPCOMING, Event::STATUS_DRAFT])
            ->orderBy('start_date')
            ->get();

        $selectedPerson = null;
        if ($request->has('person_id')) {
            $selectedPerson = Person::find($request->input('person_id'));
        }

        $selectedFamily = null;
        if ($request->has('family_id')) {
            $selectedFamily = Family::find($request->input('family_id'));
        }

        $selectedEventId = $request->input('event_id');

        return view('donations.pledges.create', compact('events', 'selectedPerson', 'selectedFamily', 'selectedEventId'));
    }

    /**
     * Store newly created pledge.
     */
    public function store(StorePledgeRequest $request): RedirectResponse
    {
        $pledge = $this->pledgeService->createPledge($request->validated(), Auth::id());

        return redirect()
            ->route('pledges.show', $pledge)
            ->with('success', __('donations.pledge_created_success'));
    }

    /**
     * Display pledge details and installment history.
     */
    public function show(Pledge $pledge): View
    {
        $pledge->load([
            'event.category',
            'person.family',
            'family',
            'creator',
            'allocations' => function ($q) {
                $q->with(['payment.receipt', 'payment.submitter', 'payment.verifier'])
                    ->latest();
            },
        ]);

        return view('donations.pledges.show', compact('pledge'));
    }

    /**
     * Show form for editing a pledge.
     */
    public function edit(Pledge $pledge): View|RedirectResponse
    {
        if ($pledge->status === Pledge::STATUS_CANCELLED) {
            return redirect()
                ->route('pledges.show', $pledge)
                ->with('error', __('donations.cannot_update_cancelled_pledge'));
        }

        return view('donations.pledges.edit', compact('pledge'));
    }

    /**
     * Update pledge details.
     */
    public function update(UpdatePledgeRequest $request, Pledge $pledge): RedirectResponse
    {
        $this->pledgeService->updatePledge($pledge, $request->validated(), Auth::id());

        return redirect()
            ->route('pledges.show', $pledge)
            ->with('success', __('donations.pledge_updated_success'));
    }

    /**
     * Cancel an uncollected pledge.
     */
    public function destroy(Request $request, Pledge $pledge): RedirectResponse
    {
        $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->pledgeService->cancelPledge($pledge, $request->cancellation_reason, Auth::id());

        return redirect()
            ->route('pledges.show', $pledge)
            ->with('success', __('donations.pledge_cancelled_success'));
    }
}
