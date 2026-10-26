@extends('layouts.app')

@section('title', __('donations.outstanding_pledges_report'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('collections.reports.index') }}" class="btn btn-sm btn-outline-secondary rounded-circle">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0 text-dark">{{ __('donations.outstanding_pledges_report') }}</h1>
            </div>
            <p class="text-muted small mb-0">{{ __('donations.pledges') }} &bull; {{ $pledges->count() }} Due Pledges</p>
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
            <form method="GET" action="{{ route('collections.reports.outstanding-pledges') }}" class="row g-3 align-items-end">
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
                    <a href="{{ route('collections.reports.outstanding-pledges') }}" class="btn btn-outline-secondary rounded-pill" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4">
                    <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.total_pledged') }}</span>
                    <h3 class="fw-bold text-dark mb-0">৳{{ number_format($totalPledged, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4">
                    <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.paid_amount') }}</span>
                    <h3 class="fw-bold text-success mb-0">৳{{ number_format($totalPaid, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4">
                    <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.outstanding_amount') }}</span>
                    <h3 class="fw-bold text-danger mb-0">৳{{ number_format($totalOutstanding, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Pledges Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="outstandingPledgesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('donations.pledge_code') }}</th>
                            <th>{{ __('donations.payer') }}</th>
                            <th>{{ __('events.event') }}</th>
                            <th>{{ __('donations.pledged_amount') }}</th>
                            <th>{{ __('donations.paid_amount') }}</th>
                            <th>{{ __('donations.outstanding_amount') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end pe-3 d-print-none">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pledges as $pledge)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace">{{ $pledge->pledge_code }}</span>
                                    <div class="small text-muted mt-1">{{ $pledge->pledged_on?->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $pledge->owner_name }}</div>
                                    @if($pledge->person?->phone)
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $pledge->person->phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $pledge->event?->name ?? '—' }}</div>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $pledge->pledge_year }}</span>
                                    @if($pledge->purpose)
                                        <div class="small text-muted">{{ $pledge->purpose }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">৳{{ number_format((float) $pledge->pledged_amount, 2) }}</div>
                                </td>
                                <td>
                                    <div class="text-success fw-semibold">৳{{ number_format($pledge->verified_paid_amount, 2) }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-danger">৳{{ number_format($pledge->outstanding_amount, 2) }}</div>
                                </td>
                                <td>
                                    @if($pledge->status === 'partial')
                                        <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 px-2 py-1">
                                            {{ __('donations.status_partial') }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                            {{ __('donations.status_unpaid') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3 d-print-none">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('pledges.show', $pledge) }}" class="btn btn-outline-primary" title="{{ __('common.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if(Auth::user()->hasPermission('record_collections'))
                                            <a href="{{ route('collections.create', ['pledge_id' => $pledge->id]) }}" class="btn btn-outline-success" title="{{ __('donations.collect_payment') }}">
                                                <i class="bi bi-cash"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-check2-all fs-2 d-block mb-2 text-success"></i>
                                    {{ __('common.no_data_found') ?? 'No outstanding pledges found. All pledges are fulfilled!' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="ps-3" colspan="3">{{ __('common.total') ?? 'Total' }}</td>
                            <td>৳{{ number_format($totalPledged, 2) }}</td>
                            <td class="text-success">৳{{ number_format($totalPaid, 2) }}</td>
                            <td class="text-danger">৳{{ number_format($totalOutstanding, 2) }}</td>
                            <td colspan="2" class="d-print-none"></td>
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
        @if($pledges->isNotEmpty())
            TempleUI.initDataTable('#outstandingPledgesTable', {
                pageLength: 25,
                columns: [
                    { orderable: true },
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
