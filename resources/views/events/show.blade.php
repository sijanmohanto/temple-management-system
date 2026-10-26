@extends('layouts.app')

@section('title', $event->name . ' (' . $event->event_code . ')')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('events.index') }}" class="text-decoration-none">{{ __('events.events') }}</a></li>
                    <li class="breadcrumb-item active">{{ $event->event_code }}</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2">{{ $event->event_code }}</span>
                @php
                    $badgeClass = match ($event->status) {
                        'draft' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                        'upcoming' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                        'active' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                        'closing' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                        'closed' => 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25',
                        default => 'bg-secondary bg-opacity-10 text-secondary',
                    };
                @endphp
                <span class="badge {{ $badgeClass }} px-3 py-2 fs-6">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>{{ __('events.status_'.$event->status) }}
                </span>
                @if($event->isClosed())
                    @if($event->isLocked())
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 fs-6">
                            <i class="bi bi-lock-fill me-1"></i>{{ __('events.mode_close_and_lock') }}
                        </span>
                    @elseif($event->allowsAuthorizedAdjustments())
                        <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 px-3 py-2 fs-6">
                            <i class="bi bi-pencil-square me-1"></i>{{ __('events.mode_close_with_authorized_adjustments') }}
                        </span>
                    @endif
                @endif
                <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2 fs-6">
                    {{ $event->category?->name }}
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fs-6">
                    {{ $event->event_year }}
                </span>
            </div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ $event->name }}</h1>
            @if($event->name_bn && app()->getLocale() !== 'bn')
                <p class="text-muted mb-0">{{ $event->name_bn }}</p>
            @endif
        </div>

        <!-- Action Toolbar -->
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>

            @if(! $event->isClosed())
                @if(Auth::user()->hasPermission('manage_events'))
                    <a href="{{ route('events.edit', $event) }}" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-pencil me-1"></i> {{ __('common.edit') }}
                    </a>
                @endif

                @if(Auth::user()->hasPermission('close_events'))
                    <button type="button" class="btn btn-warning rounded-pill px-3 text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#closeEventModal">
                        <i class="bi bi-door-closed me-1"></i> {{ __('events.close_event') }}
                    </button>
                @endif
            @else
                @if(Auth::user()->hasPermission('reopen_events'))
                    <button type="button" class="btn btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reopenEventModal">
                        <i class="bi bi-arrow-repeat me-1"></i> {{ __('events.reopen_event') }}
                    </button>
                @endif

                @if($event->allowsAuthorizedAdjustments() && Auth::user()->hasPermission('adjust_closed_events'))
                    <button type="button" class="btn btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#adjustEventModal">
                        <i class="bi bi-pencil-square me-1"></i> {{ __('events.post_close_adjust') }}
                    </button>
                @endif
            @endif

            @if($event->status === 'draft' && Auth::user()->hasPermission('manage_events'))
                <form action="{{ route('events.destroy', $event) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('events.delete_draft_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-3">
                        <i class="bi bi-trash me-1"></i> {{ __('common.delete') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Closed Status Information Banner -->
    @if($event->isClosed())
        <div class="alert {{ $event->isLocked() ? 'alert-danger' : 'alert-warning' }} border-0 shadow-sm rounded-4 mb-4 p-4">
            <div class="d-flex align-items-start gap-3">
                <i class="bi {{ $event->isLocked() ? 'bi-shield-lock-fill text-danger' : 'bi-exclamation-triangle-fill text-warning' }} fs-3 mt-1"></i>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1">
                        @if($event->isLocked())
                            {{ __('events.mode_close_and_lock') }} — {{ __('events.field_locked_notice') }}
                        @else
                            {{ __('events.mode_close_with_authorized_adjustments') }}
                        @endif
                    </h5>
                    <p class="mb-2">
                        @if($event->isLocked())
                            {{ __('events.mode_close_and_lock_desc') }}
                        @else
                            {{ __('events.mode_close_with_authorized_adjustments_desc') }}
                        @endif
                    </p>
                    <div class="small">
                        <strong>{{ __('events.closed_by') }}:</strong> {{ $event->closedBy?->name ?? '—' }} &bull;
                        <strong>{{ __('events.closed_at') }}:</strong> {{ $event->closed_at?->format('d M Y, h:i A') ?? '—' }}
                        @if($event->closing_reason)
                            <div class="mt-1">
                                <strong>{{ __('events.closing_reason') }}:</strong> &ldquo;{{ $event->closing_reason }}&rdquo;
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- Event Overview Cards -->
        <div class="col-lg-4">
            <!-- Schedule & Location -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-calendar3 me-2 text-primary"></i>{{ __('events.event_dates') }}</h6>
                    <div class="mb-3">
                        <label class="small text-muted mb-0">{{ __('events.start_date') }}</label>
                        <div class="fw-semibold text-dark">{{ $event->start_date->format('d F Y') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted mb-0">{{ __('events.end_date') }}</label>
                        <div class="fw-semibold text-dark">{{ $event->end_date->format('d F Y') }}</div>
                    </div>
                    <div>
                        <label class="small text-muted mb-0">{{ __('events.venue') }}</label>
                        <div class="fw-semibold text-dark">{{ $event->venue ?: '—' }}</div>
                    </div>
                </div>
            </div>

            <!-- Collection Window -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-cash-stack me-2 text-primary"></i>{{ __('events.collection_period') }}</h6>
                    @if($event->collection_start_date && $event->collection_end_date)
                        <div class="mb-3">
                            <label class="small text-muted mb-0">{{ __('events.collection_start_date') }}</label>
                            <div class="fw-semibold text-dark">{{ $event->collection_start_date->format('d F Y') }}</div>
                        </div>
                        <div>
                            <label class="small text-muted mb-0">{{ __('events.collection_end_date') }}</label>
                            <div class="fw-semibold text-dark">{{ $event->collection_end_date->format('d F Y') }}</div>
                        </div>
                    @else
                        <div class="text-muted small">{{ __('common.none') }}</div>
                    @endif
                </div>
            </div>

            <!-- Administrative Details -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check me-2 text-primary"></i>{{ __('common.details') }}</h6>
                    <div class="mb-2 small">
                        <span class="text-muted">{{ __('events.created_by') }}:</span>
                        <span class="fw-semibold text-dark ms-1">{{ $event->creator?->name ?? '—' }}</span>
                    </div>
                    <div class="mb-2 small">
                        <span class="text-muted">{{ __('events.created_at') }}:</span>
                        <span class="fw-semibold text-dark ms-1">{{ $event->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @if($event->updated_by)
                        <div class="mb-2 small">
                            <span class="text-muted">{{ __('events.last_updated') }}:</span>
                            <span class="fw-semibold text-dark ms-1">{{ $event->updated_at->format('d M Y, h:i A') }}</span>
                        </div>
                    @endif
                    @if($event->reopened_by)
                        <div class="mt-3 pt-2 border-top small">
                            <div class="text-success fw-bold"><i class="bi bi-arrow-repeat me-1"></i>{{ __('events.action_reopened') }}</div>
                            <div><span class="text-muted">{{ __('events.reopened_by') }}:</span> {{ $event->reopenedBy?->name ?? '—' }}</div>
                            <div><span class="text-muted">{{ __('events.reopened_at') }}:</span> {{ $event->reopened_at?->format('d M Y, h:i A') }}</div>
                            @if($event->reopen_reason)
                                <div class="fst-italic text-muted mt-1">&ldquo;{{ $event->reopen_reason }}&rdquo;</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Descriptions & Event Details -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-card-text me-2 text-primary"></i>{{ __('events.description') }}</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted small text-uppercase mb-2">{{ __('events.description_en') }}</h6>
                            <div class="p-3 bg-light rounded-3 text-dark" style="min-height: 120px; white-space: pre-line;">{{ $event->description_en ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted small text-uppercase mb-2">{{ __('events.description_bn') }}</h6>
                            <div class="p-3 bg-light rounded-3 text-dark" style="min-height: 120px; white-space: pre-line;">{{ $event->description_bn ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lifecycle Transitions & Actions Toolbar (when not closed) -->
            @if(! $event->isClosed() && Auth::user()->hasPermission('manage_events'))
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-arrow-left-right me-2 text-primary"></i>{{ __('events.transition_status') }}</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @if($event->status === 'draft')
                                <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_status" value="upcoming">
                                    <button type="submit" class="btn btn-outline-info rounded-pill px-3">
                                        <i class="bi bi-arrow-right-circle me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_upcoming')]) }}
                                    </button>
                                </form>
                                <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_status" value="active">
                                    <button type="submit" class="btn btn-outline-success rounded-pill px-3">
                                        <i class="bi bi-play-circle me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_active')]) }}
                                    </button>
                                </form>
                            @elseif($event->status === 'upcoming')
                                <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_status" value="active">
                                    <button type="submit" class="btn btn-success rounded-pill px-3">
                                        <i class="bi bi-play-circle-fill me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_active')]) }}
                                    </button>
                                </form>
                                <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_status" value="draft">
                                    <button type="submit" class="btn btn-outline-secondary rounded-pill px-3">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_draft')]) }}
                                    </button>
                                </form>
                            @elseif($event->status === 'active')
                                <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_status" value="closing">
                                    <button type="submit" class="btn btn-outline-warning rounded-pill px-3 text-dark">
                                        <i class="bi bi-hourglass-split me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_closing')]) }}
                                    </button>
                                </form>
                            @elseif($event->status === 'closing')
                                <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_status" value="active">
                                    <button type="submit" class="btn btn-outline-success rounded-pill px-3">
                                        <i class="bi bi-play-circle me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_active')]) }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Lifecycle & Audit Trail Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-warning-emphasis"></i>{{ __('events.lifecycle_history') }}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="eventLifecycleLogsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ __('events.lifecycle_action') }}</th>
                                    <th>{{ __('events.lifecycle_transition') }}</th>
                                    <th>{{ __('events.lifecycle_reason') }}</th>
                                    <th>{{ __('events.lifecycle_user') }}</th>
                                    <th class="text-end pe-3">{{ __('events.lifecycle_date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($event->lifecycleLogs as $log)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark border fw-bold" style="font-size: 0.72rem;">
                                                {{ __('events.action_'.$log->action) }}
                                            </span>
                                            @if($log->closing_mode)
                                                <div class="small mt-1">
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.65rem;">
                                                        {{ $log->closing_mode === 'close_and_lock' ? __('events.mode_close_and_lock') : __('events.mode_close_with_authorized_adjustments') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->from_status || $log->to_status)
                                                <div class="small">
                                                    <span class="badge bg-light text-secondary border">{{ $log->from_status ? __('events.status_'.$log->from_status) : '—' }}</span>
                                                    <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                                    <span class="badge bg-light text-dark border">{{ $log->to_status ? __('events.status_'.$log->to_status) : '—' }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif

                                            @if(! empty($log->changes))
                                                <div class="mt-2">
                                                    <a class="small text-primary text-decoration-none" data-bs-toggle="collapse" href="#logChanges{{ $log->id }}" role="button">
                                                        <i class="bi bi-code-slash me-1"></i>{{ __('events.lifecycle_changes') }} ({{ count($log->changes) }})
                                                    </a>
                                                    <div class="collapse mt-1" id="logChanges{{ $log->id }}">
                                                        <div class="p-2 bg-light border rounded small font-monospace" style="font-size: 0.72rem;">
                                                            @foreach($log->changes as $field => $diff)
                                                                <div><strong>{{ $field }}:</strong> {{ is_array($diff) ? json_encode($diff) : $diff }}</div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small text-dark">{{ $log->reason ?: '—' }}</div>
                                            @if($log->ip_address)
                                                <div class="text-muted" style="font-size: 0.68rem;"><i class="bi bi-globe me-1"></i>{{ $log->ip_address }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-dark">{{ $log->user?->name ?? 'System' }}</div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="small text-dark">{{ $log->created_at->format('d M Y') }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ $log->created_at->format('h:i A') }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            {{ __('events.no_logs_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Close Event Modal -->
@if(! $event->isClosed() && Auth::user()->hasPermission('close_events'))
    <div class="modal fade" id="closeEventModal" tabindex="-1" aria-labelledby="closeEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('events.close', $event) }}" method="POST" id="closeEventForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="closeEventModalLabel">
                            <i class="bi bi-door-closed me-2 text-warning"></i>{{ __('events.close_event_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 rounded-3 mb-4">
                            <i class="bi bi-info-circle me-1"></i>{{ __('events.close_event_warning') }}
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold required">{{ __('events.closing_mode') }}</label>
                            
                            <div class="form-check p-3 border rounded-3 mb-3 bg-light">
                                <input class="form-check-input ms-0 me-3" type="radio" name="closing_mode" id="modeLock" value="close_and_lock" checked>
                                <label class="form-check-label" for="modeLock">
                                    <div class="fw-bold text-danger"><i class="bi bi-lock-fill me-1"></i>{{ __('events.mode_close_and_lock') }}</div>
                                    <div class="small text-muted mt-1">{{ __('events.mode_close_and_lock_desc') }}</div>
                                </label>
                            </div>

                            <div class="form-check p-3 border rounded-3 bg-light">
                                <input class="form-check-input ms-0 me-3" type="radio" name="closing_mode" id="modeAdjust" value="close_with_authorized_adjustments">
                                <label class="form-check-label" for="modeAdjust">
                                    <div class="fw-bold text-warning-emphasis"><i class="bi bi-pencil-square me-1"></i>{{ __('events.mode_close_with_authorized_adjustments') }}</div>
                                    <div class="small text-muted mt-1">{{ __('events.mode_close_with_authorized_adjustments_desc') }}</div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('events.closing_reason') }}</label>
                            <textarea name="closing_reason" rows="3" class="form-control" placeholder="Provide justification for closing this event..." required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmCloseBtn" class="btn btn-warning rounded-pill px-4 text-dark fw-bold">
                            <i class="bi bi-check-circle me-1"></i>{{ __('events.close_event') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Modal 2: Reopen Event Modal -->
@if($event->isClosed() && Auth::user()->hasPermission('reopen_events'))
    <div class="modal fade" id="reopenEventModal" tabindex="-1" aria-labelledby="reopenEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('events.reopen', $event) }}" method="POST" id="reopenEventForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="reopenEventModalLabel">
                            <i class="bi bi-arrow-repeat me-2 text-success"></i>{{ __('events.reopen_event_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 rounded-3 mb-4">
                            <i class="bi bi-shield-check me-1"></i>{{ __('events.reopen_event_warning') }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('events.target_status') }}</label>
                            <select name="target_status" class="form-select" required>
                                <option value="active">{{ __('events.status_active') }}</option>
                                <option value="upcoming">{{ __('events.status_upcoming') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('events.reopen_reason') }}</label>
                            <textarea name="reopen_reason" rows="3" class="form-control" placeholder="Provide justification for reopening this closed event..." required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmReopenBtn" class="btn btn-success rounded-pill px-4">
                            <i class="bi bi-check2-circle me-1"></i>{{ __('events.reopen_event') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Modal 3: Post-Closing Adjustment Modal (Mode B only) -->
@if($event->allowsAuthorizedAdjustments() && Auth::user()->hasPermission('adjust_closed_events'))
    <div class="modal fade" id="adjustEventModal" tabindex="-1" aria-labelledby="adjustEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('events.adjust', $event) }}" method="POST" id="adjustEventForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="adjustEventModalLabel">
                            <i class="bi bi-pencil-square me-2 text-primary"></i>{{ __('events.post_close_adjust_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-secondary border-0 rounded-3 mb-4">
                            <i class="bi bi-info-circle me-1"></i>{{ __('events.post_close_adjust_notice') }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('events.venue') }}</label>
                            <input type="text" name="venue" class="form-control" value="{{ old('venue', $event->venue) }}">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('events.description_en') }}</label>
                                <textarea name="description_en" rows="4" class="form-control">{{ old('description_en', $event->description_en) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('events.description_bn') }}</label>
                                <textarea name="description_bn" rows="4" class="form-control">{{ old('description_bn', $event->description_bn) }}</textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('events.adjustment_reason') }}</label>
                            <textarea name="adjustment_reason" rows="3" class="form-control" placeholder="Provide justification for this post-closing modification..." required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmAdjustBtn" class="btn btn-primary rounded-pill px-4" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                            <i class="bi bi-check2-circle me-1"></i>{{ __('common.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        TempleUI.initDataTable('#eventLifecycleLogsTable', {
            pageLength: 10,
            searching: false,
            paging: true,
            info: true,
            order: [[4, 'desc']]
        });

        $('#closeEventForm').on('submit', function() {
            TempleUI.button.loading('#confirmCloseBtn');
        });

        $('#reopenEventForm').on('submit', function() {
            TempleUI.button.loading('#confirmReopenBtn');
        });

        $('#adjustEventForm').on('submit', function() {
            TempleUI.button.loading('#confirmAdjustBtn');
        });
    });
</script>
@endpush
