@extends('layouts.app')

@section('title', __('finance.trial_balance'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.trial_balance') }}</h1>
            <p class="text-muted small mb-0">Double-entry ledger reconciliation verifying total debits equal total credits</p>
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
            <form action="{{ route('finance.reports.trial-balance') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('finance.reports.trial-balance') }}" class="btn btn-outline-secondary btn-sm">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Balance Status Banner -->
    <div class="alert {{ $report['is_balanced'] ? 'alert-success' : 'alert-danger' }} border-0 shadow-sm d-flex align-items-center justify-content-between mb-4">
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

    <!-- Trial Balance Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('finance.account_code') }}</th>
                            <th>{{ __('finance.account_name') }}</th>
                            <th>{{ __('finance.account_type') }}</th>
                            <th>{{ __('finance.normal_balance') }}</th>
                            <th class="text-end">{{ __('finance.debit') }} (৳)</th>
                            <th class="text-end">{{ __('finance.credit') }} (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['accounts'] as $row)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $row['code'] }}</td>
                                <td>
                                    <a href="{{ route('finance.reports.general-ledger', ['account_id' => $row['account_id']]) }}" class="text-decoration-none fw-semibold">
                                        {{ $row['name_en'] }}
                                    </a>
                                    @if($row['name_bn'])
                                        <div class="text-muted small">{{ $row['name_bn'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ ucfirst($row['type']) }}
                                    </span>
                                </td>
                                <td>{{ ucfirst($row['normal_balance']) }}</td>
                                <td class="text-end fw-bold {{ $row['debit'] > 0 ? 'text-success' : 'text-muted' }}">
                                    {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}
                                </td>
                                <td class="text-end fw-bold {{ $row['credit'] > 0 ? 'text-primary' : 'text-muted' }}">
                                    {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">{{ __('common.no_records_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold fs-6">
                        <tr>
                            <td colspan="4" class="text-end">{{ __('common.total') }}:</td>
                            <td class="text-end text-success">৳{{ number_format($report['total_debit'], 2) }}</td>
                            <td class="text-end text-primary">৳{{ number_format($report['total_credit'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
