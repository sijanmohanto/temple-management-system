@extends('layouts.app')

@section('title', __('finance.fund_transfers'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('finance.fund_transfers') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.transfer_note') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('finance.finance_dashboard') }}
            </a>
            @if(Auth::user()->hasPermission('manage_transfers'))
                <a href="{{ route('finance.transfers.create') }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
                    <i class="bi bi-arrow-left-right me-1"></i> {{ __('finance.new_fund_transfer') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Transfers Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="transfersTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('finance.from_account_short') }}</th>
                            <th class="text-center" style="width: 32px;"><i class="bi bi-arrow-right text-muted"></i></th>
                            <th>{{ __('finance.to_account_short') }}</th>
                            <th class="text-end">{{ __('finance.transfer_amount_short') }}</th>
                            <th class="text-end">{{ __('finance.fee_amount_short') }}</th>
                            <th>{{ __('finance.reference_number_short') }}</th>
                            <th class="text-center">{{ __('common.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfers as $t)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-primary">{{ $t->fromAccount?->name_en }}</div>
                                    <div class="text-muted small">{{ $t->fromAccount?->name_bn ?: $t->fromAccount?->code }}</div>
                                    <span class="d-none">{{ $t->transfer_number }}</span>
                                </td>
                                <td class="text-center">
                                    <i class="bi bi-arrow-right text-muted"></i>
                                </td>
                                <td>
                                    <div class="fw-semibold text-success">{{ $t->toAccount?->name_en }}</div>
                                    <div class="text-muted small">{{ $t->toAccount?->name_bn ?: $t->toAccount?->code }}</div>
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-dark fs-6">৳{{ number_format($t->amount, 2) }}</div>
                                    <div class="small text-muted fw-normal text-nowrap mt-1">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $t->transfer_date->format('d M Y') }}
                                    </div>
                                </td>
                                <td class="text-end text-muted">
                                    {{ $t->fee_amount > 0 ? '৳' . number_format($t->fee_amount, 2) : '—' }}
                                </td>
                                <td>
                                    @if($t->reference_number)
                                        <span class="badge bg-light text-dark border font-monospace">{{ $t->reference_number }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($t->isCompleted())
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <i class="bi bi-check-circle me-1"></i>{{ __('finance.completed') }}
                                        </span>
                                    @elseif($t->isReversed())
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('finance.reversed') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ ucfirst($t->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1 justify-content-end">
                                        @if($t->journalEntry)
                                            <a href="{{ route('finance.journals.show', $t->journalEntry) }}" class="table-action-btn table-action-btn-view" title="{{ __('finance.voucher_details') }}">
                                                <i class="bi bi-journal-text"></i>
                                            </a>
                                        @endif
                                        @if($t->isCompleted() && Auth::user()->hasPermission('reverse_transactions'))
                                            <button type="button" class="table-action-btn table-action-btn-delete" data-bs-toggle="modal" data-bs-target="#reverseTransferModal{{ $t->id }}" title="{{ __('finance.reverse_entry') }}">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@foreach($transfers as $t)
    @if($t->isCompleted() && Auth::user()->hasPermission('reverse_transactions'))
        <!-- Reverse Modal for {{ $t->transfer_number }} -->
        <div class="modal fade text-start" id="reverseTransferModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('finance.transfers.reverse', $t) }}" method="POST">
                    @csrf
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title text-danger fw-bold">
                                <i class="bi bi-exclamation-triangle me-1"></i> Reverse Fund Transfer {{ $t->transfer_number }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p class="mb-3">Are you sure you want to reverse this fund transfer of <strong class="text-danger">৳{{ number_format($t->amount, 2) }}</strong>?</p>
                            <div class="mb-3">
                                <label class="form-label required fw-bold">{{ __('finance.reversal_reason') }}</label>
                                <textarea name="reason" class="form-control" rows="3" required minlength="5" maxlength="500" placeholder="{{ __('finance.reversal_reason_placeholder') }}"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light">
                            <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                            <button type="submit" class="btn btn-danger rounded-pill px-3">{{ __('finance.reverse_entry') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    TempleUI.initDataTable('#transfersTable', {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[3, 'desc']],
        columns: [
            { orderable: true, className: 'ps-3' },
            { orderable: false, searchable: false, className: 'text-center' },
            { orderable: true },
            { orderable: true, className: 'text-end' },
            { orderable: true, className: 'text-end' },
            { orderable: true },
            { orderable: true, className: 'text-center' },
            { orderable: false, searchable: false, className: 'text-end pe-3' }
        ]
    });
});
</script>
@endpush
