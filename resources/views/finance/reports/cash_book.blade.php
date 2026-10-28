@extends('layouts.app')

@section('title', __('finance.cash_book'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.cash_book') }}</h1>
            <p class="text-muted small mb-0">Record of daily physical cash inflows, collections, expenditures, and running vault balance</p>
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
            <form action="{{ route('finance.reports.cash-book') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <a href="{{ route('finance.reports.cash-book') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if($cashBook)
        <!-- Cash Book Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.opening_balance') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-secondary">৳{{ number_format($cashBook['opening_balance'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.cash_receipts_inflow') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-success">৳{{ number_format($cashBook['total_debit'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.cash_payments_outflow') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-danger">৳{{ number_format($cashBook['total_credit'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.closing_balance') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-primary">৳{{ number_format($cashBook['closing_balance'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Book Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">{{ __('finance.cash_in_hand_ledger') }} (Account 1010)</h5>
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
                                <th class="text-end text-success">{{ __('finance.receipts') }} ({{ __('finance.debit') }} ৳)</th>
                                <th class="text-end text-danger">{{ __('finance.payments') }} ({{ __('finance.credit') }} ৳)</th>
                                <th class="text-end">{{ __('finance.running_balance') }} (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Opening Balance Row -->
                            <tr class="table-light text-muted fst-italic">
                                <td colspan="4">{{ __('finance.opening_balance') }}</td>
                                <td class="text-end">-</td>
                                <td class="text-end">-</td>
                                <td class="text-end fw-bold">৳{{ number_format($cashBook['opening_balance'], 2) }}</td>
                            </tr>
                            @forelse($cashBook['lines'] as $line)
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
                                <td class="text-end text-success">৳{{ number_format($cashBook['total_debit'], 2) }}</td>
                                <td class="text-end text-danger">৳{{ number_format($cashBook['total_credit'], 2) }}</td>
                                <td class="text-end text-primary">৳{{ number_format($cashBook['closing_balance'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
