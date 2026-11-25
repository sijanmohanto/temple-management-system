@extends('layouts.app')

@section('title', __('finance.general_ledger'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('finance.general_ledger') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.general_ledger_subtitle') ?? 'Detailed chronological audit trail and running balance for financial accounts' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('finance.finance_dashboard') }}
            </a>
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> {{ __('finance.print_report') }}
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('finance.reports.general-ledger') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.select_account') }}</label>
                    <select name="account_id" class="form-select select2-basic" required>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ $selectedAccountId == $acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} — {{ $acc->name_en }} ({{ ucfirst($acc->account_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-1 d-flex">
                    <button type="submit" class="btn btn-primary rounded-pill w-100 shadow-sm" title="{{ __('common.filter') }}">
                        <i class="bi bi-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($ledger)
        <!-- Ledger Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.opening_balance') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-secondary">৳{{ number_format($ledger['opening_balance'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">Period Debits</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-success">৳{{ number_format($ledger['total_debit'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">Period Credits</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-primary">৳{{ number_format($ledger['total_credit'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.closing_balance') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-dark">৳{{ number_format($ledger['closing_balance'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ledger Table Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="mb-0 fw-bold text-dark">
                    {{ $ledger['account']->name_en }}
                    @if($ledger['account']->name_bn)
                        <span class="text-muted fw-normal fs-6">({{ $ledger['account']->name_bn }})</span>
                    @endif
                    <span class="badge bg-light text-muted border ms-2 font-monospace">{{ $ledger['account']->code }}</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1">{{ count($ledger['lines']) }} {{ count($ledger['lines']) === 1 ? 'transaction' : 'transactions' }}</span>
                </h5>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="generalLedgerTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('finance.entry_date') }}</th>
                                <th>{{ __('common.description') }}</th>
                                <th>{{ __('events.event') }}</th>
                                <th class="text-end">{{ __('finance.debit') }} (৳)</th>
                                <th class="text-end">{{ __('finance.credit') }} (৳)</th>
                                <th class="text-end pe-3">{{ __('finance.running_balance') }} (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Opening Balance Row -->
                            <tr class="table-light text-muted fst-italic">
                                <td class="ps-3">—</td>
                                <td>
                                    <span class="fw-semibold text-secondary">{{ __('finance.opening_balance') }}</span>
                                </td>
                                <td class="text-center">—</td>
                                <td class="text-end">—</td>
                                <td class="text-end">—</td>
                                <td class="text-end pe-3 fw-bold text-dark">৳{{ number_format($ledger['opening_balance'], 2) }}</td>
                            </tr>
                            @foreach($ledger['lines'] as $line)
                                <tr>
                                    <td class="ps-3 text-nowrap">
                                        <i class="bi bi-calendar3 me-1 text-muted"></i>{{ $line['entry_date'] }}
                                    </td>
                                    <td>
                                        <a href="{{ route('finance.journals.index', ['search' => $line['journal_number']]) }}" class="text-decoration-none text-dark fw-semibold">
                                            {{ $line['description'] }}
                                        </a>
                                        <span class="d-none">{{ $line['journal_number'] }}</span>
                                    </td>
                                    <td>
                                        @if($line['event_title'])
                                            <span class="badge bg-light text-secondary border font-xs">
                                                <i class="bi bi-calendar-event me-1"></i>{{ $line['event_title'] }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold {{ $line['debit'] > 0 ? 'text-success' : 'text-muted' }}">
                                        {{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end fw-bold {{ $line['credit'] > 0 ? 'text-primary' : 'text-muted' }}">
                                        {{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end pe-3 fw-bold text-dark">৳{{ number_format($line['running_balance'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold fs-6">
                            <tr>
                                <td colspan="3" class="text-end ps-3">{{ __('common.total') }}:</td>
                                <td class="text-end text-success">৳{{ number_format($ledger['total_debit'], 2) }}</td>
                                <td class="text-end text-primary">৳{{ number_format($ledger['total_credit'], 2) }}</td>
                                <td class="text-end text-dark pe-3">৳{{ number_format($ledger['closing_balance'], 2) }}</td>
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

    @if($ledger)
        TempleUI.initDataTable('#generalLedgerTable', {
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [], // Preserve chronological ledger balance order
            columns: [
                { orderable: true, className: 'ps-3' },
                { orderable: true },
                { orderable: true },
                { orderable: true, className: 'text-end' },
                { orderable: true, className: 'text-end' },
                { orderable: true, className: 'text-end pe-3' }
            ]
        });
    @endif
});
</script>
@endpush
