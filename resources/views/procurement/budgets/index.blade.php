@extends('layouts.app')

@section('title', __('procurement.budget_list'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-calculator text-primary me-2"></i>{{ __('procurement.budget_list') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('procurement.procurement_preview_desc') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if(Auth::user()->hasPermission('manage_budgets'))
                <a href="{{ route('procurement.budgets.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('procurement.create_budget') }}
                </a>
            @endif
            <a href="{{ route('procurement.reports.budget-vs-actual') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-graph-up me-1"></i> {{ __('procurement.budget_vs_actual') }}
            </a>
        </div>
    </div>

    <!-- Summary Performance Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.total_budget') }}</div>
                    <div class="h3 mb-0 fw-bold text-primary">{{ $summary['total_budgets'] }}</div>
                    <small class="text-muted">{{ __('procurement.budgets') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.approved_budget_total') }}</div>
                    <div class="h3 mb-0 fw-bold text-success">{{ $summary['total_approved_amount'] }}</div>
                    <small class="text-muted">{{ $summary['approved_budgets'] }} {{ __('procurement.status_approved') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.pending_budget_total') }}</div>
                    <div class="h3 mb-0 fw-bold text-warning">{{ $summary['pending_budgets'] }}</div>
                    <small class="text-muted">{{ __('procurement.status_submitted') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.increment_status') }}</div>
                    <div class="small fw-semibold text-dark mt-1">v1.6 (Phase 6)</div>
                    <small class="text-muted">{{ __('procurement.under_development') }}</small>
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
            <form id="budgetFilterForm" class="row g-3">
                <!-- Status -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('common.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select form-select-sm">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        <option value="draft">{{ __('procurement.status_draft') }}</option>
                        <option value="submitted">{{ __('procurement.status_submitted') }}</option>
                        <option value="approved">{{ __('procurement.status_approved') }}</option>
                        <option value="rejected">{{ __('procurement.status_rejected') }}</option>
                        <option value="cancelled">{{ __('procurement.status_cancelled') }}</option>
                    </select>
                </div>

                <!-- Budget Type -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('procurement.budget_type') }}</label>
                    <select name="budget_type" id="filterType" class="form-select form-select-sm">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        <option value="annual">{{ __('procurement.type_annual') }}</option>
                        <option value="event">{{ __('procurement.type_event') }}</option>
                        <option value="general_fund">{{ __('procurement.type_general_fund') }}</option>
                    </select>
                </div>

                <!-- Fiscal Year -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('procurement.fiscal_year') }}</label>
                    <select name="fiscal_year" id="filterYear" class="form-select form-select-sm">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ $yr == now()->year ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Event Filter -->
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('events.event') }}</label>
                    <select name="event_id" id="filterEvent" class="form-select form-select-sm select2-basic">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">
                                {{ $event->title_en }} ({{ $event->category?->name_en ?? 'Event' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input & Actions -->
                <div class="col-md-8">
                    <label class="form-label small fw-bold">{{ __('common.search') }}</label>
                    <input type="text" name="search" id="filterSearch" class="form-control form-control-sm" placeholder="{{ __('common.search') }}...">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilters">
                        <i class="bi bi-filter me-1"></i>{{ __('common.filter') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetFilters">
                        <i class="bi bi-arrow-counterclockwise"></i> {{ __('common.reset') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable Card (Strictly NO Row Number / # Column) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle w-100" id="budgetTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('procurement.budget_code') }}</th>
                            <th>{{ __('common.title') }}</th>
                            <th>{{ __('procurement.budget_type') }}</th>
                            <th>{{ __('procurement.scope') }}</th>
                            <th class="text-end">{{ __('procurement.total_budget') }}</th>
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2-basic').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        });
    }

    var table = TempleUI.initDataTable('#budgetTable', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("procurement.budgets.index") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.budget_type = $('#filterType').val();
                d.fiscal_year = $('#filterYear').val();
                d.event_id = $('#filterEvent').val();
                d.search = $('#filterSearch').val();
            }
        },
        columns: [
            { data: 'budget_code', name: 'budget_code', orderable: true },
            { data: 'title', name: 'title_en', orderable: true },
            { data: 'budget_type', name: 'budget_type', orderable: true },
            { data: 'scope_period', name: 'fiscal_year', orderable: true },
            { data: 'total_amount', name: 'total_amount', orderable: true, className: 'text-end fw-bold text-primary' },
            { data: 'status', name: 'status', orderable: true, className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end pe-3' }
        ],
        order: [[0, 'desc']],
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

    $('#budgetFilterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function() {
        $('#budgetFilterForm')[0].reset();
        if ($.fn.select2) {
            $('#filterEvent').val('all').trigger('change');
        }
        table.ajax.reload();
    });
});
</script>
@endpush
