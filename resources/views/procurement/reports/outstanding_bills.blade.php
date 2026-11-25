@extends('layouts.app')

@section('title', __('procurement.outstanding_bills_report_title'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">
                <i class="bi bi-receipt-cutoff text-danger me-2"></i>{{ __('procurement.outstanding_bills_report_title') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('procurement.outstanding_bills_report_subtitle') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('procurement.bills.index') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-receipt me-1"></i> {{ __('procurement.bill_list') }}
            </a>
            @if(Auth::user()->hasAnyPermission(['export_procurement_reports', 'view_procurement_reports']))
            <a href="{{ route('procurement.reports.outstanding-bills.export') }}" id="btnExportCsv" class="btn btn-outline-success rounded-pill px-3 shadow-sm" target="_blank">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('procurement.export_csv') }}
            </a>
            @endif
            <a href="{{ route('procurement.reports.outstanding-bills.print') }}" id="btnPrintReport" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" target="_blank">
                <i class="bi bi-printer me-1"></i> {{ __('procurement.print_report') }}
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4" id="billsSummaryCards">
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-primary h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.approved_bill_total') }}</span>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h4 mb-0 fw-bold text-dark" id="summaryTotalBillsCount">{{ number_format($summary['total_bills_count']) }}</span>
                        <span class="small fw-bold text-primary" id="summaryApprovedAmount">{{ $summary['total_approved_amount_formatted'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.total_paid_amount') }}</span>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h4 mb-0 fw-bold text-success" id="summaryPaidAmount">{{ $summary['total_paid_amount_formatted'] }}</span>
                        <span class="small text-muted font-xs">{{ __('procurement.fully_paid_bills') }}: <strong id="summaryFullyPaidCount">{{ $summary['fully_paid_bills_count'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-danger h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.total_outstanding_amount') }}</span>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h4 mb-0 fw-bold text-danger" id="summaryOutstandingAmount">{{ $summary['total_outstanding_amount_formatted'] }}</span>
                        <span class="small text-muted font-xs">Unpaid: <strong id="summaryUnpaidCount">{{ $summary['unpaid_bills_count'] }}</strong> &bull; Partial: <strong id="summaryPartialCount">{{ $summary['partially_paid_bills_count'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-12">
            <div class="card border-0 shadow-sm rounded-4 bg-danger bg-opacity-10 border border-danger border-opacity-25 p-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                        <div>
                            <span class="fw-bold text-danger">{{ __('procurement.overdue_bills') }}</span>
                            <span class="text-muted small ms-2">Bills past their payment due date with positive remaining outstanding balance</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-3">
                        <span class="badge bg-danger text-white fs-6 px-3 py-1" id="summaryOverdueCount">{{ $summary['overdue_bills_count'] }} Overdue</span>
                        <span class="h5 mb-0 fw-bold text-danger" id="summaryOverdueAmount">{{ $summary['overdue_amount_formatted'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form id="billsFilterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.vendor') }}</label>
                    <select name="vendor_id" id="filterVendorId" class="form-select form-select-sm select2-filter">
                        <option value="all">{{ __('procurement.all_vendors') }}</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.payment_status_filter') }}</label>
                    <select name="payment_status" id="filterPaymentStatus" class="form-select form-select-sm">
                        <option value="all">{{ __('procurement.all_statuses') }}</option>
                        <option value="unpaid">{{ __('procurement.payment_unpaid') }}</option>
                        <option value="partially_paid">{{ __('procurement.payment_partial') }}</option>
                        <option value="paid">{{ __('procurement.payment_paid') }}</option>
                        <option value="overdue">{{ __('procurement.overdue') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.overdue_status_filter') }}</label>
                    <select name="is_overdue" id="filterIsOverdue" class="form-select form-select-sm">
                        <option value="all">{{ __('procurement.all_records') }}</option>
                        <option value="overdue">{{ __('procurement.overdue_only') }}</option>
                        <option value="not_overdue">{{ __('procurement.not_overdue') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('common.search') }}</label>
                    <input type="text" name="search" id="filterSearch" class="form-control form-control-sm" placeholder="{{ __('procurement.search_ref_placeholder') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.bill_date_from') }}</label>
                    <input type="date" name="bill_date_from" id="filterBillDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.bill_date_to') }}</label>
                    <input type="date" name="bill_date_to" id="filterBillDateTo" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.min_amount') }}</label>
                    <input type="number" step="0.01" name="min_amount" id="filterMinAmount" class="form-control form-control-sm" placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.max_amount') }}</label>
                    <input type="number" step="0.01" name="max_amount" id="filterMaxAmount" class="form-control form-control-sm" placeholder="0.00">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1 shadow-sm" id="btnApplyFilters">
                        <i class="bi bi-filter me-1"></i> {{ __('common.filter') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetFilters" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable Card -->
    <!-- STRICTLY NO SERIAL # COLUMN -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="outstandingBillsReportTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('procurement.bill_ref_short') }}</th>
                            <th>{{ __('procurement.vendor_name_short') }}</th>
                            <th>{{ __('procurement.po_ref_short') }}</th>
                            <th>{{ __('procurement.bill_date_short') }}</th>
                            <th>{{ __('procurement.due_date_short') }}</th>
                            <th class="text-end">{{ __('procurement.bill_amount_short') }}</th>
                            <th class="text-end">{{ __('procurement.paid_amount_short') }}</th>
                            <th class="text-end">{{ __('procurement.outstanding_short') }}</th>
                            <th class="text-center">{{ __('procurement.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Vendor Bill Details Bootstrap Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="billDetailsOffcanvas" aria-labelledby="billDetailsOffcanvasLabel" style="width: 700px; max-width: 96vw;">
    <div class="offcanvas-header bg-light border-bottom">
        <div>
            <h5 class="offcanvas-title fw-bold text-dark" id="billDetailsOffcanvasLabel">
                <i class="bi bi-receipt-cutoff text-primary me-2"></i>{{ __('procurement.bill_details') }}
            </h5>
            <span class="text-muted small">{{ __('procurement.procurement_reports') }} &bull; {{ __('procurement.increment_status') }}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3 p-md-4" id="billDetailsOffcanvasBody">
        <!-- Loaded via AJAX -->
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // 1. Initialize Select2
    if ($.fn.select2) {
        $('.select2-filter').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        });
    }

    // 2. Offcanvas instance
    var offcanvasEl = document.getElementById('billDetailsOffcanvas');
    var offcanvasInstance = offcanvasEl ? new bootstrap.Offcanvas(offcanvasEl) : null;

    // Helper to get active filter parameters
    function getFilterParams() {
        return {
            vendor_id: $('#filterVendorId').val(),
            payment_status: $('#filterPaymentStatus').val(),
            is_overdue: $('#filterIsOverdue').val(),
            search: $('#filterSearch').val(),
            bill_date_from: $('#filterBillDateFrom').val(),
            bill_date_to: $('#filterBillDateTo').val(),
            min_amount: $('#filterMinAmount').val(),
            max_amount: $('#filterMaxAmount').val()
        };
    }

    // 3. DataTable initialization — STRICTLY 10 COLUMNS, NO # COLUMN
    var table = TempleUI.initDataTable('#outstandingBillsReportTable', {
        processing: true,
        serverSide: true,
        pageLength: 10,
        order: [[3, 'desc']], // order by bill_date desc
        ajax: {
            url: '{{ route("procurement.reports.outstanding-bills") }}',
            data: function(d) {
                var filters = getFilterParams();
                $.extend(d, filters);
            }
        },
        columns: [
            { data: 'bill_number', name: 'bill_number' },
            { data: 'vendor', name: 'vendor_id' },
            { data: 'purchase_order', name: 'purchase_order_id' },
            { data: 'bill_date', name: 'bill_date' },
            { data: 'due_date', name: 'due_date' },
            { data: 'bill_amount', name: 'total_amount', className: 'text-end' },
            { data: 'paid_amount', name: 'paid_amount', className: 'text-end' },
            { data: 'outstanding_amount', name: 'outstanding_amount', className: 'text-end' },
            { data: 'payment_status', name: 'payment_status', className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end pe-3' }
        ],
        drawCallback: function(settings) {
            // Update summary cards dynamically from server response
            var json = settings.json;
            if (json && json.summary) {
                $('#summaryTotalBillsCount').text(Number(json.summary.total_bills_count || 0).toLocaleString());
                $('#summaryApprovedAmount').text(json.summary.total_approved_amount_formatted || '৳0.00');
                $('#summaryPaidAmount').text(json.summary.total_paid_amount_formatted || '৳0.00');
                $('#summaryFullyPaidCount').text(Number(json.summary.fully_paid_bills_count || 0).toLocaleString());
                $('#summaryOutstandingAmount').text(json.summary.total_outstanding_amount_formatted || '৳0.00');
                $('#summaryUnpaidCount').text(Number(json.summary.unpaid_bills_count || 0).toLocaleString());
                $('#summaryPartialCount').text(Number(json.summary.partially_paid_bills_count || 0).toLocaleString());
                $('#summaryOverdueCount').text(Number(json.summary.overdue_bills_count || 0).toLocaleString() + ' Overdue');
                $('#summaryOverdueAmount').text(json.summary.overdue_amount_formatted || '৳0.00');
            }

            // Sync export and print links with current filters
            var queryStr = $.param(getFilterParams());
            var baseExportUrl = '{{ route("procurement.reports.outstanding-bills.export") }}';
            var basePrintUrl = '{{ route("procurement.reports.outstanding-bills.print") }}';
            $('#btnExportCsv').attr('href', baseExportUrl + '?' + queryStr);
            $('#btnPrintReport').attr('href', basePrintUrl + '?' + queryStr);
        }
    });

    // 4. Filter form submissions
    $('#billsFilterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function() {
        $('#billsFilterForm')[0].reset();
        if ($.fn.select2) {
            $('.select2-filter').val('all').trigger('change');
        }
        table.ajax.reload();
    });

    // 5. AJAX View Details Offcanvas Handler
    $(document).on('click', '.btn-view-bill-details', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!id) return;

        $('#billDetailsOffcanvasBody').html(
            '<div class="text-center py-5">' +
                '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ __("common.loading") }}...</span></div>' +
                '<div class="mt-2 text-muted small">{{ __("common.loading") }}...</div>' +
            '</div>'
        );

        if (offcanvasInstance) {
            offcanvasInstance.show();
        }

        var url = '{{ route("procurement.reports.outstanding-bills.details", ":id") }}'.replace(':id', id);

        $.ajax({
            url: url,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(html) {
                $('#billDetailsOffcanvasBody').html(html);
            },
            error: function(xhr) {
                var msg = 'Failed to load vendor bill details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (window.TempleUI && window.TempleUI.toast) {
                    TempleUI.toast.error(msg);
                }
                $('#billDetailsOffcanvasBody').html(
                    '<div class="alert alert-danger m-3"><i class="bi bi-exclamation-triangle me-2"></i>' + msg + '</div>'
                );
            }
        });
    });
});
</script>
@endpush
