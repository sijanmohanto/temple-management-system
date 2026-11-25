@extends('layouts.app')

@section('title', __('finance.income_expense_statement'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('finance.income_expense_statement') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.income_expense_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('finance.finance_dashboard') }}
            </a>
            <button type="button" class="btn btn-outline-dark rounded-pill px-3 shadow-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> {{ __('finance.print_report') }}
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('finance.reports.income-expense') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">{{ __('events.event') }} ({{ __('common.optional') ?? 'Optional' }})</label>
                    <select name="event_id" class="form-select select2-basic">
                        <option value="">-- {{ __('finance.all_events') }} --</option>
                        @foreach($events as $ev)
                            <option value="{{ $ev->id }}" {{ $eventId == $ev->id ? 'selected' : '' }}>
                                {{ $ev->title_en }} ({{ $ev->category?->name_en }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill px-3 flex-grow-1 shadow-sm">
                        <i class="bi bi-filter me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('finance.reports.income-expense') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Performance Banner -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-success">
                <div class="card-body p-3 p-md-4">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('finance.period_income') }}</div>
                    <div class="h3 mb-0 fw-bold text-success">৳{{ number_format($report['total_income'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 border-danger">
                <div class="card-body p-3 p-md-4">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('finance.period_expense') }}</div>
                    <div class="h3 mb-0 fw-bold text-danger">৳{{ number_format($report['total_expense'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-4 {{ $report['net_surplus'] >= 0 ? 'border-primary' : 'border-danger' }}">
                <div class="card-body p-3 p-md-4">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('finance.net_surplus_deficit') }}</div>
                    <div class="h3 mb-0 fw-bold {{ $report['net_surplus'] >= 0 ? 'text-primary' : 'text-danger' }}">
                        ৳{{ number_format($report['net_surplus'], 2) }}
                        <span class="fs-6 fw-normal">({{ $report['net_surplus'] >= 0 ? __('finance.surplus') : __('finance.deficit') }})</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Incomes Table -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-bottom border-success border-2 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-success d-flex align-items-center">
                        <i class="bi bi-graph-up-arrow me-2"></i>{{ __('finance.income') }}
                    </h5>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">{{ count($report['incomes']) }}</span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="incomeStatementTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ __('finance.category_title') }}</th>
                                    <th class="text-end pe-3">{{ __('common.amount') }} (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['incomes'] as $row)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold">{{ $row['name_en'] }}</div>
                                            @if(!empty($row['name_bn']))
                                                <div class="text-muted small">{{ $row['name_bn'] }}</div>
                                            @endif
                                            <span class="d-none">{{ $row['code'] }}</span>
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-success">৳{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold fs-6">
                                <tr>
                                    <td class="text-end ps-3">{{ __('finance.period_income') }}:</td>
                                    <td class="text-end pe-3 text-success">৳{{ number_format($report['total_income'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-bottom border-danger border-2 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-danger d-flex align-items-center">
                        <i class="bi bi-graph-down-arrow me-2"></i>{{ __('finance.expense') }}
                    </h5>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">{{ count($report['expenses']) }}</span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="expenseStatementTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ __('finance.category_title') }}</th>
                                    <th class="text-end pe-3">{{ __('common.amount') }} (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['expenses'] as $row)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold">{{ $row['name_en'] }}</div>
                                            @if(!empty($row['name_bn']))
                                                <div class="text-muted small">{{ $row['name_bn'] }}</div>
                                            @endif
                                            <span class="d-none">{{ $row['code'] }}</span>
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-danger">৳{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold fs-6">
                                <tr>
                                    <td class="text-end ps-3">{{ __('finance.period_expense') }}:</td>
                                    <td class="text-end pe-3 text-danger">৳{{ number_format($report['total_expense'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2-basic').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    TempleUI.initDataTable('#incomeStatementTable', {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [],
        columns: [
            { orderable: true, className: 'ps-3' },
            { orderable: true, className: 'text-end pe-3' }
        ]
    });

    TempleUI.initDataTable('#expenseStatementTable', {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [],
        columns: [
            { orderable: true, className: 'ps-3' },
            { orderable: true, className: 'text-end pe-3' }
        ]
    });
});
</script>
@endpush
