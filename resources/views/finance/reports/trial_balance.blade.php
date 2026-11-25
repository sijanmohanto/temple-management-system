@extends('layouts.app')

@section('title', __('finance.trial_balance'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('finance.trial_balance') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.trial_balance_subtitle') ?? 'Double-entry ledger reconciliation verifying total debits equal total credits' }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-dark rounded-pill px-3 shadow-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> {{ __('finance.print_report') }}
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('finance.reports.trial-balance') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 flex-grow-1 shadow-sm">
                        <i class="bi bi-filter me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('finance.reports.trial-balance') }}" class="btn btn-outline-secondary rounded-pill px-3" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Balance Status Banner -->
    <div class="alert {{ $report['is_balanced'] ? 'alert-success' : 'alert-danger' }} border-0 shadow-sm rounded-4 d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <i class="bi {{ $report['is_balanced'] ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger' }} fs-3 me-3"></i>
            <div>
                <h6 class="mb-1 fw-bold">
                    {{ $report['is_balanced'] ? __('finance.balanced') : __('finance.out_of_balance') }}
                </h6>
                <div class="small">
                    Total Debits: <strong>৳{{ number_format($report['total_debit'], 2) }}</strong> | Total Credits: <strong>৳{{ number_format($report['total_credit'], 2) }}</strong>
                    @if(! $report['is_balanced'])
                        | {{ __('finance.difference') }}: <span class="badge bg-danger">৳{{ number_format($report['difference'], 2) }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div>
            <span class="badge {{ $report['is_balanced'] ? 'bg-success' : 'bg-danger' }} fs-6">
                {{ $report['is_balanced'] ? 'Reconciled (0.00 Difference)' : 'Imbalance Detected' }}
            </span>
        </div>
    </div>

    <!-- Trial Balance Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="trialBalanceTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('finance.account_name') }}</th>
                            <th>{{ __('finance.account_type') }}</th>
                            <th>{{ __('finance.normal_balance') }}</th>
                            <th class="text-end">{{ __('finance.debit') }} (৳)</th>
                            <th class="text-end pe-3">{{ __('finance.credit') }} (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['accounts'] as $row)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('finance.reports.general-ledger', ['account_id' => $row['account_id']]) }}" class="text-decoration-none fw-semibold">
                                        {{ $row['name_en'] }}
                                    </a>
                                    @if($row['name_bn'])
                                        <div class="text-muted small">{{ $row['name_bn'] }}</div>
                                    @endif
                                    <span class="d-none">{{ $row['code'] }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-dark border">
                                        {{ ucfirst($row['type']) }}
                                    </span>
                                </td>
                                <td>{{ ucfirst($row['normal_balance']) }}</td>
                                <td class="text-end fw-bold {{ $row['debit'] > 0 ? 'text-success' : 'text-muted' }}">
                                    {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '—' }}
                                </td>
                                <td class="text-end pe-3 fw-bold {{ $row['credit'] > 0 ? 'text-primary' : 'text-muted' }}">
                                    {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold fs-6">
                        <tr>
                            <td colspan="3" class="text-end ps-3">{{ __('common.total') }}:</td>
                            <td class="text-end text-success">৳{{ number_format($report['total_debit'], 2) }}</td>
                            <td class="text-end text-primary pe-3">৳{{ number_format($report['total_credit'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    TempleUI.initDataTable('#trialBalanceTable', {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [],
        columns: [
            { orderable: true, className: 'ps-3' },
            { orderable: true },
            { orderable: true },
            { orderable: true, className: 'text-end' },
            { orderable: true, className: 'text-end pe-3' }
        ]
    });
});
</script>
@endpush
