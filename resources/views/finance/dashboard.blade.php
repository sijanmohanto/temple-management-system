@extends('layouts.app')

@section('title', __('finance.finance_dashboard'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.finance_dashboard') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.all_systems_operational') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if(Auth::user()->hasPermission('record_expenses'))
                <a href="{{ route('finance.expenses.create') }}" class="btn btn-outline-danger">
                    <i class="bi bi-dash-circle me-1"></i> {{ __('finance.record_expense') }}
                </a>
            @endif
            @if(Auth::user()->hasPermission('manage_transfers'))
                <a href="{{ route('finance.transfers.create') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left-right me-1"></i> {{ __('finance.new_fund_transfer') }}
                </a>
            @endif
            @if(Auth::user()->hasPermission('record_income'))
                <a href="{{ route('finance.income.create') }}" class="btn btn-outline-success">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('finance.new_misc_income') }}
                </a>
            @endif
            @if(Auth::user()->hasPermission('view_financial_reports'))
                <a href="{{ route('finance.reports.trial-balance') }}" class="btn btn-primary">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('finance.financial_reports') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Treasury Cards (Real-time Balances) -->
    <div class="row g-3 mb-4">
        <!-- Cash in Hand -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">{{ __('finance.cash_in_hand') }}</div>
                            <div class="h4 mb-0 fw-bold text-success">৳{{ number_format($cashBalance, 2) }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Account 1010</div>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                            <i class="bi bi-cash-stack fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Balances -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">{{ __('finance.bank_balances') }}</div>
                            <div class="h4 mb-0 fw-bold text-primary">৳{{ number_format($bankBalance, 2) }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Accounts 1020+</div>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                            <i class="bi bi-bank fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MFS Balances -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">{{ __('finance.mfs_balances') }}</div>
                            <div class="h4 mb-0 fw-bold text-info">৳{{ number_format($mfsBalance, 2) }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">bKash, Nagad, Rocket (1030)</div>
                        </div>
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle">
                            <i class="bi bi-phone fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Treasury -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase">{{ __('finance.total_treasury') }}</div>
                            <div class="h4 mb-0 fw-bold text-dark">৳{{ number_format($totalTreasury, 2) }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">All Liquid Assets</div>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                            <i class="bi bi-safe2 fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Operational Performance -->
    <div class="row g-3 mb-4">
        <!-- Monthly Income -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold">{{ __('finance.period_income') }} ({{ Carbon\Carbon::now()->format('F Y') }})</span>
                        <span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-graph-up-arrow"></i></span>
                    </div>
                    <div class="h3 fw-bold text-success mb-1">৳{{ number_format($monthlyStatement['total_income'], 2) }}</div>
                    <div class="text-muted small">Pledges, general, event donations & dues</div>
                </div>
            </div>
        </div>

        <!-- Monthly Expenses -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold">{{ __('finance.period_expense') }} ({{ Carbon\Carbon::now()->format('F Y') }})</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger"><i class="bi bi-graph-down-arrow"></i></span>
                    </div>
                    <div class="h3 fw-bold text-danger mb-1">৳{{ number_format($monthlyStatement['total_expense'], 2) }}</div>
                    <div class="text-muted small">Puja, utilities, bhog & maintenance</div>
                </div>
            </div>
        </div>

        <!-- Net Surplus / Deficit -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold">{{ __('finance.net_surplus_deficit') }}</span>
                        <span class="badge {{ $monthlyStatement['net_surplus'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                            {{ $monthlyStatement['net_surplus'] >= 0 ? __('finance.surplus') : __('finance.deficit') }}
                        </span>
                    </div>
                    <div class="h3 fw-bold {{ $monthlyStatement['net_surplus'] >= 0 ? 'text-primary' : 'text-danger' }} mb-1">
                        ৳{{ number_format($monthlyStatement['net_surplus'], 2) }}
                    </div>
                    <div class="text-muted small">Net operating result for current period</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attention Items -->
    <div class="row g-3 mb-4">
        @if($pendingExpensesCount > 0)
            <div class="col-md-6">
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between mb-0">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning"></i>
                        <div>
                            <div class="fw-bold">{{ __('finance.pending_approvals') }}: {{ $pendingExpensesCount }}</div>
                            <div class="small">Totaling ৳{{ number_format($pendingExpensesAmount, 2) }} awaiting treasurer approval.</div>
                        </div>
                    </div>
                    <a href="{{ route('finance.expenses.index', ['status' => 'pending']) }}" class="btn btn-warning btn-sm">
                        {{ __('finance.view_all') }}
                    </a>
                </div>
            </div>
        @endif

        @if($unpostedPaymentsCount > 0)
            <div class="col-md-6">
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center justify-content-between mb-0">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-3 me-3 text-info"></i>
                        <div>
                            <div class="fw-bold">{{ __('finance.unposted_collections') }}: {{ $unpostedPaymentsCount }}</div>
                            <div class="small">{{ __('finance.unposted_help') }} (৳{{ number_format($unpostedPaymentsAmount, 2) }}).</div>
                        </div>
                    </div>
                    <a href="{{ route('collections.index', ['status' => 'verified']) }}" class="btn btn-info btn-sm text-white">
                        {{ __('finance.view_all') }}
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Recent Journal Entries -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">{{ __('finance.recent_journals') }}</h5>
            <a href="{{ route('finance.journals.index') }}" class="btn btn-outline-secondary btn-sm">
                {{ __('finance.view_all') }} <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                    <tbody>
                        @forelse($recentJournals as $journal)
                            <tr>
                                <td class="fw-bold">
                                    <a href="{{ route('finance.journals.show', $journal) }}" class="text-decoration-none">
                                        {{ $journal->journal_number }}
                                    </a>
                                </td>
                                <td>{{ $journal->entry_date->format('Y-m-d') }}</td>
                                <td>
                                    <div class="text-truncate" style="max-width: 320px;" title="{{ $journal->description }}">
                                        {{ $journal->description }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ ucfirst(str_replace('_', ' ', $journal->source_type)) }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-success">৳{{ number_format($journal->total_debit, 2) }}</td>
                                <td class="text-end fw-bold text-primary">৳{{ number_format($journal->total_credit, 2) }}</td>
                                <td class="text-center">
                                    @if($journal->status === 'posted')
                                        <span class="badge bg-success">{{ __('finance.posted') }}</span>
                                    @elseif($journal->status === 'reversed')
                                        <span class="badge bg-danger">{{ __('finance.reversed') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($journal->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('finance.journals.show', $journal) }}" class="btn btn-sm btn-outline-primary" title="{{ __('common.view') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    {{ __('common.no_records_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
