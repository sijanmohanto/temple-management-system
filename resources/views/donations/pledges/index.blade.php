@extends('layouts.app')

@section('title', __('donations.all_pledges'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('donations.pledges') }}</h1>
            <p class="text-muted small mb-0">{{ __('donations.all_pledges') }} &bull; {{ $pledges->total() }} {{ __('common.details') }}</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('collections.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('donations.dashboard') }}
            </a>
            @if(Auth::user()->hasPermission('manage_pledges'))
                <a href="{{ route('pledges.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                    <i class="bi bi-plus-circle-fill me-1"></i> {{ __('donations.add_pledge') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('pledges.index') }}" class="row g-3" id="pledgesFilterForm">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('events.event') }}</label>
                    <select name="event_id" id="filterEvent" class="form-select select2" data-placeholder="All Events">
                        <option value="">All Events</option>
                        @foreach($events as $evt)
                            <option value="{{ $evt->id }}" {{ ($filters['event_id'] ?? '') == $evt->id ? 'selected' : '' }}>
                                {{ $evt->name }} ({{ $evt->event_year }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('donations.payment_year') }}</label>
                    <select name="pledge_year" id="filterYear" class="form-select select2" data-placeholder="All Years">
                        <option value="">All Years</option>
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ ($filters['pledge_year'] ?? '') == $yr ? 'selected' : '' }}>
                                {{ $yr }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('common.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="unpaid" {{ ($filters['status'] ?? '') === 'unpaid' ? 'selected' : '' }}>{{ __('donations.status_unpaid') }}</option>
                        <option value="partial" {{ ($filters['status'] ?? '') === 'partial' ? 'selected' : '' }}>{{ __('donations.status_partial') }}</option>
                        <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>{{ __('donations.status_paid') }}</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>{{ __('donations.status_cancelled') }}</option>
                    </select>
                </div>

                <div class="col-md-12 col-lg-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">
                        <i class="bi bi-funnel me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('pledges.index') }}" class="btn btn-outline-secondary rounded-pill" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Pledges Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="pledgesDataTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('donations.pledge_code') }}</th>
                            <th>{{ __('donations.payer') }}</th>
                            <th>{{ __('events.event') }}</th>
                            <th>{{ __('donations.pledged_amount') }}</th>
                            <th>{{ __('donations.paid_amount') }}</th>
                            <th>{{ __('donations.outstanding_amount') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pledges as $pledge)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace">{{ $pledge->pledge_code }}</span>
                                    <div class="small text-muted mt-1">{{ $pledge->pledged_on->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $pledge->owner_name }}</div>
                                    @if($pledge->person && $pledge->person->phone)
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $pledge->person->phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $pledge->event?->name ?? '—' }}</div>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">{{ $pledge->pledge_year }}</span>
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
                                    <div class="fw-bold {{ $pledge->outstanding_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                        ৳{{ number_format($pledge->outstanding_amount, 2) }}
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match ($pledge->status) {
                                            'paid' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                            'partial' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                                            'unpaid' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                            'cancelled' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                            default => 'bg-secondary bg-opacity-10 text-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} px-2 py-1">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>{{ __('donations.status_'.$pledge->status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('pledges.show', $pledge) }}" class="btn btn-outline-primary" title="{{ __('common.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if(Auth::user()->hasPermission('manage_pledges') && $pledge->status !== 'cancelled' && $pledge->status !== 'paid')
                                            <a href="{{ route('pledges.edit', $pledge) }}" class="btn btn-outline-secondary" title="{{ __('common.edit') }}">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
                                        @if(Auth::user()->hasPermission('record_collections') && $pledge->outstanding_amount > 0 && $pledge->status !== 'cancelled')
                                            <a href="{{ route('collections.create', ['pledge_id' => $pledge->id]) }}" class="btn btn-outline-success" title="{{ __('donations.collect_payment') }}">
                                                <i class="bi bi-cash"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="pledgesNativePagination" class="mt-3">
                @if($pledges->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $pledges->links() }}
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
        var table = TempleUI.initDataTable('#pledgesDataTable', {
            serverSide: true,
            processing: true,
            deferLoading: {{ $pledges->total() }},
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
            ajax: {
                url: "{{ route('pledges.index') }}",
                data: function(d) {
                    d.event_id = $('#filterEvent').val();
                    d.pledge_year = $('#filterYear').val();
                    d.status = $('#filterStatus').val();
                }
            },
            columns: [
                { data: 0, name: 'pledge_code' },
                { data: 1, name: 'owner' },
                { data: 2, name: 'event' },
                { data: 3, name: 'pledged_amount' },
                { data: 4, name: 'paid_amount', orderable: false },
                { data: 5, name: 'outstanding', orderable: false },
                { data: 6, name: 'status' },
                { data: 7, name: 'actions', orderable: false, className: 'text-end pe-3' }
            ]
        });

        $('#pledgesNativePagination').hide();

        $('#pledgesFilterForm').on('submit', function(e) {
            e.preventDefault();
            table.draw();
        });

        $('#filterEvent, #filterYear, #filterStatus').on('change', function() {
            table.draw();
        });
    });
</script>
@endpush
