@extends('layouts.app')

@section('title', __('finance.cash_book'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('finance.cash_book') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.cash_book_subtitle') ?? 'Record of daily physical cash inflows, collections, expenditures, and running vault balance' }}</p>
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
            <form action="{{ route('finance.reports.cash-book') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.start_date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">{{ __('finance.end_date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 flex-grow-1 shadow-sm">
                        <i class="bi bi-filter me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('finance.reports.cash-book') }}" class="btn btn-outline-secondary rounded-pill px-3" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if($cashBook)
        <!-- Cash Book Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.opening_balance') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-secondary">৳{{ number_format($cashBook['opening_balance'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.cash_receipts_inflow') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-success">৳{{ number_format($cashBook['total_debit'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.cash_payments_outflow') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-danger">৳{{ number_format($cashBook['total_credit'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('finance.closing_balance') }}</span>
                        <div class="h5 mb-0 fw-bold mt-1 text-primary">৳{{ number_format($cashBook['closing_balance'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Book Table Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="mb-0 fw-bold text-dark">
                    {{ __('finance.cash_in_hand_ledger') }}
                    <span class="badge bg-light text-muted border ms-2 font-monospace">Account 1010</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1">{{ count($cashBook['lines']) }} {{ count($cashBook['lines']) === 1 ? 'transaction' : 'transactions' }}</span>
                </h5>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="cashBookTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('finance.entry_date') }}</th>
                                <th>{{ __('common.description') }}</th>
                                <th>{{ __('events.event') }}</th>
                                <th class="text-end text-success">{{ __('finance.receipts') }} ({{ __('finance.debit') }} ৳)</th>
                                <th class="text-end text-danger">{{ __('finance.payments') }} ({{ __('finance.credit') }} ৳)</th>
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
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end pe-3 fw-bold text-dark">৳{{ number_format($cashBook['opening_balance'], 2) }}</td>
                            </tr>
                            @foreach($cashBook['lines'] as $line)
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
                                    <td class="text-end fw-bold text-success">
                                        {{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end pe-3 fw-bold text-dark">৳{{ number_format($line['running_balance'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold fs-6">
                            <tr>
                                <td colspan="3" class="text-end ps-3">{{ __('common.total') }}:</td>
                                <td class="text-end text-success">৳{{ number_format($cashBook['total_debit'], 2) }}</td>
                                <td class="text-end text-danger">৳{{ number_format($cashBook['total_credit'], 2) }}</td>
                                <td class="text-end text-primary pe-3">৳{{ number_format($cashBook['closing_balance'], 2) }}</td>
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
    @if($cashBook)
        TempleUI.initDataTable('#cashBookTable', {
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [], // Preserve chronological running balance order
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
