@extends('layouts.app')

@section('title', __('finance.expenses'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.expenses') }}</h1>
            <p class="text-muted small mb-0">Record, approve and review operational, puja, and maintenance expenditures</p>
        </div>
        <div>
            @if(Auth::user()->hasPermission('record_expenses'))
                <a href="{{ route('finance.expenses.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('finance.record_expense') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="expenseFilterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select form-select-sm">
                        <option value="">-- {{ __('common.all') }} --</option>
                        <option value="pending_approval" {{ in_array(request('status'), ['pending', 'pending_approval']) ? 'selected' : '' }}>{{ __('finance.pending') }}</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('finance.approved') }}</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('finance.rejected') }}</option>
                        <option value="reversed" {{ request('status') === 'reversed' ? 'selected' : '' }}>{{ __('finance.reversed') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('events.event') }}</label>
                    <select name="event_id" id="filterEvent" class="form-select form-select-sm">
                        <option value="">-- {{ __('common.all') }} --</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->title_en }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" id="filterStartDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" id="filterEndDate" class="form-control form-control-sm">
                </div>
            </form>
        </div>
    </div>

    <!-- Expenses DataTable -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="expensesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('common.title') }}</th>
                            <th>{{ __('finance.expense_category_short') }}</th>
                            <th>{{ __('finance.paid_from_short') }}</th>
                            <th>{{ __('finance.payee_vendor_short') }}</th>
                            <th class="text-end">{{ __('common.amount_short') }}</th>
                            <th class="text-center">{{ __('common.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modals for DataTable Row Actions -->
    <div class="modal fade" id="listApproveModal" tabindex="-1" aria-labelledby="listApproveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="listApproveForm" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-success" id="listApproveModalLabel">
                            <i class="bi bi-check2-circle me-1"></i> {{ __('finance.approve_expense') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">{{ __('finance.confirm_approve_expense') }}</p>
                        <div class="alert alert-light border mb-3">
                            <div class="fw-bold" id="listApproveNumber"></div>
                            <div class="h5 fw-bold text-danger mb-1 mt-1" id="listApproveAmount"></div>
                            <div class="small text-muted">{{ __('finance.approve_expense_notice') }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('finance.approval_notes') }}</label>
                            <input type="text" name="note" class="form-control" placeholder="Optional audit memo...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-success" id="btnListApproveSubmit">
                            <i class="bi bi-check-lg me-1"></i> {{ __('finance.approve_expense') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="listRejectModal" tabindex="-1" aria-labelledby="listRejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="listRejectForm" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger" id="listRejectModalLabel">
                            <i class="bi bi-x-circle me-1"></i> {{ __('finance.reject_expense') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">{{ __('finance.confirm_reject_expense') }}</p>
                        <div class="alert alert-light border mb-3">
                            <div class="fw-bold" id="listRejectNumber"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required fw-bold">{{ __('finance.rejection_reason') }}</label>
                            <textarea name="reason" id="listRejectReason" class="form-control" rows="3" required minlength="3" maxlength="500" placeholder="{{ __('finance.rejection_reason_placeholder') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-danger" id="btnListRejectSubmit">
                            <i class="bi bi-x-lg me-1"></i> {{ __('finance.reject_expense') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="listReverseModal" tabindex="-1" aria-labelledby="listReverseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="listReverseForm" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger" id="listReverseModalLabel">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('finance.reverse_entry') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning small">
                            {{ __('finance.reversal_warning') }}
                        </div>
                        <div class="alert alert-light border mb-3">
                            <div class="fw-bold" id="listReverseNumber"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required fw-bold">{{ __('finance.reversal_reason') }}</label>
                            <textarea name="reason" id="listReverseReason" class="form-control" rows="3" required minlength="5" maxlength="500" placeholder="{{ __('finance.reversal_reason_placeholder') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-danger" id="btnListReverseSubmit">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('finance.reverse_entry') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = TempleUI.initDataTable('#expensesTable', {
        processing: true,
        serverSide: true,
        pageLength: 25,
        ajax: {
            url: "{{ route('finance.expenses.index') }}",
            data: function (d) {
                d.status = $('#filterStatus').val();
                d.event_id = $('#filterEvent').val();
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
            }
        },
        columns: [
            { 
                data: 'title', 
                name: 'title_en',
                className: 'ps-3',
                render: function(data, type, row) {
                    var titleText = row.display_title || data || '—';
                    var html = '<a href="' + row.view_url + '" class="text-decoration-none fw-semibold text-dark hover-primary">' + titleText + '</a>';
                    if (row.event_name) {
                        html += '<div class="mt-1"><span class="badge bg-light text-secondary border font-xs"><i class="bi bi-calendar-event me-1"></i>' + row.event_name + '</span></div>';
                    }
                    html += '<span class="d-none">' + row.expense_number + '</span>';
                    return html;
                }
            },
            { 
                data: 'category', 
                name: 'expense_account_id',
                render: function(data) {
                    return '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">' + (data || '—') + '</span>';
                }
            },
            { 
                data: 'paid_from', 
                name: 'payment_account_id',
                render: function(data) {
                    return '<span class="fw-semibold text-dark">' + (data || '—') + '</span>';
                }
            },
            { 
                data: 'vendor', 
                name: 'vendor_name',
                render: function(data) {
                    return data && data !== '-' ? '<span class="text-dark">' + data + '</span>' : '<span class="text-muted">—</span>';
                }
            },
            { 
                data: 'amount', 
                name: 'amount', 
                className: 'text-end fw-bold',
                render: function(data, type, row) { 
                    var displayDate = row.expense_date_formatted || row.expense_date || '';
                    return '<div class="text-danger fs-6 fw-bold mb-0">৳' + data + '</div>' +
                           '<div class="small text-muted fw-normal text-nowrap mt-1"><i class="bi bi-calendar3 me-1"></i>' + displayDate + '</div>';
                }
            },
            { 
                data: 'status', 
                name: 'status', 
                className: 'text-center',
                render: function(data) {
                    if (data === 'approved') {
                        return '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="bi bi-check-circle me-1"></i>{{ __("finance.approved") }}</span>';
                    } else if (data === 'pending' || data === 'pending_approval') {
                        return '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25"><i class="bi bi-clock me-1"></i>{{ __("finance.pending") }}</span>';
                    } else if (data === 'rejected') {
                        return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="bi bi-x-circle me-1"></i>{{ __("finance.rejected") }}</span>';
                    } else if (data === 'reversed') {
                        return '<span class="badge bg-secondary bg-opacity-10 text-secondary border"><i class="bi bi-arrow-counterclockwise me-1"></i>{{ __("finance.reversed") }}</span>';
                    }
                    return '<span class="badge bg-secondary">' + data + '</span>';
                }
            },
            { 
                data: null, 
                name: 'actions', 
                orderable: false, 
                searchable: false, 
                className: 'text-end text-nowrap pe-3',
                render: function(data, type, row) {
                    let html = '<div class="d-inline-flex gap-1 justify-content-end">';
                    
                    html += '<a href="' + row.view_url + '" class="table-action-btn table-action-btn-view" title="{{ __("common.view") }}"><i class="bi bi-eye"></i></a>';
                    
                    if (row.is_pending) {
                        if (row.can_manage) {
                            html += '<a href="' + row.edit_url + '" class="table-action-btn table-action-btn-edit" title="{{ __("common.edit") }}"><i class="bi bi-pencil"></i></a>';
                        }
                        if (row.can_approve) {
                            html += '<button type="button" class="table-action-btn table-action-btn-toggle-reactivate btn-approve-expense" data-id="' + row.id + '" data-number="' + row.expense_number + '" data-amount="' + row.amount + '" data-url="' + row.approve_url + '" title="{{ __("finance.approve_expense") }}"><i class="bi bi-check-lg"></i></button>';
                            html += '<button type="button" class="table-action-btn table-action-btn-delete btn-reject-expense" data-id="' + row.id + '" data-number="' + row.expense_number + '" data-url="' + row.reject_url + '" title="{{ __("finance.reject_expense") }}"><i class="bi bi-x-lg"></i></button>';
                        }
                    } else if (row.is_approved && row.can_reverse) {
                        html += '<button type="button" class="table-action-btn table-action-btn-delete btn-reverse-expense" data-id="' + row.id + '" data-number="' + row.expense_number + '" data-url="' + row.reverse_url + '" title="{{ __("finance.reverse_entry") }}"><i class="bi bi-arrow-counterclockwise"></i></button>';
                    }
                    
                    html += '</div>';
                    return html;
                }
            }
        ],
        order: [[4, 'desc']]
    });

    $('#filterStatus, #filterEvent, #filterStartDate, #filterEndDate').on('change', function() {
        table.draw();
    });

    // Handle Approve modal click
    $(document).on('click', '.btn-approve-expense', function() {
        var url = $(this).data('url');
        var number = $(this).data('number');
        var amount = $(this).data('amount');
        $('#listApproveForm').attr('action', url);
        $('#listApproveNumber').text(number);
        $('#listApproveAmount').text('৳' + amount);
        $('#listApproveModal').modal('show');
    });

    $('#listApproveForm').on('submit', function() {
        TempleUI.btnLoading('#btnListApproveSubmit', true);
    });

    // Handle Reject modal click
    $(document).on('click', '.btn-reject-expense', function() {
        var url = $(this).data('url');
        var number = $(this).data('number');
        $('#listRejectForm').attr('action', url);
        $('#listRejectNumber').text(number);
        $('#listRejectReason').val('');
        $('#listRejectModal').modal('show');
    });

    $('#listRejectForm').on('submit', function() {
        TempleUI.btnLoading('#btnListRejectSubmit', true);
    });

    // Handle Reverse modal click
    $(document).on('click', '.btn-reverse-expense', function() {
        var url = $(this).data('url');
        var number = $(this).data('number');
        $('#listReverseForm').attr('action', url);
        $('#listReverseNumber').text(number);
        $('#listReverseReason').val('');
        $('#listReverseModal').modal('show');
    });

    $('#listReverseForm').on('submit', function() {
        TempleUI.btnLoading('#btnListReverseSubmit', true);
    });
});
</script>
@endpush
