@extends('layouts.app')

@section('title', __('donations.all_payments'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('donations.collections') }}</h1>
            <p class="text-muted small mb-0">{{ __('donations.all_payments') }} &bull; {{ $payments->total() }} {{ __('common.details') }}</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('collections.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('donations.dashboard') }}
            </a>
            @if(Auth::user()->hasPermission('record_collections'))
                <a href="{{ route('collections.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                    <i class="bi bi-cash-plus me-1"></i> {{ __('donations.submit_payment') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="mb-4">
        <div class="nav nav-pills gap-2 bg-light p-2 rounded-4 d-inline-flex border" id="paymentStatusTabs">
            <a href="{{ route('collections.index') }}" class="nav-link rounded-pill px-3 {{ empty($filters['status']) ? 'active bg-dark text-white' : 'text-dark' }}">
                All Payments
            </a>
            <a href="{{ route('collections.index', ['status' => 'pending_verification']) }}" class="nav-link rounded-pill px-3 {{ ($filters['status'] ?? '') === 'pending_verification' ? 'active bg-warning text-dark fw-bold' : 'text-dark' }}">
                <i class="bi bi-hourglass-split me-1"></i> {{ __('donations.pending_verification') }}
                @if($pendingCount > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('collections.index', ['status' => 'verified']) }}" class="nav-link rounded-pill px-3 {{ ($filters['status'] ?? '') === 'verified' ? 'active bg-success text-white' : 'text-dark' }}">
                <i class="bi bi-check2-circle me-1"></i> {{ __('donations.verified_payments') }}
            </a>
            <a href="{{ route('collections.index', ['status' => 'rejected']) }}" class="nav-link rounded-pill px-3 {{ ($filters['status'] ?? '') === 'rejected' ? 'active bg-danger text-white' : 'text-dark' }}">
                {{ __('donations.rejected_payments') }}
            </a>
            <a href="{{ route('collections.index', ['status' => 'reversed']) }}" class="nav-link rounded-pill px-3 {{ ($filters['status'] ?? '') === 'reversed' ? 'active bg-secondary text-white' : 'text-dark' }}">
                {{ __('donations.reversed_payments') }}
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('collections.index') }}" class="row g-3" id="collectionsFilterForm">
                <input type="hidden" name="status" id="filterStatus" value="{{ $filters['status'] ?? '' }}">

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('donations.type') }}</label>
                    <select name="collection_type" id="filterType" class="form-select select2" data-placeholder="All Collection Contexts">
                        <option value="">All Contexts</option>
                        <option value="event_pledge" {{ ($filters['collection_type'] ?? '') === 'event_pledge' ? 'selected' : '' }}>{{ __('donations.type_event_pledge') }}</option>
                        <option value="event_donation" {{ ($filters['collection_type'] ?? '') === 'event_donation' ? 'selected' : '' }}>{{ __('donations.type_event_donation') }}</option>
                        <option value="monthly_contribution" {{ ($filters['collection_type'] ?? '') === 'monthly_contribution' ? 'selected' : '' }}>{{ __('donations.type_monthly_contribution') }}</option>
                        <option value="general_donation" {{ ($filters['collection_type'] ?? '') === 'general_donation' ? 'selected' : '' }}>{{ __('donations.type_general_donation') }}</option>
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('donations.payment_method') }}</label>
                    <select name="payment_method" id="filterMethod" class="form-select select2" data-placeholder="All Payment Methods">
                        <option value="">All Methods</option>
                        <option value="cash" {{ ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' }}>{{ __('donations.method_cash') }}</option>
                        <option value="bank_transfer" {{ ($filters['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' }}>{{ __('donations.method_bank_transfer') }}</option>
                        <option value="bkash" {{ ($filters['payment_method'] ?? '') === 'bkash' ? 'selected' : '' }}>{{ __('donations.method_bkash') }}</option>
                        <option value="nagad" {{ ($filters['payment_method'] ?? '') === 'nagad' ? 'selected' : '' }}>{{ __('donations.method_nagad') }}</option>
                        <option value="rocket" {{ ($filters['payment_method'] ?? '') === 'rocket' ? 'selected' : '' }}>{{ __('donations.method_rocket') }}</option>
                        <option value="cheque" {{ ($filters['payment_method'] ?? '') === 'cheque' ? 'selected' : '' }}>{{ __('donations.method_cheque') }}</option>
                        <option value="other" {{ ($filters['payment_method'] ?? '') === 'other' ? 'selected' : '' }}>{{ __('donations.method_other') }}</option>
                    </select>
                </div>

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

                <div class="col-md-12 col-lg-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">
                        <i class="bi bi-funnel me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('collections.index') }}" class="btn btn-outline-secondary rounded-pill" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="paymentsDataTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('donations.payment_code') }}</th>
                            <th>{{ __('donations.payer') }}</th>
                            <th>{{ __('donations.type') }}</th>
                            <th>{{ __('donations.payment_method') }}</th>
                            <th>{{ __('donations.amount') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace">{{ $payment->payment_code }}</span>
                                    @if($payment->receipt)
                                        <div class="mt-1">
                                            <a href="{{ route('receipts.show', $payment->receipt) }}" class="badge {{ $payment->receipt->isValid() ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' }} text-decoration-none">
                                                <i class="bi bi-receipt me-1"></i>{{ $payment->receipt->receipt_number }}
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $payment->payer_display_name }}</div>
                                    @if($payment->payerPerson && $payment->payerPerson->phone)
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $payment->payerPerson->phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mb-1" style="font-size: 0.72rem;">
                                        {{ __('donations.type_'.$payment->collection_type) }}
                                    </span>
                                    @if($payment->event)
                                        <div class="small text-dark fw-semibold">{{ $payment->event->name }}</div>
                                    @endif
                                    <div class="small text-muted"><i class="bi bi-calendar3 me-1"></i>{{ $payment->payment_date->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><i class="bi bi-credit-card me-1 text-muted"></i>{{ __('donations.method_'.$payment->payment_method) }}</div>
                                    @if($payment->transaction_reference)
                                        <div class="small text-muted font-monospace">
                                            <i class="bi bi-hash me-1"></i>{{ Auth::user()->hasPermission('view_sensitive_payment_details') ? $payment->transaction_reference : substr($payment->transaction_reference, 0, 4).'****' }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fs-6 fw-bold text-dark">৳{{ number_format((float) $payment->amount, 2) }}</div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match ($payment->status) {
                                            'verified' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                            'pending_verification' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                                            'rejected' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                            'reversed' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                            default => 'bg-secondary bg-opacity-10 text-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} px-2 py-1">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>{{ __('donations.status_'.$payment->status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('collections.show', $payment) }}" class="btn btn-outline-primary" title="{{ __('common.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($payment->isPending() && Auth::user()->hasPermission('verify_collections'))
                                            <a href="{{ route('collections.show', $payment) }}" class="btn btn-outline-success" title="{{ __('donations.verify_payment') }}">
                                                <i class="bi bi-check2-circle"></i>
                                            </a>
                                        @endif
                                        @if($payment->receipt && $payment->receipt->isValid() && Auth::user()->hasPermission('print_receipts'))
                                            <a href="{{ route('receipts.print', $payment->receipt) }}" target="_blank" class="btn btn-outline-secondary" title="{{ __('donations.print_receipt') }}">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="paymentsNativePagination" class="mt-3">
                @if($payments->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $payments->links() }}
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
        var table = TempleUI.initDataTable('#paymentsDataTable', {
            serverSide: true,
            processing: true,
            deferLoading: {{ $payments->total() }},
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
            ajax: {
                url: "{{ route('collections.index') }}",
                data: function(d) {
                    d.status = $('#filterStatus').val();
                    d.collection_type = $('#filterType').val();
                    d.payment_method = $('#filterMethod').val();
                    d.event_id = $('#filterEvent').val();
                }
            },
            columns: [
                { data: 0, name: 'payment_code' },
                { data: 1, name: 'payer' },
                { data: 2, name: 'payment_date' },
                { data: 3, name: 'method', orderable: false },
                { data: 4, name: 'amount' },
                { data: 5, name: 'status' },
                { data: 6, name: 'actions', orderable: false, className: 'text-end pe-3' }
            ]
        });

        $('#paymentsNativePagination').hide();

        $('#collectionsFilterForm').on('submit', function(e) {
            e.preventDefault();
            table.draw();
        });

        $('#filterType, #filterMethod, #filterEvent').on('change', function() {
            table.draw();
        });
    });
</script>
@endpush
