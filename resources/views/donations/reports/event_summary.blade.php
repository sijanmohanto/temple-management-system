@extends('layouts.app')

@section('title', __('donations.event_summary_report'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('collections.reports.index') }}" class="btn btn-sm btn-outline-secondary rounded-circle">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0 text-dark">{{ __('donations.event_summary_report') }}</h1>
            </div>
            <p class="text-muted small mb-0">{{ __('events.events') }} &bull; Festival Collection &amp; Pledge Breakdown</p>
        </div>

        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                <i class="bi bi-printer me-1"></i> {{ __('common.print') ?? 'Print Report' }}
            </button>
            <a href="{{ route('collections.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('donations.dashboard') }}
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('collections.reports.event-summary') }}" class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label class="form-label small fw-semibold text-muted">{{ __('events.event') }}</label>
                    <select name="event_id" class="form-select select2" data-placeholder="All Events">
                        <option value="">All Events</option>
                        @foreach($events as $evt)
                            <option value="{{ $evt->id }}" {{ $selectedEventId == $evt->id ? 'selected' : '' }}>
                                {{ $evt->name }} ({{ $evt->event_year }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">
                        <i class="bi bi-funnel me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('collections.reports.event-summary') }}" class="btn btn-outline-secondary rounded-pill" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Overall KPI Metrics -->
    @php
        $sumPledged = $reportEvents->sum('total_pledged');
        $sumVerified = $reportEvents->sum('total_verified');
        $sumPending = $reportEvents->sum('pending_collections');
        $sumOutstanding = $reportEvents->sum('outstanding_pledges');
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.total_pledged') }}</span>
                    <h4 class="fw-bold text-dark mb-0">৳{{ number_format($sumPledged, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.total_verified') }}</span>
                    <h4 class="fw-bold text-success mb-0">৳{{ number_format($sumVerified, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.pending_verification_amount') }}</span>
                    <h4 class="fw-bold text-warning mb-0">৳{{ number_format($sumPending, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.outstanding_amount') }}</span>
                    <h4 class="fw-bold {{ $sumOutstanding > 0 ? 'text-danger' : 'text-muted' }} mb-0">৳{{ number_format($sumOutstanding, 2) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Summary Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="eventSummaryTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('events.event') }}</th>
                            <th>{{ __('donations.pledges') }}</th>
                            <th>{{ __('donations.total_pledged') }}</th>
                            <th>{{ __('donations.verified_payments') }}</th>
                            <th>{{ __('donations.pending_verification') }}</th>
                            <th>{{ __('donations.outstanding_amount') }}</th>
                            <th class="text-end pe-3 d-print-none">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportEvents as $row)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $row['event']->name }}</div>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $row['event']->event_year }}</span>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ $row['event']->category?->name ?? 'General' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $row['pledge_count'] }} Pledges</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">৳{{ number_format($row['total_pledged'], 2) }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-success">৳{{ number_format($row['total_verified'], 2) }}</div>
                                    <div class="small text-muted" style="font-size: 0.75rem;">
                                        Pledges: ৳{{ number_format($row['verified_pledge_collections'], 2) }} &bull;
                                        Voluntary: ৳{{ number_format($row['verified_voluntary_collections'], 2) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold {{ $row['pending_collections'] > 0 ? 'text-warning' : 'text-muted' }}">
                                        ৳{{ number_format($row['pending_collections'], 2) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold {{ $row['outstanding_pledges'] > 0 ? 'text-danger' : 'text-muted' }}">
                                        ৳{{ number_format($row['outstanding_pledges'], 2) }}
                                    </div>
                                </td>
                                <td class="text-end pe-3 d-print-none">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('events.show', $row['event']) }}" class="btn btn-outline-primary" title="{{ __('common.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('pledges.index', ['event_id' => $row['event']->id]) }}" class="btn btn-outline-secondary" title="{{ __('donations.pledges') }}">
                                            <i class="bi bi-card-checklist"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    {{ __('common.no_data_found') ?? 'No events found.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="ps-3" colspan="2">{{ __('common.total') ?? 'Total' }}</td>
                            <td>৳{{ number_format($sumPledged, 2) }}</td>
                            <td class="text-success">৳{{ number_format($sumVerified, 2) }}</td>
                            <td class="text-warning">৳{{ number_format($sumPending, 2) }}</td>
                            <td class="text-danger">৳{{ number_format($sumOutstanding, 2) }}</td>
                            <td class="d-print-none"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        @if($reportEvents->isNotEmpty())
            TempleUI.initDataTable('#eventSummaryTable', {
                pageLength: 25,
                columns: [
                    { orderable: true },
                    { orderable: true },
                    { orderable: true },
                    { orderable: true },
                    { orderable: true },
                    { orderable: true },
                    { orderable: false }
                ]
            });
        @endif
    });
</script>
@endpush
