@extends('layouts.app')

@section('title', __('finance.bank_mfs_statements'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.bank_mfs_statements') }}</h1>
            <p class="text-muted small mb-0">Detailed electronic statement for temple institutional bank and mobile wallet accounts</p>
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
            <form action="{{ route('finance.reports.account-statement') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold">{{ __('finance.select_account') }}</label>
                    <select name="account_id" class="form-select form-select-sm select2-basic" required>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ $selectedAccountId == $acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} — {{ $acc->name_en }} ({{ ucfirst($acc->account_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-md-1 d-flex">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($statement)
        <!-- Statement Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.opening_balance') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-secondary">৳{{ number_format($statement['opening_balance'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">Deposits / Credits</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-success">৳{{ number_format($statement['total_debit'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">Withdrawals / Debits</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-danger">৳{{ number_format($statement['total_credit'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.closing_balance') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-primary">৳{{ number_format($statement['closing_balance'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statement Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    {{ $statement['account']->code }} — {{ $statement['account']->name_en }}
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('finance.entry_date') }}</th>
                                <th>{{ __('finance.journal_number') }}</th>
                                <th>{{ __('common.description') }}</th>
                                <th>{{ __('events.event') }}</th>
                                <th class="text-end text-success">{{ __('finance.deposits') }} ({{ __('finance.debit') }} ৳)</th>
                                <th class="text-end text-danger">{{ __('finance.withdrawals') }} ({{ __('finance.credit') }} ৳)</th>
                                <th class="text-end">{{ __('finance.running_balance') }} (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Opening Balance Row -->
                            <tr class="table-light text-muted fst-italic">
                                <td colspan="4">{{ __('finance.opening_balance') }}</td>
                                <td class="text-end">-</td>
                                <td class="text-end">-</td>
                                <td class="text-end fw-bold">৳{{ number_format($statement['opening_balance'], 2) }}</td>
                            </tr>
                            @forelse($statement['lines'] as $line)
                                <tr>
                                    <td>{{ $line['entry_date'] }}</td>
                                    <td class="fw-bold font-monospace">
                                        <a href="{{ route('finance.journals.index', ['search' => $line['journal_number']]) }}" class="text-decoration-none">
                                            {{ $line['journal_number'] }}
                                        </a>
                                    </td>
                                    <td>{{ $line['description'] }}</td>
                                    <td>{{ $line['event_title'] ?: '-' }}</td>
                                    <td class="text-end fw-bold text-success">
                                        {{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '-' }}
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '-' }}
                                    </td>
                                    <td class="text-end fw-bold">৳{{ number_format($line['running_balance'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">{{ __('common.no_records_found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold fs-6">
                            <tr>
                                <td colspan="4" class="text-end">{{ __('common.total') }}:</td>
                                <td class="text-end text-success">৳{{ number_format($statement['total_debit'], 2) }}</td>
                                <td class="text-end text-danger">৳{{ number_format($statement['total_credit'], 2) }}</td>
                                <td class="text-end text-primary">৳{{ number_format($statement['closing_balance'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
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
