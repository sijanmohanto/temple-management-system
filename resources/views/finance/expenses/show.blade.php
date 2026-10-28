@extends('layouts.app')

@section('title', $expense->expense_number . ' — ' . __('finance.voucher_details'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ $expense->expense_number }}</h1>
            <p class="text-muted small mb-0">{{ $expense->title_en }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($expense->isPending())
                @if(Auth::user()->hasPermission('approve_expenses'))
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveExpenseModal">
                        <i class="bi bi-check-lg me-1"></i> {{ __('finance.approve_expense') }}
                    </button>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectExpenseModal">
                        <i class="bi bi-x-lg me-1"></i> {{ __('finance.reject_expense') }}
                    </button>
                @endif
                @if(Auth::user()->hasPermission('manage_expenses') || Auth::user()->hasPermission('record_expenses'))
                    <a href="{{ route('finance.expenses.edit', $expense) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> {{ __('common.edit') }}
                    </a>
                @endif
            @endif

            @if($expense->status === 'approved' && Auth::user()->hasPermission('reverse_transactions'))
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reverseExpenseModal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('finance.reverse_entry') }}
                </button>
            @endif

            <a href="{{ route('finance.expenses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Status Alert if Rejected or Reversed -->
    @if($expense->status === 'rejected')
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-x-circle-fill fs-3 me-3 text-danger"></i>
                <div>
                    <h6 class="mb-1 fw-bold">{{ __('finance.rejected') }} by {{ $expense->rejecter?->name ?? 'Admin' }}</h6>
                    <div class="small">Reason: {{ $expense->rejection_reason }}</div>
                </div>
            </div>
        </div>
    @elseif($expense->status === 'reversed')
        <div class="alert alert-secondary border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-arrow-counterclockwise fs-3 me-3 text-secondary"></i>
                <div>
                    <h6 class="mb-1 fw-bold">{{ __('finance.reversed') }}</h6>
                    <div class="small">{{ $expense->notes }}</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Expense Details Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('finance.expense_category') }}</span>
                    <div class="fs-6 fw-bold mt-1 text-primary">{{ $expense->expenseAccount?->name_en }}</div>
                    <div class="text-muted small">{{ $expense->expenseAccount?->code }}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('finance.paid_from') }}</span>
                    <div class="fs-6 fw-bold mt-1 text-dark">{{ $expense->paymentAccount?->name_en }}</div>
                    <div class="text-muted small">{{ $expense->paymentAccount?->code }} ({{ ucfirst(str_replace('_', ' ', $expense->payment_method)) }})</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('common.amount') }}</span>
                    <div class="h4 fw-bold mt-1 text-danger">৳{{ number_format($expense->amount, 2) }}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('finance.status') }}</span>
                    <div class="mt-1">
                        @if($expense->status === 'approved')
                            <span class="badge bg-success fs-6">{{ __('finance.approved') }}</span>
                        @elseif($expense->isPending())
                            <span class="badge bg-warning text-dark fs-6">{{ __('finance.pending') }}</span>
                        @elseif($expense->status === 'rejected')
                            <span class="badge bg-danger fs-6">{{ __('finance.rejected') }}</span>
                        @elseif($expense->status === 'reversed')
                            <span class="badge bg-secondary fs-6">{{ __('finance.reversed') }}</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 border-top pt-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('finance.entry_date') }}</span>
                    <div class="mt-1 fw-semibold">{{ $expense->expense_date->format('d M Y') }}</div>
                </div>
                <div class="col-md-3 border-top pt-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('finance.payee_vendor') }}</span>
                    <div class="mt-1 fw-semibold">{{ $expense->vendor_name ?: '-' }}</div>
                    @if($expense->vendor_phone)
                        <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $expense->vendor_phone }}</div>
                    @endif
                </div>
                <div class="col-md-3 border-top pt-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('finance.invoice_number') }}</span>
                    <div class="mt-1 fw-semibold font-monospace">{{ $expense->invoice_number ?: '-' }}</div>
                </div>
                <div class="col-md-3 border-top pt-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('events.event') }}</span>
                    <div class="mt-1 fw-semibold">{{ $expense->event?->title_en ?: 'General Temple' }}</div>
                </div>

                @if($expense->notes)
                    <div class="col-12 border-top pt-3">
                        <span class="text-muted small fw-bold text-uppercase">{{ __('common.notes') }}</span>
                        <div class="mt-1 text-muted">{{ $expense->notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Linked General Ledger Journal Entry -->
    @if($expense->journalEntry)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">General Ledger Voucher</h5>
                <a href="{{ route('finance.journals.show', $expense->journalEntry) }}" class="btn btn-outline-primary btn-sm">
                    {{ $expense->journalEntry->journal_number }} <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Account</th>
                                <th>Line Description</th>
                                <th class="text-end">{{ __('finance.debit') }} (৳)</th>
                                <th class="text-end">{{ __('finance.credit') }} (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expense->journalEntry->lines as $line)
                                <tr>
                                    <td>
                                        <span class="fw-bold font-monospace">{{ $line->account?->code }}</span> — {{ $line->account?->name_en }}
                                    </td>
                                    <td>{{ $line->description ?: '-' }}</td>
                                    <td class="text-end fw-bold {{ $line->entry_type === 'debit' ? 'text-success' : 'text-muted' }}">
                                        {{ $line->entry_type === 'debit' ? number_format($line->amount, 2) : '-' }}
                                    </td>
                                    <td class="text-end fw-bold {{ $line->entry_type === 'credit' ? 'text-primary' : 'text-muted' }}">
                                        {{ $line->entry_type === 'credit' ? number_format($line->amount, 2) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">{{ __('common.total') }}:</td>
                                <td class="text-end text-success">৳{{ number_format($expense->journalEntry->total_debit, 2) }}</td>
                                <td class="text-end text-primary">৳{{ number_format($expense->journalEntry->total_credit, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Approve Expense Modal -->
@if($expense->isPending() && Auth::user()->hasPermission('approve_expenses'))
<div class="modal fade" id="approveExpenseModal" tabindex="-1" aria-labelledby="approveExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.expenses.approve', $expense) }}" method="POST" id="showApproveForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-success" id="approveExpenseModalLabel">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('finance.approve_expense') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">{{ __('finance.confirm_approve_expense') }}</p>
                    <div class="alert alert-light border mb-3">
                        <div class="fw-bold">{{ $expense->expense_number }}: {{ $expense->title_en }}</div>
                        <div class="h5 fw-bold text-danger mb-1 mt-1">৳{{ number_format($expense->amount, 2) }}</div>
                        <div class="small text-muted">{{ __('finance.approve_expense_notice') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('finance.approval_notes') }}</label>
                        <input type="text" name="note" class="form-control" placeholder="Optional audit memo...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-success" id="btnShowApproveSubmit">
                        <i class="bi bi-check-lg me-1"></i> {{ __('finance.approve_expense') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reject Expense Modal -->
<div class="modal fade" id="rejectExpenseModal" tabindex="-1" aria-labelledby="rejectExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.expenses.reject', $expense) }}" method="POST" id="showRejectForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="rejectExpenseModalLabel">
                        <i class="bi bi-x-circle me-1"></i> {{ __('finance.reject_expense') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">{{ __('finance.confirm_reject_expense') }}</p>
                    <div class="alert alert-light border mb-3">
                        <div class="fw-bold">{{ $expense->expense_number }}: {{ $expense->title_en }}</div>
                        <div class="text-danger fw-bold">৳{{ number_format($expense->amount, 2) }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required fw-bold">{{ __('finance.rejection_reason') }}</label>
                        <textarea name="reason" class="form-control" rows="3" required minlength="3" maxlength="500" placeholder="{{ __('finance.rejection_reason_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger" id="btnShowRejectSubmit">
                        <i class="bi bi-x-lg me-1"></i> {{ __('finance.reject_expense') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Reverse Expense Modal -->
@if($expense->status === 'approved' && Auth::user()->hasPermission('reverse_transactions'))
<div class="modal fade" id="reverseExpenseModal" tabindex="-1" aria-labelledby="reverseExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.expenses.reverse', $expense) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="reverseExpenseModalLabel">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('finance.reverse_entry') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        Reversing an approved expense creates an inverse double-entry ledger entry and preserves complete audit history.
                    </div>
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#showApproveForm').on('submit', function() {
        TempleUI.btnLoading('#btnShowApproveSubmit', true);
    });
    $('#showRejectForm').on('submit', function() {
        TempleUI.btnLoading('#btnShowRejectSubmit', true);
    });
});
</script>
@endpush
