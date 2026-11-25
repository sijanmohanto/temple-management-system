@extends('layouts.app')

@section('title', __('procurement.purchase_requisitions_report_title'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">
                <i class="bi bi-clipboard-data text-primary me-2"></i>{{ __('procurement.purchase_requisitions_report_title') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('procurement.purchase_requisitions_report_subtitle') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('procurement.requests.index') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-card-checklist me-1"></i> {{ __('procurement.request_list') }}
            </a>
            @if(Auth::user()->hasAnyPermission(['export_procurement_reports', 'view_procurement_reports']))
            <a href="{{ route('procurement.reports.requests.export') }}" id="btnExportCsv" class="btn btn-outline-success rounded-pill px-3 shadow-sm" target="_blank">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('procurement.export_csv') }}
            </a>
            @endif
            <a href="{{ route('procurement.reports.requests.print') }}" id="btnPrintReport" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" target="_blank">
                <i class="bi bi-printer me-1"></i> {{ __('procurement.print_report') }}
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4" id="requestsSummaryCards">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-primary h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.total_requisitions') }}</span>
                    <div class="d-flex align-items-baseline justify-content-between mt-1">
                        <span class="h4 mb-0 fw-bold text-dark" id="summaryTotalCount">{{ number_format($summary['total_count']) }}</span>
                        <span class="small fw-bold text-primary" id="summaryTotalAmount">{{ $summary['total_amount_formatted'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-secondary h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.draft_requisitions') }}</span>
                    <div class="h4 mb-0 fw-bold mt-1 text-secondary" id="summaryDraftCount">{{ number_format($summary['draft_count']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.pending_approval_count') }}</span>
                    <div class="h4 mb-0 fw-bold mt-1 text-warning-emphasis" id="summaryPendingCount">{{ number_format($summary['pending_count']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.approved_requisitions') }}</span>
                    <div class="h4 mb-0 fw-bold mt-1 text-success" id="summaryApprovedCount">{{ number_format($summary['approved_count']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-danger h-100">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('procurement.rejected_or_cancelled') }}</span>
                    <div class="h4 mb-0 fw-bold mt-1 text-danger" id="summaryRejectedCancelledCount">{{ number_format($summary['rejected_or_cancelled_count']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form id="requisitionsFilterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" id="filterStartDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" id="filterEndDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select form-select-sm">
                        <option value="all">{{ __('procurement.all_statuses') }}</option>
                        <option value="draft">{{ __('procurement.status_draft') }}</option>
                        <option value="submitted">{{ __('procurement.status_submitted') }}</option>
                        <option value="approved">{{ __('procurement.status_approved') }}</option>
                        <option value="rejected">{{ __('procurement.status_rejected') }}</option>
                        <option value="cancelled">{{ __('procurement.status_cancelled') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.requested_by_short') }}</label>
                    <select name="requested_by" id="filterRequestedBy" class="form-select form-select-sm select2-filter">
                        <option value="all">{{ __('procurement.all_requesters') }}</option>
                        @foreach($requesters as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.approved_by_short') }}</label>
                    <select name="approved_by" id="filterApprovedBy" class="form-select form-select-sm select2-filter">
                        <option value="all">{{ __('procurement.all_approvers') }}</option>
                        @foreach($approvers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.min_amount') }}</label>
                    <input type="number" step="0.01" name="min_amount" id="filterMinAmount" class="form-control form-control-sm" placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">{{ __('procurement.max_amount') }}</label>
                    <input type="number" step="0.01" name="max_amount" id="filterMaxAmount" class="form-control form-control-sm" placeholder="0.00">
                </div>
                <div class="col-md-3">
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
                <table class="table table-hover align-middle mb-0 w-100" id="purchaseRequestsReportTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('procurement.purpose_short') }}</th>
                            <th>{{ __('procurement.requested_by_short') }}</th>
                            <th>{{ __('procurement.request_date_short') }}</th>
                            <th>{{ __('procurement.required_date_short') }}</th>
                            <th class="text-end">{{ __('procurement.estimated_amount_short') }}</th>
                            <th class="text-center">{{ __('procurement.status') }}</th>
                            <th>{{ __('procurement.approved_by_short') }}</th>
                            <th>{{ __('procurement.approval_date_short') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Requisition Details Bootstrap Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="requestDetailsOffcanvas" aria-labelledby="requestDetailsOffcanvasLabel" style="width: 650px; max-width: 95vw;">
    <div class="offcanvas-header bg-light border-bottom">
        <div>
            <h5 class="offcanvas-title fw-bold text-dark" id="requestDetailsOffcanvasLabel">
                <i class="bi bi-clipboard-check text-primary me-2"></i>{{ __('procurement.requisition_details') }}
            </h5>
            <span class="text-muted small">{{ __('procurement.procurement_reports') }} &bull; {{ __('procurement.increment_status') }}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3 p-md-4" id="requestDetailsOffcanvasBody">
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
    var offcanvasEl = document.getElementById('requestDetailsOffcanvas');
    var offcanvasInstance = offcanvasEl ? new bootstrap.Offcanvas(offcanvasEl) : null;

    // Helper to get active filter parameters
    function getFilterParams() {
        return {
            start_date: $('#filterStartDate').val(),
            end_date: $('#filterEndDate').val(),
            status: $('#filterStatus').val(),
            requested_by: $('#filterRequestedBy').val(),
            approved_by: $('#filterApprovedBy').val(),
            min_amount: $('#filterMinAmount').val(),
            max_amount: $('#filterMaxAmount').val(),
            search: $('#filterSearch').val()
        };
    }

    // 3. DataTable initialization — STRICTLY 9 COLUMNS, NO # COLUMN
    var table = TempleUI.initDataTable('#purchaseRequestsReportTable', {
        processing: true,
        serverSide: true,
        pageLength: 10,
        order: [[2, 'desc']], // order by request_date desc
        ajax: {
            url: '{{ route("procurement.reports.requests") }}',
            data: function(d) {
                var filters = getFilterParams();
                $.extend(d, filters);
            }
        },
        columns: [
            { data: 'title', name: 'title' },
            { data: 'requested_by', name: 'requested_by' },
            { data: 'request_date', name: 'request_date' },
            { data: 'required_date', name: 'required_date' },
            { data: 'total_estimated_amount', name: 'total_estimated_amount', className: 'text-end' },
            { data: 'status', name: 'status', className: 'text-center' },
            { data: 'approved_by', name: 'approved_by' },
            { data: 'approval_date', name: 'approval_date' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end pe-3' }
        ],
        drawCallback: function(settings) {
            // Update summary cards dynamically from server response
            var json = settings.json;
            if (json && json.summary) {
                $('#summaryTotalCount').text(Number(json.summary.total_count || 0).toLocaleString());
                $('#summaryTotalAmount').text(json.summary.total_amount_formatted || '৳0.00');
                $('#summaryDraftCount').text(Number(json.summary.draft_count || 0).toLocaleString());
                $('#summaryPendingCount').text(Number(json.summary.pending_count || 0).toLocaleString());
                $('#summaryApprovedCount').text(Number(json.summary.approved_count || 0).toLocaleString());
                $('#summaryRejectedCancelledCount').text(Number(json.summary.rejected_or_cancelled_count || 0).toLocaleString());
            }

            // Sync export and print links with current filters
            var queryStr = $.param(getFilterParams());
            var baseExportUrl = '{{ route("procurement.reports.requests.export") }}';
            var basePrintUrl = '{{ route("procurement.reports.requests.print") }}';
            $('#btnExportCsv').attr('href', baseExportUrl + '?' + queryStr);
            $('#btnPrintReport').attr('href', basePrintUrl + '?' + queryStr);
        }
    });

    // 4. Filter form submissions
    $('#requisitionsFilterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function() {
        $('#requisitionsFilterForm')[0].reset();
        if ($.fn.select2) {
            $('.select2-filter').val('all').trigger('change');
        }
        table.ajax.reload();
    });

    // 5. AJAX View Details Offcanvas Handler
    $(document).on('click', '.btn-view-request-details', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!id) return;

        $('#requestDetailsOffcanvasBody').html(
            '<div class="text-center py-5">' +
                '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ __("common.loading") }}...</span></div>' +
                '<div class="mt-2 text-muted small">{{ __("common.loading") }}...</div>' +
            '</div>'
        );

        if (offcanvasInstance) {
            offcanvasInstance.show();
        }

        var url = '{{ route("procurement.reports.requests.details", ":id") }}'.replace(':id', id);

        $.ajax({
            url: url,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(html) {
                $('#requestDetailsOffcanvasBody').html(html);
            },
            error: function(xhr) {
                var msg = 'Failed to load requisition details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (window.TempleUI && window.TempleUI.toast) {
                    TempleUI.toast.error(msg);
                }
                $('#requestDetailsOffcanvasBody').html(
                    '<div class="alert alert-danger m-3"><i class="bi bi-exclamation-triangle me-2"></i>' + msg + '</div>'
                );
            }
        });
    });
});
</script>
@endpush
