@extends('layouts.app')

@section('title', __('finance.expense_report'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-graph-down-arrow text-danger me-2"></i>{{ __('finance.expense_report') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('finance.expense_report_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> {{ __('finance.print_report') }}
            </button>
        </div>
    </div>

    <!-- Summary Performance Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-danger h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('finance.paid_posted_expense_total') }}</div>
                    <div class="h3 mb-0 fw-bold text-danger" id="cardPaidTotal">
                        {{ $summary['paid_total_formatted'] }}
                    </div>
                    <small class="text-muted">{{ __('finance.paid_expense_help') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('finance.pending_approval_amount') }}</div>
                    <div class="h3 mb-0 fw-bold text-warning" id="cardPendingAmount">
                        {{ $summary['pending_amount_formatted'] }}
                    </div>
                    <small class="text-muted">{{ __('finance.pending_expense_help') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('finance.number_of_transactions') }}</div>
                    <div class="h3 mb-0 fw-bold text-primary" id="cardTransactionCount">
                        {{ $summary['transaction_count_formatted'] }}
                    </div>
                    <small class="text-muted">{{ __('finance.total_expense_help') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-funnel me-1 text-primary"></i> {{ __('common.filter') }}
            </h6>
        </div>
        <div class="card-body">
            <form id="expenseFilterForm" class="row g-3">
                <!-- Payee/Person Select2 AJAX -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.payee_user_details') }}</label>
                    <select name="person_id" id="filterPerson" class="form-select form-select-sm" data-placeholder="{{ __('finance.select_payee') }}">
                        <option value="">-- {{ __('common.all') }} --</option>
                    </select>
                </div>

                <!-- Expense Category -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.expense_category') }}</label>
                    <select name="expense_category_id" id="filterCategory" class="form-select form-select-sm select2-basic">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        @foreach($expenseCategories as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->account_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Event -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('events.event') }}</label>
                    <select name="event_id" id="filterEvent" class="form-select form-select-sm select2-basic">
                        <option value="all">-- {{ __('finance.all_events') }} --</option>
                        <option value="general">{{ __('finance.general_temple_fund') }}</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">
                                {{ $event->title_en }} ({{ $event->category?->name_en ?? 'Event' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('common.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select form-select-sm">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        <option value="approved">{{ __('finance.approved') }}</option>
                        <option value="pending_approval">{{ __('finance.pending_approval') }}</option>
                        <option value="rejected">{{ __('finance.rejected') }}</option>
                        <option value="reversed">{{ __('finance.reversed') }}</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" id="filterStartDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" id="filterEndDate" class="form-control form-control-sm">
                </div>

                <!-- Payment Method -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('finance.payment_method') }}</label>
                    <select name="payment_method" id="filterPaymentMethod" class="form-select form-select-sm">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        <option value="cash">{{ __('donations.method_cash') }}</option>
                        <option value="bank_transfer">{{ __('donations.method_bank_transfer') }}</option>
                        <option value="bkash">{{ __('donations.method_bkash') }}</option>
                        <option value="nagad">{{ __('donations.method_nagad') }}</option>
                        <option value="rocket">{{ __('donations.method_rocket') }}</option>
                        <option value="cheque">{{ __('donations.method_cheque') }}</option>
                        <option value="other">{{ __('donations.method_other') }}</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('common.search') }}</label>
                    <input type="text" name="search" id="filterSearch" class="form-control form-control-sm" placeholder="{{ __('common.search') }}">
                </div>

                <!-- Actions -->
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilters">
                        <i class="bi bi-filter me-1"></i>{{ __('finance.apply_filters') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetFilters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expense Report DataTable Card -->
    <!-- STRICTLY NO ROW NUMBER / # COLUMN -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle w-100" id="expenseReportTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('finance.payee_user_details') }}</th>
                            <th>{{ __('common.title') }} / {{ __('finance.expense_category') }}</th>
                            <th>{{ __('events.event') }}</th>
                            <th class="text-end">{{ __('common.amount') }}</th>
                            <th>{{ __('finance.entry_date') }}</th>
                            <th class="text-center">{{ __('common.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Reusable Bootstrap 5 Offcanvas for Details -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="reportDetailsOffcanvas" aria-labelledby="reportDetailsOffcanvasLabel" style="width: 580px; max-width: 94vw;">
    <div class="offcanvas-header bg-light border-bottom">
        <div>
            <h5 class="offcanvas-title fw-bold text-dark" id="reportDetailsOffcanvasLabel">
                <i class="bi bi-receipt text-danger me-2"></i>{{ __('finance.transaction_info') }}
            </h5>
            <span class="text-muted small">{{ __('finance.expense_report') }} &bull; {{ __('finance.live_audit_trail') }}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3" id="reportDetailsOffcanvasBody">
        <!-- Dynamic AJAX Content -->
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // 1. Initialize Select2 for payee/person search
    if ($.fn.select2) {
        $('#filterPerson').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: '{{ __("finance.select_payee") }}',
            ajax: {
                url: '{{ route("finance.reports.people-search") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term || '', context: 'expense' };
                },
                processResults: function(data) {
                    return { results: data.results || [] };
                },
                cache: true
            }
        });

        $('.select2-basic').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        });
    }

    // 2. Offcanvas instance
    var offcanvasEl = document.getElementById('reportDetailsOffcanvas');
    var offcanvasInstance = offcanvasEl ? new bootstrap.Offcanvas(offcanvasEl) : null;

    // 3. DataTable initialization — STRICTLY 7 COLUMNS, NO ROW NUMBER / # COLUMN
    var table = TempleUI.initDataTable('#expenseReportTable', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("finance.reports.expenses") }}',
            data: function(d) {
                d.person_id = $('#filterPerson').val();
                d.expense_category_id = $('#filterCategory').val();
                d.event_id = $('#filterEvent').val();
                d.status = $('#filterStatus').val();
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
                d.payment_method = $('#filterPaymentMethod').val();
                d.search = $('#filterSearch').val();
            },
            dataSrc: function(json) {
                // Update live summary cards dynamically
                if (json.summary) {
                    $('#cardPaidTotal').text(json.summary.paid_total_formatted);
                    $('#cardPendingAmount').text(json.summary.pending_amount_formatted);
                    $('#cardTransactionCount').text(json.summary.transaction_count_formatted);
                }
                return json.data;
            }
        },
        columns: [
            { data: 'payee_details', name: 'vendor_name', orderable: true },
            { data: 'purpose', name: 'title_en', orderable: true },
            { data: 'event', name: 'event_id', orderable: true },
            { data: 'amount', name: 'amount', orderable: true, className: 'text-end fw-bold text-danger' },
            { data: 'date', name: 'expense_date', orderable: true },
            { data: 'status', name: 'status', orderable: true, className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end pe-3' }
        ],
        order: [[4, 'desc']],
        pageLength: 15,
        lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
        dom: '<"row align-items-center mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "{{ __('common.search') }}",
            paginate: {
                previous: "{{ __('datatables.paginate.previous') }}",
                next: "{{ __('datatables.paginate.next') }}"
            }
        }
    });

    // 4. Filter form handlers
    $('#expenseFilterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function() {
        $('#expenseFilterForm')[0].reset();
        if ($.fn.select2) {
            $('#filterPerson').val(null).trigger('change');
            $('#filterCategory').val('all').trigger('change');
            $('#filterEvent').val('all').trigger('change');
        }
        table.ajax.reload();
    });

    // 5. AJAX View Details Offcanvas Handler
    $(document).on('click', '.btn-view-expense-details', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var id = $btn.data('id');

        if (!id) return;

        // Show loading spinner in offcanvas
        $('#reportDetailsOffcanvasBody').html(
            '<div class="text-center py-5">' +
                '<div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div>' +
                '<div class="mt-2 text-muted small">{{ __("common.loading") }}...</div>' +
            '</div>'
        );

        if (offcanvasInstance) {
            offcanvasInstance.show();
        }

        // Fetch partial HTML
        var url = '{{ route("finance.reports.expenses.details", ":id") }}'.replace(':id', id);

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(html) {
                $('#reportDetailsOffcanvasBody').html(html);
            },
            error: function(xhr) {
                var msg = 'Failed to load details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                if (window.TempleUI && window.TempleUI.toast) {
                    TempleUI.toast.error(msg);
                }
                $('#reportDetailsOffcanvasBody').html(
                    '<div class="alert alert-danger m-3"><i class="bi bi-exclamation-triangle me-2"></i>' + msg + '</div>'
                );
            }
        });
    });
});
</script>
@endpush
