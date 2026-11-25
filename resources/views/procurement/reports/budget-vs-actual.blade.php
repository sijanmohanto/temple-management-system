@extends('layouts.app')

@section('title', __('procurement.budget_vs_actual'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-graph-up text-primary me-2"></i>{{ __('procurement.budget_vs_actual') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('procurement.variance_analysis') }} &bull; {{ __('procurement.increment_status') }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-dark rounded-pill px-3 shadow-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> {{ __('procurement.print_report') }}
            </button>
            <a href="{{ route('procurement.budgets.index') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-list-columns-reverse me-1"></i> {{ __('procurement.budget_list') }}
            </a>
        </div>
    </div>

    <!-- Budget Selection Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('procurement.reports.budget-vs-actual') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label small fw-semibold text-muted mb-1">{{ __('procurement.select_budget') }}</label>
                    <select name="budget_id" class="form-select select2-basic" onchange="this.form.submit()">
                        @foreach($approvedBudgets as $b)
                            <option value="{{ $b->id }}" {{ $selectedBudget && $selectedBudget->id === $b->id ? 'selected' : '' }}>
                                {{ $b->title }} ({{ $b->type_label }} &bull; {{ $b->event?->title_en ?? $b->fiscal_year }}) &bull; ৳{{ number_format((float) $b->total_amount, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 flex-grow-1 shadow-sm">
                        <i class="bi bi-filter me-1"></i> {{ __('common.filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($reportData)
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-primary h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.approved_budget_total') }}</div>
                        <div class="h3 mb-0 fw-bold text-primary">{{ $reportData['summary']['total_budget_formatted'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-danger h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.actual_expenses') }}</div>
                        <div class="h3 mb-0 fw-bold text-danger">{{ $reportData['summary']['total_actual_formatted'] }}</div>
                        <small class="text-muted">{{ __('finance.approved') }} {{ __('finance.expenses') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 border-start border-4 {{ $reportData['summary']['is_over_budget'] ? 'border-danger' : 'border-success' }} h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="text-muted small fw-bold text-uppercase">
                            {{ $reportData['summary']['is_over_budget'] ? __('procurement.over_budget') : __('procurement.remaining_budget') }}
                        </div>
                        <div class="h3 mb-0 fw-bold {{ $reportData['summary']['is_over_budget'] ? 'text-danger' : 'text-success' }}">
                            {{ $reportData['summary']['is_over_budget'] ? $reportData['summary']['total_over_budget_formatted'] : $reportData['summary']['total_remaining_formatted'] }}
                        </div>
                        <small class="text-muted">{{ $reportData['summary']['overall_utilization_pct'] }}% {{ __('procurement.utilization_percentage') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-warning h-100">
                    <div class="card-body p-3 p-md-4">
                        <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.pending_expenses') }}</div>
                        <div class="h3 mb-0 fw-bold text-warning">{{ $reportData['summary']['total_pending_formatted'] }}</div>
                        <small class="text-muted">{{ __('finance.pending_approval') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Variance Table Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-table me-2 text-primary"></i>{{ __('procurement.variance_analysis') }}
                    </h6>
                    <small class="text-muted">
                        {{ $selectedBudget->title }}
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small fw-bold text-muted">{{ __('procurement.utilization_percentage') }}:</span>
                    <div class="progress" style="width: 140px; height: 10px;">
                        <div class="progress-bar {{ $reportData['summary']['is_over_budget'] ? 'bg-danger' : 'bg-primary' }}" style="width: {{ min(100, $reportData['summary']['overall_utilization_pct']) }}%;"></div>
                    </div>
                    <span class="small fw-bold {{ $reportData['summary']['is_over_budget'] ? 'text-danger' : 'text-primary' }}">{{ $reportData['summary']['overall_utilization_pct'] }}%</span>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="budgetVsActualTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('procurement.account_head_short') }}</th>
                                <th class="text-end">{{ __('procurement.allocated_budget_short') }}</th>
                                <th class="text-end">{{ __('procurement.actual_expenses_short') }}</th>
                                <th class="text-end">{{ __('procurement.pending_expenses_short') }}</th>
                                <th class="text-end">{{ __('procurement.remaining_budget_short') }}</th>
                                <th class="text-end">{{ __('procurement.over_budget_short') }}</th>
                                <th class="text-center pe-3" style="width: 20%;">{{ __('procurement.utilization_short') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportData['items'] as $row)
                                <tr class="{{ $row['is_unbudgeted'] ? 'table-warning' : '' }}">
                                    <td class="ps-3">
                                        <div class="fw-bold">{{ $row['account_name'] }}</div>
                                        <span class="d-none">{{ $row['account_code'] }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-primary" data-order="{{ $row['allocated'] }}">{{ $row['allocated_formatted'] }}</td>
                                    <td class="text-end fw-bold text-danger" data-order="{{ $row['actual'] }}">{{ $row['actual_formatted'] }}</td>
                                    <td class="text-end text-warning" data-order="{{ $row['pending'] }}">{{ $row['pending_formatted'] }}</td>
                                    <td class="text-end fw-bold text-success" data-order="{{ $row['remaining'] }}">{{ $row['remaining_formatted'] }}</td>
                                    <td class="text-end fw-bold {{ $row['is_over_budget'] ? 'text-danger' : 'text-muted' }}" data-order="{{ $row['over_budget'] }}">
                                        {{ $row['is_over_budget'] ? '+' . $row['over_budget_formatted'] : '-' }}
                                    </td>
                                    <td class="pe-3" data-order="{{ $row['utilization_pct'] }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar {{ $row['is_over_budget'] ? 'bg-danger' : 'bg-primary' }}" style="width: {{ min(100, $row['utilization_pct']) }}%;"></div>
                                            </div>
                                            <small class="fw-semibold text-muted" style="font-size: 0.75rem;">{{ $row['utilization_pct'] }}%</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold fs-6">
                            <tr>
                                <td class="ps-3">{{ __('common.total') }}:</td>
                                <td class="text-end text-primary">{{ $reportData['summary']['total_budget_formatted'] }}</td>
                                <td class="text-end text-danger">{{ $reportData['summary']['total_actual_formatted'] }}</td>
                                <td class="text-end text-warning">{{ $reportData['summary']['total_pending_formatted'] }}</td>
                                <td class="text-end text-success">{{ $reportData['summary']['total_remaining_formatted'] }}</td>
                                <td class="text-end text-danger">{{ $reportData['summary']['is_over_budget'] ? '+' . $reportData['summary']['total_over_budget_formatted'] : '-' }}</td>
                                <td class="text-center pe-3">{{ $reportData['summary']['overall_utilization_pct'] }}%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info py-4 text-center rounded-4 shadow-sm">
            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
            {{ __('procurement.no_budgets_found') }}
            <div class="mt-2">
                @if(Auth::user()->hasPermission('manage_budgets'))
                    <a href="{{ route('procurement.budgets.create') }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> {{ __('procurement.create_budget') }}
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2-basic').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    TempleUI.initDataTable('#budgetVsActualTable', {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [],
        columns: [
            { orderable: true, className: 'ps-3' },
            { orderable: true, className: 'text-end' },
            { orderable: true, className: 'text-end' },
            { orderable: true, className: 'text-end' },
            { orderable: true, className: 'text-end' },
            { orderable: true, className: 'text-end' },
            { orderable: true, className: 'pe-3' }
        ]
    });
});
</script>
@endpush
