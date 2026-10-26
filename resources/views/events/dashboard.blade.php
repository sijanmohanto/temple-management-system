@extends('layouts.app')

@section('title', __('events.dashboard'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('events.events_management') }}</h1>
            <p class="text-muted small mb-0">{{ __('events.dashboard') }} &bull; {{ __('events.current_year_events', ['year' => $currentYear]) }}: <strong>{{ $metrics['current_year_events'] }}</strong></p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('events.index') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-list-ul me-1"></i> {{ __('events.all_events') }}
            </a>
            @if(Auth::user()->hasPermission('manage_events'))
                <a href="{{ route('events.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                    <i class="bi bi-plus-circle-fill me-1"></i> {{ __('events.add_event') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('events.active_events') }}</div>
                <div class="fs-3 fw-bold text-success mt-1">{{ $metrics['active'] }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-circle-fill text-success me-1" style="font-size: 0.5rem;"></i>{{ __('events.status_active') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('events.upcoming_events') }}</div>
                <div class="fs-3 fw-bold text-info mt-1">{{ $metrics['upcoming'] }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-circle-fill text-info me-1" style="font-size: 0.5rem;"></i>{{ __('events.status_upcoming') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('events.draft_events') }}</div>
                <div class="fs-3 fw-bold text-secondary mt-1">{{ $metrics['draft'] }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-circle-fill text-secondary me-1" style="font-size: 0.5rem;"></i>{{ __('events.status_draft') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('events.closing_events') }}</div>
                <div class="fs-3 fw-bold text-warning mt-1">{{ $metrics['closing'] }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-circle-fill text-warning me-1" style="font-size: 0.5rem;"></i>{{ __('events.status_closing') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('events.closed_events') }}</div>
                <div class="fs-3 fw-bold text-dark mt-1">{{ $metrics['closed'] }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-lock-fill text-dark me-1" style="font-size: 0.7rem;"></i>{{ __('events.status_closed') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('events.categories') }}</div>
                <div class="fs-3 fw-bold text-primary mt-1">{{ $metrics['categories'] }}</div>
                <div class="small text-muted mt-auto pt-2">
                    <a href="{{ route('events.categories.index') }}" class="text-decoration-none text-primary"><i class="bi bi-tags me-1"></i>{{ __('common.manage') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Active & Upcoming Events List -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar-check me-2 text-primary"></i>{{ __('events.active_events') }} &amp; {{ __('events.upcoming_events') }}</h5>
                    <a href="{{ route('events.index') }}" class="btn btn-sm btn-link text-decoration-none">{{ __('common.view_all') }}</a>
                </div>
                <div class="card-body p-4">
                    @if($activeEvents->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <p class="mb-0">{{ __('events.no_events_found') }}</p>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-3">
                            @foreach($activeEvents as $evt)
                                <div class="p-3 border rounded-3 bg-light bg-opacity-50 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-light text-dark border font-monospace">{{ $evt->event_code }}</span>
                                            <span class="badge {{ $evt->status === 'active' ? 'bg-success' : 'bg-info' }} bg-opacity-10 {{ $evt->status === 'active' ? 'text-success' : 'text-info' }} border border-opacity-25" style="font-size: 0.72rem;">
                                                {{ __('events.status_'.$evt->status) }}
                                            </span>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">
                                                {{ $evt->category?->name }}
                                            </span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">{{ $evt->name }}</h6>
                                        <div class="small text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>{{ $evt->start_date->format('d M Y') }} - {{ $evt->end_date->format('d M Y') }}
                                            @if($evt->venue)
                                                &bull; <i class="bi bi-geo-alt me-1"></i>{{ $evt->venue }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-md-end">
                                        <a href="{{ route('events.show', $evt) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-eye me-1"></i> {{ __('common.view') }}
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Lifecycle Activity Feed -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-warning-emphasis"></i>{{ __('events.recent_event_activity') }}</h5>
                </div>
                <div class="card-body p-4">
                    @if($recentLogs->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-journal-text fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <p class="mb-0">{{ __('events.no_logs_found') }}</p>
                        </div>
                    @else
                        <div class="timeline position-relative">
                            @foreach($recentLogs as $log)
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                                        <span class="badge bg-light text-dark border fw-bold" style="font-size: 0.72rem;">
                                            {{ __('events.action_'.$log->action) }}
                                        </span>
                                        <small class="text-muted" style="font-size: 0.72rem;">{{ $log->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="fw-semibold small text-dark">
                                        @if($log->event)
                                            <a href="{{ route('events.show', $log->event) }}" class="text-decoration-none text-primary">
                                                {{ $log->event->name }} ({{ $log->event->event_code }})
                                            </a>
                                        @else
                                            <span>{{ __('events.event') }}</span>
                                        @endif
                                    </div>
                                    @if($log->reason)
                                        <div class="small text-muted fst-italic mt-1">&ldquo;{{ Str::limit($log->reason, 80) }}&rdquo;</div>
                                    @endif
                                    <div class="small text-muted mt-1" style="font-size: 0.7rem;">
                                        <i class="bi bi-person me-1"></i>{{ $log->user?->name ?? 'System' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
