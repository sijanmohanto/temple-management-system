<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustClosedEventRequest;
use App\Http\Requests\CloseEventRequest;
use App\Http\Requests\ReopenEventRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventLifecycleLog;
use App\Services\AuditLogger;
use App\Services\EventLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EventController extends Controller implements HasMiddleware
{
    public function __construct(
        protected EventLifecycleService $lifecycleService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_events', only: ['index', 'show']),
            new Middleware('permission:manage_events', only: ['create', 'store', 'edit', 'update', 'destroy']),
            new Middleware('permission:close_events', only: ['close']),
            new Middleware('permission:reopen_events', only: ['reopen']),
            new Middleware('permission:adjust_closed_events', only: ['adjust']),
        ];
    }

    /**
     * Display a listing of events with server-side DataTables support.
     */
    public function index(Request $request): View|JsonResponse
    {
        $rawSearch = $request->input('search');
        $searchString = is_array($rawSearch) ? ($rawSearch['value'] ?? '') : (is_string($rawSearch) ? $rawSearch : '');
        $searchString = trim((string) $searchString);

        $filters = $request->only(['event_year', 'event_category_id', 'status']);
        if (! empty($searchString)) {
            $filters['search'] = $searchString;
        }

        if (($request->ajax() || $request->wantsJson()) && $request->has('draw')) {
            return $this->dataTableResponse($request, $filters);
        }

        $events = Event::query()
            ->with(['category'])
            ->filter($filters)
            ->latest('start_date')
            ->paginate(15)
            ->withQueryString();

        $categories = EventCategory::orderBy('name_en')->get();
        $years = Event::distinct()->orderByDesc('event_year')->pluck('event_year')->toArray();
        if (! in_array((int) date('Y'), $years, true)) {
            $years[] = (int) date('Y');
            rsort($years);
        }

        return view('events.index', compact('events', 'filters', 'categories', 'years'));
    }

    /**
     * Server-side DataTables response.
     */
    protected function dataTableResponse(Request $request, array $filters): JsonResponse
    {
        $draw = $request->integer('draw');
        $start = $request->integer('start', 0);
        $length = $request->integer('length', 15);
        $searchValue = trim($request->input('search.value', ''));

        $query = Event::query()->with(['category']);
        $totalRecords = Event::count();

        unset($filters['search']);
        $query->filter($filters);

        if (! empty($searchValue)) {
            $query->search($searchValue);
        }

        $filteredRecords = $query->count();

        $orderColumnIndex = $request->has('order.0.column') ? (int) $request->input('order.0.column') : null;
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columnMap = [
            0 => 'event_code',
            1 => 'name_en',
            2 => 'start_date',
            3 => 'collection_start_date',
            4 => 'status',
        ];

        if ($orderColumnIndex !== null && isset($columnMap[$orderColumnIndex])) {
            $query->orderBy($columnMap[$orderColumnIndex], $orderDir);
        } else {
            $query->latest('start_date');
        }

        $records = $query->skip($start)->take($length)->get();
        $canManage = $request->user()?->hasPermission('manage_events') ?? false;

        $data = $records->map(function (Event $event) use ($canManage) {
            $codeHtml = '<span class="badge bg-light text-dark border font-monospace">'.e($event->event_code).'</span>';

            $nameHtml = '<div class="fw-bold text-dark">'.e($event->name).'</div>';
            if ($event->name_bn && app()->getLocale() !== 'bn') {
                $nameHtml .= '<small class="text-muted">'.e($event->name_bn).'</small>';
            }
            $catName = $event->category?->name ?? '—';
            $nameHtml .= '<div><span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">'.e($catName).'</span>';
            $nameHtml .= ' <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.72rem;">'.e($event->event_year).'</span></div>';

            $datesHtml = '<div><i class="bi bi-calendar3 me-1 text-muted"></i>'.e($event->start_date->format('d M Y')).' - '.e($event->end_date->format('d M Y')).'</div>';
            if ($event->venue) {
                $datesHtml .= '<div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>'.e($event->venue).'</div>';
            }

            if ($event->collection_start_date && $event->collection_end_date) {
                $collHtml = '<div class="small"><i class="bi bi-cash-stack me-1 text-muted"></i>'.e($event->collection_start_date->format('d M Y')).' - '.e($event->collection_end_date->format('d M Y')).'</div>';
            } else {
                $collHtml = '<span class="text-muted small">—</span>';
            }

            $statusBadgeClass = match ($event->status) {
                Event::STATUS_DRAFT => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                Event::STATUS_UPCOMING => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                Event::STATUS_ACTIVE => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                Event::STATUS_CLOSING => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                Event::STATUS_CLOSED => 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25',
                default => 'bg-secondary bg-opacity-10 text-secondary',
            };

            $statusHtml = '<span class="badge '.$statusBadgeClass.' px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>'.e(__('events.status_'.$event->status)).'</span>';
            if ($event->isClosed()) {
                if ($event->isLocked()) {
                    $statusHtml .= '<div class="mt-1"><span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.68rem;"><i class="bi bi-lock-fill me-1"></i>'.e(__('events.mode_close_and_lock')).'</span></div>';
                } elseif ($event->allowsAuthorizedAdjustments()) {
                    $statusHtml .= '<div class="mt-1"><span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25" style="font-size: 0.68rem;"><i class="bi bi-pencil-square me-1"></i>'.e(__('events.mode_close_with_authorized_adjustments')).'</span></div>';
                }
            }

            $actionsHtml = '<div class="btn-group btn-group-sm text-end">'
                .'<a href="'.route('events.show', $event).'" class="btn btn-outline-primary" title="'.e(__('common.view')).'"><i class="bi bi-eye"></i></a>';

            if ($canManage && ! $event->isClosed()) {
                $actionsHtml .= '<a href="'.route('events.edit', $event).'" class="btn btn-outline-secondary" title="'.e(__('common.edit')).'"><i class="bi bi-pencil"></i></a>';
            }

            $actionsHtml .= '</div>';

            return [
                $codeHtml,
                $nameHtml,
                $datesHtml,
                $collHtml,
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
     * Show the form for creating a new event.
     */
    public function create(): View
    {
        $categories = EventCategory::active()->orderBy('name_en')->get();
        $currentYear = (int) date('Y');

        return view('events.create', compact('categories', 'currentYear'));
    }

    /**
     * Store a newly created event.
     */
    public function store(StoreEventRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $status = $validated['status'] ?? Event::STATUS_DRAFT;

        $event = DB::transaction(function () use ($validated, $status, $request) {
            $year = (int) $validated['event_year'];
            $code = Event::generateEventCode($year);

            $eventData = array_merge($validated, [
                'event_code' => $code,
                'status' => $status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $event = Event::create($eventData);

            EventLifecycleLog::create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'from_status' => null,
                'to_status' => $status,
                'reason' => 'Initial event creation',
                'ip_address' => $request->ip(),
            ]);

            AuditLogger::log(
                'event_created',
                "Created new event {$event->name_en} ({$event->event_code})",
                $event,
                $event->toArray()
            );

            return $event;
        });

        return redirect()
            ->route('events.show', $event)
            ->with('success', __('events.event_created_success'));
    }

    /**
     * Display the specified event details, summary, and lifecycle logs.
     */
    public function show(Event $event): View
    {
        $event->load([
            'category',
            'creator',
            'updater',
            'closedBy',
            'reopenedBy',
            'lifecycleLogs.user',
        ]);

        return view('events.show', compact('event'));
    }

    /**
     * Show the form for editing the event.
     */
    public function edit(Event $event): View|RedirectResponse
    {
        if ($event->isClosed()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', __('events.cannot_edit_closed_event'));
        }

        $categories = EventCategory::active()->orderBy('name_en')->get();

        return view('events.edit', compact('event', 'categories'));
    }

    /**
     * Update the event details.
     */
    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        if ($event->isClosed()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', __('events.cannot_edit_closed_event'));
        }

        $validated = $request->validated();
        $targetStatus = $validated['status'];
        unset($validated['status']);

        DB::transaction(function () use ($event, $validated, $targetStatus, $request) {
            $oldData = $event->toArray();

            $validated['updated_by'] = Auth::id();
            $event->update($validated);

            if ($event->status !== $targetStatus) {
                $this->lifecycleService->transitionStatus($event, $targetStatus, $request->input('status_change_reason'));
            }

            AuditLogger::log(
                'event_updated',
                "Updated event details for {$event->name_en} ({$event->event_code})",
                $event,
                [
                    'old' => $oldData,
                    'new' => $event->fresh()->toArray(),
                ]
            );
        });

        return redirect()
            ->route('events.show', $event)
            ->with('success', __('events.event_updated_success'));
    }

    /**
     * Transition event status via simple lifecycle transition button.
     */
    public function transition(Request $request, Event $event): RedirectResponse
    {
        $request->validate([
            'target_status' => ['required', 'string', 'in:'.Event::STATUS_DRAFT.','.Event::STATUS_UPCOMING.','.Event::STATUS_ACTIVE.','.Event::STATUS_CLOSING],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($event->isClosed()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', __('events.cannot_edit_closed_event'));
        }

        $this->lifecycleService->transitionStatus($event, $request->target_status, $request->reason);

        return redirect()
            ->route('events.show', $event)
            ->with('success', __('events.status_transition_success'));
    }

    /**
     * Close an event with specified closing mode (Mode A or Mode B).
     */
    public function close(CloseEventRequest $request, Event $event): RedirectResponse
    {
        $this->lifecycleService->closeEvent($event, $request->closing_mode, $request->closing_reason);

        return redirect()
            ->route('events.show', $event)
            ->with('success', __('events.event_closed_success'));
    }

    /**
     * Reopen a closed event.
     */
    public function reopen(ReopenEventRequest $request, Event $event): RedirectResponse
    {
        $this->lifecycleService->reopenEvent($event, $request->target_status, $request->reopen_reason);

        return redirect()
            ->route('events.show', $event)
            ->with('success', __('events.event_reopened_success'));
    }

    /**
     * Adjust metadata of a closed event (Mode B only).
     */
    public function adjust(AdjustClosedEventRequest $request, Event $event): RedirectResponse
    {
        $attributes = $request->only(['description_en', 'description_bn', 'venue']);
        $this->lifecycleService->adjustClosedEvent($event, $attributes, $request->adjustment_reason);

        return redirect()
            ->route('events.show', $event)
            ->with('success', __('events.adjustment_saved_success'));
    }

    /**
     * Remove the specified draft event.
     */
    public function destroy(Event $event): RedirectResponse
    {
        if ($event->status !== Event::STATUS_DRAFT) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', __('events.cannot_delete_non_draft_event'));
        }

        $code = $event->event_code;
        $name = $event->name_en;

        DB::transaction(function () use ($event, $code, $name) {
            $event->lifecycleLogs()->delete();
            $event->delete();

            AuditLogger::log(
                'event_deleted',
                "Deleted draft event {$name} ({$code})",
                null,
                ['event_code' => $code, 'name_en' => $name]
            );
        });

        return redirect()
            ->route('events.index')
            ->with('success', __('events.event_deleted_success'));
    }
}
