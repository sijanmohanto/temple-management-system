@extends('layouts.app')

@section('title', __('finance.journal_entries'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.journal_entries') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.reversal_warning') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if(Auth::user()->hasPermission('view_financial_reports'))
                <a href="{{ route('finance.reports.trial-balance') }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('finance.trial_balance') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.source_type') }}</label>
                    <select name="source_type" id="filterSource" class="form-select form-select-sm">
                        <option value="">-- {{ __('common.all') }} --</option>
                        <option value="collection_payment">{{ __('donations.collections') }}</option>
                        <option value="expense">{{ __('finance.expenses') }}</option>
                        <option value="fund_transfer">{{ __('finance.fund_transfers') }}</option>
                        <option value="manual_income">{{ __('finance.misc_income') }}</option>
                        <option value="opening_balance">{{ __('finance.opening_balances') }}</option>
                        <option value="reversal">{{ __('finance.reversed') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('finance.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select form-select-sm">
                        <option value="">-- {{ __('common.all') }} --</option>
                        <option value="posted">{{ __('finance.posted') }}</option>
                        <option value="reversed">{{ __('finance.reversed') }}</option>
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
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" id="filterStartDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" id="filterEndDate" class="form-control form-control-sm">
                </div>
            </form>
        </div>
    </div>

    <!-- Journals DataTable -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="journalsTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('finance.journal_number') }}</th>
                            <th>{{ __('finance.entry_date') }}</th>
                            <th>{{ __('common.description') }}</th>
                            <th>{{ __('finance.source_type') }}</th>
                            <th class="text-end">{{ __('finance.total_debit') }}</th>
                            <th class="text-end">{{ __('finance.total_credit') }}</th>
                            <th class="text-center">{{ __('finance.status') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = TempleUI.initDataTable('#journalsTable', {
        processing: true,
        serverSide: true,
        pageLength: 25,
        ajax: {
            url: "{{ route('finance.journals.index') }}",
            data: function (d) {
                d.source_type = $('#filterSource').val();
                d.status = $('#filterStatus').val();
                d.event_id = $('#filterEvent').val();
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
            }
        },
        columns: [
            { 
                data: 'journal_number', 
                name: 'journal_number',
                render: function(data, type, row) {
                    return '<a href="' + row.view_url + '" class="fw-bold font-monospace text-decoration-none">' + data + '</a>';
                }
            },
            { data: 'entry_date', name: 'entry_date' },
            { 
                data: 'description', 
                name: 'description',
                render: function(data) {
                    return '<div class="text-truncate" style="max-width: 300px;" title="' + (data || '') + '">' + (data || '-') + '</div>';
                }
            },
            { 
                data: 'source_type', 
                name: 'source_type',
                render: function(data) {
                    return '<span class="badge bg-light text-dark border">' + data + '</span>';
                }
            },
            { 
                data: 'total_debit', 
                name: 'total_debit', 
                className: 'text-end fw-bold text-success',
                render: function(data) { return '৳' + data; }
            },
            { 
                data: 'total_credit', 
                name: 'total_credit', 
                className: 'text-end fw-bold text-primary',
                render: function(data) { return '৳' + data; }
            },
            { 
                data: 'status', 
                name: 'status', 
                className: 'text-center',
                render: function(data) {
                    if (data === 'posted') {
                        return '<span class="badge bg-success">{{ __("finance.posted") }}</span>';
                    } else if (data === 'reversed') {
                        return '<span class="badge bg-danger">{{ __("finance.reversed") }}</span>';
                    }
                    return '<span class="badge bg-secondary">' + data + '</span>';
                }
            },
            { 
                data: 'view_url', 
                name: 'actions', 
                orderable: false, 
                searchable: false, 
                className: 'text-end',
                render: function(data) {
                    return '<a href="' + data + '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>';
                }
            }
        ],
        order: [[1, 'desc']]
    });

    $('#filterSource, #filterStatus, #filterEvent, #filterStartDate, #filterEndDate').on('change', function() {
        table.draw();
    });
});
</script>
@endpush
