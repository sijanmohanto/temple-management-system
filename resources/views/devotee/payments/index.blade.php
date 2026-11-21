@extends('layouts.app')

@section('title', __('devotee.my_payments_title'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-wallet2 text-danger me-2"></i> {{ __('devotee.my_payments_title') }}
                    </h3>
                    <p class="text-muted small mb-0">{{ __('devotee.my_payments_subtitle') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('devotee.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-speedometer2 me-1"></i> {{ __('devotee.back_to_dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form id="paymentFilterForm" class="row g-3">
                <!-- Event Filter -->
                <div class="col-md-3">
                    <label for="filterEvent" class="form-label small fw-semibold text-secondary">{{ __('portal.public.event') }}</label>
                    <select id="filterEvent" name="event_id" class="form-select select2-basic">
                        <option value="">{{ __('common.all') }} {{ __('portal.public.events') }}</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->name }} ({{ $event->event_year }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2">
                    <label for="filterStatus" class="form-label small fw-semibold text-secondary">{{ __('portal.public.status') }}</label>
                    <select id="filterStatus" name="status" class="form-select">
                        <option value="all">{{ __('common.all') }}</option>
                        <option value="verified">{{ __('portal.public.status_verified') }}</option>
                        <option value="pending">{{ __('portal.public.status_pending') }}</option>
                        <option value="rejected">{{ __('portal.public.status_rejected') }}</option>
                        <option value="reversed">{{ __('portal.public.status_reversed') }}</option>
                    </select>
                </div>

                <!-- Payment Method Filter -->
                <div class="col-md-2">
                    <label for="filterMethod" class="form-label small fw-semibold text-secondary">{{ __('portal.public.payment_method') }}</label>
                    <select id="filterMethod" name="payment_method" class="form-select">
                        <option value="all">{{ __('common.all') }}</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="rocket">Rocket</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>

                <!-- Date Range Filters -->
                <div class="col-md-2">
                    <label for="filterDateFrom" class="form-label small fw-semibold text-secondary">{{ __('common.date_from') }}</label>
                    <input type="date" id="filterDateFrom" name="date_from" class="form-control">
                </div>
                <div class="col-md-2">
                    <label for="filterDateTo" class="form-label small fw-semibold text-secondary">{{ __('common.date_to') }}</label>
                    <input type="date" id="filterDateTo" name="date_to" class="form-control">
                </div>

                <!-- Filter Actions -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary w-100" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments DataTable Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="devoteePaymentsTable" class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('portal.public.payment_code') }}</th>
                            <th>{{ __('portal.public.event') }}</th>
                            <th>{{ __('portal.public.allocation_breakdown') }}</th>
                            <th>{{ __('portal.public.amount') }}</th>
                            <th>{{ __('portal.public.payment_method') }}</th>
                            <th>{{ __('portal.public.payment_date') }}</th>
                            <th>{{ __('portal.public.status') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated via DataTables Server-Side AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Offcanvas for Payment Details -->
<div class="offcanvas offcanvas-end shadow" tabindex="-1" id="paymentDetailsOffcanvas" aria-labelledby="offcanvasLabel" style="width: 480px;">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold text-dark" id="offcanvasLabel">
            <i class="bi bi-wallet2 text-danger me-2"></i> {{ __('devotee.payment_details_title') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4" id="paymentDetailsBody">
        <div class="text-center py-5">
            <div class="spinner-border text-danger" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#devoteePaymentsTable').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        ajax: {
            url: "{{ route('devotee.payments.index') }}",
            data: function(d) {
                d.event_id = $('#filterEvent').val();
                d.status = $('#filterStatus').val();
                d.payment_method = $('#filterMethod').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
            }
        },
        language: window.TempleConfig.datatablesLanguage,
        pageLength: 10,
        responsive: true
    });

    // Event Filters
    $('#filterEvent, #filterStatus, #filterMethod, #filterDateFrom, #filterDateTo').on('change', function() {
        table.draw();
    });

    $('#resetFiltersBtn').on('click', function() {
        $('#paymentFilterForm')[0].reset();
        $('#filterEvent').val('').trigger('change');
        table.draw();
    });

    // View Details Button Handler (Offcanvas)
    $(document).on('click', '.view-details-btn', function() {
        var paymentCode = $(this).data('code');
        var offcanvasElement = document.getElementById('paymentDetailsOffcanvas');
        var offcanvas = new bootstrap.Offcanvas(offcanvasElement);
        offcanvas.show();

        $('#paymentDetailsBody').html(
            '<div class="text-center py-5">' +
                '<div class="spinner-border text-danger" role="status"></div>' +
                '<p class="text-muted small mt-2">' + window.TempleConfig.i18n.loading + '</p>' +
            '</div>'
        );

        $.ajax({
            url: "{{ url('/devotee/payments') }}/" + paymentCode + "/details",
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    var p = res.payment;
                    var html = '';

                    // Header Info
                    html += '<div class="d-flex justify-content-between align-items-center mb-3">';
                    html += '<span class="badge bg-light text-dark border font-monospace fs-6">' + p.payment_code + '</span>';
                    if (p.status === 'verified') {
                        html += '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Verified</span>';
                    } else if (p.status === 'pending') {
                        html += '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> Pending Verification</span>';
                    } else if (p.status === 'rejected') {
                        html += '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Rejected</span>';
                    } else if (p.status === 'reversed') {
                        html += '<span class="badge bg-dark"><i class="bi bi-arrow-counterclockwise me-1"></i> Reversed</span>';
                    }
                    html += '</div>';

                    // Amount Card
                    html += '<div class="p-3 bg-light rounded-3 text-center mb-4 border">';
                    html += '<small class="text-muted text-uppercase fw-semibold d-block">Submitted Amount</small>';
                    html += '<h2 class="fw-bold text-dark mb-0">৳' + parseFloat(p.amount).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</h2>';
                    html += '</div>';

                    // Detail List
                    const methodLogos = {
                        bkash: "{{ asset('assets/images/methods/bkash.svg') }}",
                        nagad: "{{ asset('assets/images/methods/nagad.svg') }}",
                        rocket: "{{ asset('assets/images/methods/rocket.svg') }}",
                        bank_transfer: "{{ asset('assets/images/methods/bank.svg') }}",
                        cash: "{{ asset('assets/images/methods/cash.svg') }}"
                    };
                    const methodNames = {
                        bkash: "bKash",
                        nagad: "Nagad",
                        rocket: "Rocket",
                        bank_transfer: "{{ __('portal.payment_methods.bank_transfer') }}",
                        cash: "{{ __('donations.method_cash') ?? 'Cash' }}"
                    };
                    const mLogo = methodLogos[p.payment_method] || methodLogos['cash'];
                    const mLabel = methodNames[p.payment_method] || p.payment_method;
                    const methodBadgeHtml = '<span class="payment-method-badge method-' + p.payment_method + '"><img src="' + mLogo + '" class="method-logo" alt="' + mLabel + '"> <span class="method-name">' + mLabel + '</span></span>';

                    html += '<div class="list-group list-group-flush mb-4">';
                    html += '<div class="list-group-item d-flex justify-content-between px-0 py-2"><span>Event</span><strong class="text-dark">' + (p.event_name || '—') + '</strong></div>';
                    html += '<div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2"><span>Payment Method</span>' + methodBadgeHtml + '</div>';
                    if (p.transaction_reference) {
                        html += '<div class="list-group-item d-flex justify-content-between px-0 py-2"><span>Transaction Ref</span><strong class="font-monospace text-dark">' + p.transaction_reference + '</strong></div>';
                    }
                    if (p.sender_mobile) {
                        html += '<div class="list-group-item d-flex justify-content-between px-0 py-2"><span>Sender Mobile</span><strong class="text-dark">' + p.sender_mobile + '</strong></div>';
                    }
                    html += '<div class="list-group-item d-flex justify-content-between px-0 py-2"><span>Payment Date</span><strong class="text-dark">' + (p.payment_date || '—') + '</strong></div>';
                    if (p.verified_at) {
                        html += '<div class="list-group-item d-flex justify-content-between px-0 py-2"><span>Verified On</span><strong class="text-success">' + p.verified_at + '</strong></div>';
                    }
                    if (p.rejection_reason) {
                        html += '<div class="list-group-item px-0 py-2 bg-danger bg-opacity-10 text-danger rounded mt-2 p-2"><strong>Rejection Reason:</strong> ' + p.rejection_reason + '</div>';
                    }
                    if (p.reversal_reason) {
                        html += '<div class="list-group-item px-0 py-2 bg-dark bg-opacity-10 text-dark rounded mt-2 p-2"><strong>Reversal Reason:</strong> ' + p.reversal_reason + ' (' + p.reversed_at + ')</div>';
                    }
                    html += '</div>';

                    // Receipt Action
                    if (p.receipt_number && p.receipt_url) {
                        html += '<div class="d-grid gap-2">';
                        html += '<a href="' + p.receipt_url + '" target="_blank" class="btn temple-btn-maroon"><i class="bi bi-receipt me-1"></i> View Official Receipt (' + p.receipt_number + ')</a>';
                        html += '</div>';
                    }

                    $('#paymentDetailsBody').html(html);
                }
            },
            error: function(err) {
                $('#paymentDetailsBody').html('<div class="alert alert-danger mb-0">Failed to load payment details.</div>');
            }
        });
    });
});
</script>
@endpush
