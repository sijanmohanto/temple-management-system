@extends('layouts.app')

@section('title', __('finance.fund_transfers'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.fund_transfers') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.transfer_note') }}</p>
        </div>
        <div>
            @if(Auth::user()->hasPermission('manage_transfers'))
                <a href="{{ route('finance.transfers.create') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-left-right me-1"></i> {{ __('finance.new_fund_transfer') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Transfers Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="transfersTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('finance.transfer_number') }}</th>
                            <th>{{ __('finance.transfer_date') }}</th>
                            <th>{{ __('finance.from_account') }}</th>
                            <th><i class="bi bi-arrow-right"></i></th>
                            <th>{{ __('finance.to_account') }}</th>
                            <th class="text-end">{{ __('finance.transfer_amount') }}</th>
                            <th class="text-end">{{ __('finance.fee_amount') }}</th>
                            <th>{{ __('finance.reference_number') }}</th>
                            <th class="text-center">{{ __('finance.status') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $t)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $t->transfer_number }}</td>
                                <td>{{ $t->transfer_date->format('Y-m-d') }}</td>
                                <td>
                                    <span class="fw-semibold text-primary">{{ $t->fromAccount?->name_en }}</span>
                                    <div class="text-muted small">{{ $t->fromAccount?->code }}</div>
                                </td>
                                <td><i class="bi bi-arrow-right text-muted"></i></td>
                                <td>
                                    <span class="fw-semibold text-success">{{ $t->toAccount?->name_en }}</span>
                                    <div class="text-muted small">{{ $t->toAccount?->code }}</div>
                                </td>
                                <td class="text-end fw-bold text-dark">৳{{ number_format($t->amount, 2) }}</td>
                                <td class="text-end text-muted">{{ $t->fee_amount > 0 ? '৳' . number_format($t->fee_amount, 2) : '-' }}</td>
                                <td>{{ $t->reference_number ?: '-' }}</td>
                                <td class="text-center">
                                    @if($t->isCompleted())
                                        <span class="badge bg-success">{{ __('finance.completed') }}</span>
                                    @elseif($t->isReversed())
                                        <span class="badge bg-danger">{{ __('finance.reversed') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($t->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($t->journalEntry)
                                        <a href="{{ route('finance.journals.show', $t->journalEntry) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('finance.voucher_details') }}">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                    @endif
                                    @if($t->isCompleted() && Auth::user()->hasPermission('reverse_transactions'))
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reverseTransferModal{{ $t->id }}" title="{{ __('finance.reverse_entry') }}">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>

                                        <!-- Reverse Modal -->
                                        <div class="modal fade text-start" id="reverseTransferModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form action="{{ route('finance.transfers.reverse', $t) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title text-danger">Reverse Fund Transfer {{ $t->transfer_number }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to reverse this fund transfer of <strong>৳{{ number_format($t->amount, 2) }}</strong>?</p>
                                                            <div class="mb-3">
                                                                <label class="form-label required fw-bold">{{ __('finance.reversal_reason') }}</label>
                                                                <textarea name="reason" class="form-control" rows="3" required minlength="5" maxlength="500" placeholder="{{ __('finance.reversal_reason_placeholder') }}"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                                                            <button type="submit" class="btn btn-danger">{{ __('finance.reverse_entry') }}</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">{{ __('common.no_records_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transfers->hasPages())
                <div class="mt-3">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
