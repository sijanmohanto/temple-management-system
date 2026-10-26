@extends('layouts.app')

@section('title', __('events.all_events'))

@section('content')
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('events.events_management') }}</h1>
            <p class="text-muted small mb-0">{{ __('events.all_events') }} &bull; {{ $events->total() }} {{ __('common.details') }}</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('events.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer me-1"></i> {{ __('events.dashboard') }}
            </a>
            @if(Auth::user()->hasPermission('manage_events'))
                <a href="{{ route('events.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                    <i class="bi bi-plus-circle-fill me-1"></i> {{ __('events.add_event') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('events.index') }}" class="row g-3" id="eventsFilterForm">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('events.filter_by_year') }}</label>
                    <select name="event_year" id="filterYear" class="form-select select2" data-placeholder="{{ __('events.all_years') }}">
                        <option value="">{{ __('events.all_years') }}</option>
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ ($filters['event_year'] ?? '') == $yr ? 'selected' : '' }}>
                                {{ $yr }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('events.filter_by_category') }}</label>
                    <select name="event_category_id" id="filterCategory" class="form-select select2" data-placeholder="{{ __('events.all_categories') }}">
                        <option value="">{{ __('events.all_categories') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ ($filters['event_category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('events.filter_by_status') }}</label>
                    <select name="status" id="filterStatus" class="form-select">
                        <option value="">{{ __('events.all_statuses') }}</option>
                        <option value="draft" {{ ($filters['status'] ?? '') === 'draft' ? 'selected' : '' }}>{{ __('events.status_draft') }}</option>
                        <option value="upcoming" {{ ($filters['status'] ?? '') === 'upcoming' ? 'selected' : '' }}>{{ __('events.status_upcoming') }}</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>{{ __('events.status_active') }}</option>
                        <option value="closing" {{ ($filters['status'] ?? '') === 'closing' ? 'selected' : '' }}>{{ __('events.status_closing') }}</option>
                        <option value="closed" {{ ($filters['status'] ?? '') === 'closed' ? 'selected' : '' }}>{{ __('events.status_closed') }}</option>
                    </select>
                </div>

                <div class="col-md-12 col-lg-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">
                        <i class="bi bi-funnel me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('events.index') }}" class="btn btn-outline-secondary rounded-pill" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Events Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="eventsDataTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('events.event_code') }}</th>
                            <th>{{ __('events.name') }}</th>
                            <th>{{ __('events.event_dates') }}</th>
                            <th>{{ __('events.collection_period') }}</th>
                            <th>{{ __('events.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $event->event_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $event->name }}</div>
                                    @if($event->name_bn && app()->getLocale() !== 'bn')
                                        <small class="text-muted">{{ $event->name_bn }}</small>
                                    @endif
                                    <div>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">
                                            {{ $event->category?->name ?? '—' }}
                                        </span>
                                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.72rem;">
                                            {{ $event->event_year }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div><i class="bi bi-calendar3 me-1 text-muted"></i>{{ $event->start_date->format('d M Y') }} - {{ $event->end_date->format('d M Y') }}</div>
                                    @if($event->venue)
                                        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $event->venue }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($event->collection_start_date && $event->collection_end_date)
                                        <div class="small"><i class="bi bi-cash-stack me-1 text-muted"></i>{{ $event->collection_start_date->format('d M Y') }} - {{ $event->collection_end_date->format('d M Y') }}</div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
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
                                    <span class="badge {{ $badgeClass }} px-2 py-1">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>{{ __('events.status_'.$event->status) }}
                                    </span>
                                    @if($event->isClosed())
                                        @if($event->isLocked())
                                            <div class="mt-1">
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.68rem;">
                                                    <i class="bi bi-lock-fill me-1"></i>{{ __('events.mode_close_and_lock') }}
                                                </span>
                                            </div>
                                        @elseif($event->allowsAuthorizedAdjustments())
                                            <div class="mt-1">
                                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25" style="font-size: 0.68rem;">
                                                    <i class="bi bi-pencil-square me-1"></i>{{ __('events.mode_close_with_authorized_adjustments') }}
                                                </span>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('events.show', $event) }}" class="btn btn-outline-primary" title="{{ __('common.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if(Auth::user()->hasPermission('manage_events') && ! $event->isClosed())
                                            <a href="{{ route('events.edit', $event) }}" class="btn btn-outline-secondary" title="{{ __('common.edit') }}">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="eventsNativePagination" class="mt-3">
                @if($events->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $events->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var eventsTable = TempleUI.initDataTable('#eventsDataTable', {
            serverSide: true,
            processing: true,
            deferLoading: {{ $events->total() }},
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
            ajax: {
                url: "{{ route('events.index') }}",
                data: function(d) {
                    d.event_year = $('#filterYear').val();
                    d.event_category_id = $('#filterCategory').val();
                    d.status = $('#filterStatus').val();
                }
            },
            columns: [
                { data: 0, name: 'event_code' },
                { data: 1, name: 'name_en' },
                { data: 2, name: 'start_date' },
                { data: 3, name: 'collection_start_date', orderable: false },
                { data: 4, name: 'status' },
                { data: 5, name: 'actions', orderable: false, className: 'text-end pe-3' }
            ]
        });

        $('#eventsNativePagination').hide();

        $('#eventsFilterForm').on('submit', function(e) {
            e.preventDefault();
            eventsTable.draw();
        });

        $('#filterYear, #filterCategory, #filterStatus').on('change', function() {
            eventsTable.draw();
        });
    });
</script>
@endpush
