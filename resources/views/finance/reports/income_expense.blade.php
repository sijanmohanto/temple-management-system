@extends('layouts.app')

@section('title', __('finance.income_expense_statement'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.income_expense_statement') }}</h1>
            <p class="text-muted small mb-0">Summary of temple revenues, donations, operational expenditures and net surplus/deficit</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> {{ __('finance.print_report') }}
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('finance.reports.income-expense') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('events.event') }} (Optional)</label>
                    <select name="event_id" class="form-select form-select-sm select2-basic">
                        <option value="">-- {{ __('finance.all_events') }} --</option>
                        @foreach($events as $ev)
                            <option value="{{ $ev->id }}" {{ $eventId == $ev->id ? 'selected' : '' }}>
                                {{ $ev->title_en }} ({{ $ev->category?->name_en }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <a href="{{ route('finance.reports.income-expense') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Performance Banner -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-success">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('finance.period_income') }}</div>
                    <div class="h3 mb-0 fw-bold text-success">৳{{ number_format($report['total_income'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-danger">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('finance.period_expense') }}</div>
                    <div class="h3 mb-0 fw-bold text-danger">৳{{ number_format($report['total_expense'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 {{ $report['net_surplus'] >= 0 ? 'border-primary' : 'border-danger' }}">
                <div class="card-body">
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
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom border-success border-2">
                    <h5 class="mb-0 fw-bold text-success">{{ __('finance.income') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('finance.account_code') }}</th>
                                    <th>{{ __('finance.category_title') }}</th>
                                    <th class="text-end">{{ __('common.amount') }} (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['incomes'] as $row)
                                    <tr>
                                        <td class="fw-bold font-monospace">{{ $row['code'] }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $row['name_en'] }}</div>
                                            @if($row['name_bn'])
                                                <div class="text-muted small">{{ $row['name_bn'] }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-success">৳{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">{{ __('common.no_records_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light fw-bold fs-6">
                                <tr>
                                    <td colspan="2" class="text-end">{{ __('finance.period_income') }}:</td>
                                    <td class="text-end text-success">৳{{ number_format($report['total_income'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom border-danger border-2">
                    <h5 class="mb-0 fw-bold text-danger">{{ __('finance.expense') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('finance.account_code') }}</th>
                                    <th>{{ __('finance.category_title') }}</th>
                                    <th class="text-end">{{ __('common.amount') }} (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['expenses'] as $row)
                                    <tr>
                                        <td class="fw-bold font-monospace">{{ $row['code'] }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $row['name_en'] }}</div>
                                            @if($row['name_bn'])
                                                <div class="text-muted small">{{ $row['name_bn'] }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-danger">৳{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">{{ __('common.no_records_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light fw-bold fs-6">
                                <tr>
                                    <td colspan="2" class="text-end">{{ __('finance.period_expense') }}:</td>
                                    <td class="text-end text-danger">৳{{ number_format($report['total_expense'], 2) }}</td>
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
});
</script>
@endpush
