@extends('layouts.app')

@section('title', __('procurement.purchase_orders_report_title'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">
                <i class="bi bi-box-seam text-primary me-2"></i>{{ __('procurement.purchase_orders_report_title') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('procurement.purchase_orders_report_subtitle') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('procurement.orders.index') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-boxes me-1"></i> {{ __('procurement.order_list') }}
            </a>
            @if(Auth::user()->hasAnyPermission(['export_procurement_reports', 'view_procurement_reports']))
            <a href="{{ route('procurement.reports.orders.export') }}" id="btnExportCsv" class="btn btn-outline-success rounded-pill px-3 shadow-sm" target="_blank">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('procurement.export_csv') }}
            </a>
            @endif
            <a href="{{ route('procurement.reports.orders.print') }}" id="btnPrintReport" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" target="_blank">
                <i class="bi bi-printer me-1"></i> {{ __('procurement.print_report') }}
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4" id="ordersSummaryCards">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-primary h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.total_orders') }}</span>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h4 mb-0 fw-bold text-dark" id="summaryTotalCount">{{ number_format($summary['total_count']) }}</span>
                        <span class="small fw-bold text-primary" id="summaryHistoricalAmount">{{ $summary['total_historical_amount_formatted'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-info h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.active_committed_procurement') }}</span>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h4 mb-0 fw-bold text-info-emphasis" id="summaryActiveCount">{{ number_format($summary['active_committed_count']) }}</span>
                        <span class="small fw-bold text-info-emphasis" id="summaryActiveAmount">{{ $summary['active_committed_amount_formatted'] }}</span>
                    </div>
                    <small class="text-muted font-xs">{{ __('procurement.status_approved') }} + {{ __('procurement.status_sent') }}</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.completed_orders') }}</span>
                    <div class="h4 mb-0 fw-bold mt-1 text-success" id="summaryCompletedCount">{{ number_format($summary['completed_count']) }}</div>
                    <small class="text-muted font-xs" id="summaryCompletedAmount">{{ $summary['completed_amount_formatted'] }}</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-secondary h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.draft_orders') }}</span>
                    <div class="h4 mb-0 fw-bold mt-1 text-secondary" id="summaryDraftCount">{{ number_format($summary['draft_count']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-danger h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.cancelled_orders') }}</span>
                    <div class="h4 mb-0 fw-bold mt-1 text-danger" id="summaryCancelledCount">{{ number_format($summary['cancelled_count']) }}</div>
                    <small class="text-muted font-xs" id="summaryCancelledAmount">{{ $summary['cancelled_amount_formatted'] }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form id="ordersFilterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" id="filterStartDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" id="filterEndDate" class="form-control form-control-sm">
                </div>
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
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select form-select-sm">
                        <option value="all">{{ __('procurement.all_statuses') }}</option>
                        <option value="draft">{{ __('procurement.status_draft') }}</option>
                        <option value="approved">{{ __('procurement.status_approved') }}</option>
                        <option value="sent">{{ __('procurement.status_sent') }}</option>
                        <option value="completed">{{ __('procurement.status_completed') }}</option>
                        <option value="cancelled">{{ __('procurement.status_cancelled') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.min_amount') }}</label>
                    <input type="number" step="0.01" name="min_amount" id="filterMinAmount" class="form-control form-control-sm" placeholder="0.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.max_amount') }}</label>
                    <input type="number" step="0.01" name="max_amount" id="filterMaxAmount" class="form-control form-control-sm" placeholder="0.00">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">{{ __('common.search') }}</label>
                    <input type="text" name="search" id="filterSearch" class="form-control form-control-sm" placeholder="{{ __('procurement.search_ref_placeholder') }}">
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
                <table class="table table-hover align-middle mb-0 w-100" id="purchaseOrdersReportTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('procurement.po_ref_short') }}</th>
                            <th>{{ __('procurement.vendor_name_short') }}</th>
                            <th>{{ __('procurement.req_ref_short') }}</th>
                            <th>{{ __('procurement.request_date_short') }}</th>
                            <th>{{ __('procurement.delivery_date_short') }}</th>
                            <th class="text-end">{{ __('procurement.order_amount_short') }}</th>
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

<!-- Purchase Order Details Bootstrap Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="orderDetailsOffcanvas" aria-labelledby="orderDetailsOffcanvasLabel" style="width: 650px; max-width: 95vw;">
    <div class="offcanvas-header bg-light border-bottom">
        <div>
            <h5 class="offcanvas-title fw-bold text-dark" id="orderDetailsOffcanvasLabel">
                <i class="bi bi-box-seam text-primary me-2"></i>{{ __('procurement.order_details') }}
            </h5>
            <span class="text-muted small">{{ __('procurement.procurement_reports') }} &bull; {{ __('procurement.increment_status') }}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3 p-md-4" id="orderDetailsOffcanvasBody">
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
    var offcanvasEl = document.getElementById('orderDetailsOffcanvas');
    var offcanvasInstance = offcanvasEl ? new bootstrap.Offcanvas(offcanvasEl) : null;

    // Helper to get active filter parameters
    function getFilterParams() {
        return {
            start_date: $('#filterStartDate').val(),
            end_date: $('#filterEndDate').val(),
            vendor_id: $('#filterVendorId').val(),
            status: $('#filterStatus').val(),
            min_amount: $('#filterMinAmount').val(),
            max_amount: $('#filterMaxAmount').val(),
            search: $('#filterSearch').val()
        };
    }

    // 3. DataTable initialization — STRICTLY 8 COLUMNS, NO # COLUMN
    var table = TempleUI.initDataTable('#purchaseOrdersReportTable', {
        processing: true,
        serverSide: true,
        pageLength: 10,
        order: [[3, 'desc']], // order by order_date desc
        ajax: {
            url: '{{ route("procurement.reports.orders") }}',
            data: function(d) {
                var filters = getFilterParams();
                $.extend(d, filters);
            }
        },
        columns: [
            { data: 'order_number', name: 'order_number' },
            { data: 'vendor', name: 'vendor_id' },
            { data: 'requisition', name: 'purchase_request_id' },
            { data: 'order_date', name: 'order_date' },
            { data: 'expected_delivery_date', name: 'expected_delivery_date' },
            { data: 'total_amount', name: 'total_amount', className: 'text-end' },
            { data: 'status', name: 'status', className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end pe-3' }
        ],
        drawCallback: function(settings) {
            // Update summary cards dynamically from server response
            var json = settings.json;
            if (json && json.summary) {
                $('#summaryTotalCount').text(Number(json.summary.total_count || 0).toLocaleString());
                $('#summaryHistoricalAmount').text(json.summary.total_historical_amount_formatted || '৳0.00');
                $('#summaryActiveCount').text(Number(json.summary.active_committed_count || 0).toLocaleString());
                $('#summaryActiveAmount').text(json.summary.active_committed_amount_formatted || '৳0.00');
                $('#summaryCompletedCount').text(Number(json.summary.completed_count || 0).toLocaleString());
                $('#summaryCompletedAmount').text(json.summary.completed_amount_formatted || '৳0.00');
                $('#summaryDraftCount').text(Number(json.summary.draft_count || 0).toLocaleString());
                $('#summaryCancelledCount').text(Number(json.summary.cancelled_count || 0).toLocaleString());
                $('#summaryCancelledAmount').text(json.summary.cancelled_amount_formatted || '৳0.00');
            }

            // Sync export and print links with current filters
            var queryStr = $.param(getFilterParams());
            var baseExportUrl = '{{ route("procurement.reports.orders.export") }}';
            var basePrintUrl = '{{ route("procurement.reports.orders.print") }}';
            $('#btnExportCsv').attr('href', baseExportUrl + '?' + queryStr);
            $('#btnPrintReport').attr('href', basePrintUrl + '?' + queryStr);
        }
    });

    // 4. Filter form submissions
    $('#ordersFilterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function() {
        $('#ordersFilterForm')[0].reset();
        if ($.fn.select2) {
            $('.select2-filter').val('all').trigger('change');
        }
        table.ajax.reload();
    });

    // 5. AJAX View Details Offcanvas Handler
    $(document).on('click', '.btn-view-order-details', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!id) return;

        $('#orderDetailsOffcanvasBody').html(
            '<div class="text-center py-5">' +
                '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ __("common.loading") }}...</span></div>' +
                '<div class="mt-2 text-muted small">{{ __("common.loading") }}...</div>' +
            '</div>'
        );

        if (offcanvasInstance) {
            offcanvasInstance.show();
        }

        var url = '{{ route("procurement.reports.orders.details", ":id") }}'.replace(':id', id);

        $.ajax({
            url: url,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(html) {
                $('#orderDetailsOffcanvasBody').html(html);
            },
            error: function(xhr) {
                var msg = 'Failed to load purchase order details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (window.TempleUI && window.TempleUI.toast) {
                    TempleUI.toast.error(msg);
                }
                $('#orderDetailsOffcanvasBody').html(
                    '<div class="alert alert-danger m-3"><i class="bi bi-exclamation-triangle me-2"></i>' + msg + '</div>'
                );
            }
        });
    });
});
</script>
@endpush
