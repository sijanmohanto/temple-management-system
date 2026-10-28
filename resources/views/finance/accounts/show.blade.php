@extends('layouts.app')

@section('title', $account->name_en . ' — ' . __('finance.account_details'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ $account->code }} — {{ $account->name_en }}</h1>
            @if($account->name_bn)
                <p class="text-muted small mb-0">{{ $account->name_bn }}</p>
            @endif
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('finance.reports.general-ledger', ['account_id' => $account->id]) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('finance.general_ledger') }}
            </a>
            <a href="{{ route('finance.accounts.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Account Details Card -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold">{{ __('finance.account_type') }}</div>
                    <div class="h5 mb-0 fw-bold">{{ ucfirst($account->account_type) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold">{{ __('finance.normal_balance') }}</div>
                    <div class="h5 mb-0 fw-bold">{{ ucfirst($account->normal_balance) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold">{{ __('finance.opening_balance') }}</div>
                    <div class="h5 mb-0 fw-bold text-secondary">৳{{ number_format($ledger['opening_balance'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold">{{ __('finance.closing_balance') }}</div>
                    <div class="h5 mb-0 fw-bold text-primary">৳{{ number_format($ledger['closing_balance'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Transactions / Running Ledger -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">{{ __('finance.general_ledger') }} ({{ count($ledger['lines']) }} transactions)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="accountLedgerTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('finance.entry_date') }}</th>
                            <th>{{ __('finance.journal_number') }}</th>
                            <th>{{ __('common.description') }}</th>
                            <th>{{ __('events.event') }}</th>
                            <th class="text-end">{{ __('finance.debit') }}</th>
                            <th class="text-end">{{ __('finance.credit') }}</th>
                            <th class="text-end">{{ __('finance.running_balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledger['lines'] as $line)
                            <tr>
                                <td>{{ $line['entry_date'] }}</td>
                                <td class="fw-bold font-monospace">
                                    <a href="{{ route('finance.journals.index', ['search' => $line['journal_number']]) }}" class="text-decoration-none">
                                        {{ $line['journal_number'] }}
                                    </a>
                                </td>
                                <td>{{ $line['description'] }}</td>
                                <td>{{ $line['event_title'] ?: '-' }}</td>
                                <td class="text-end fw-semibold {{ $line['debit'] > 0 ? 'text-success' : 'text-muted' }}">
                                    {{ $line['debit'] > 0 ? '৳' . number_format($line['debit'], 2) : '-' }}
                                </td>
                                <td class="text-end fw-semibold {{ $line['credit'] > 0 ? 'text-primary' : 'text-muted' }}">
                                    {{ $line['credit'] > 0 ? '৳' . number_format($line['credit'], 2) : '-' }}
                                </td>
                                <td class="text-end fw-bold">৳{{ number_format($line['running_balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    {{ __('common.no_records_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">{{ __('common.total') }}:</td>
                            <td class="text-end text-success">৳{{ number_format($ledger['total_debit'], 2) }}</td>
                            <td class="text-end text-primary">৳{{ number_format($ledger['total_credit'], 2) }}</td>
                            <td class="text-end text-primary">৳{{ number_format($ledger['closing_balance'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
