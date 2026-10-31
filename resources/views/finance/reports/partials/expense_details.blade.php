<div class="report-details-drawer">
    <!-- Header Summary Alert -->
    <div class="d-flex justify-content-between align-items-center p-3 mb-3 bg-light rounded border">
        <div>
            <span class="text-muted small text-uppercase fw-bold">{{ __('finance.transaction_code') }}</span>
            <h5 class="mb-0 fw-bold font-monospace text-danger">
                {{ $expense->expense_number }}
            </h5>
        </div>
        <div class="text-end">
            <span class="text-muted small text-uppercase fw-bold d-block">{{ __('common.amount') }}</span>
            <span class="fs-4 fw-bold text-danger">৳{{ number_format((float)$expense->amount, 2) }}</span>
        </div>
    </div>

    <!-- Section A: Transaction Info -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 px-3 fw-bold text-dark border-bottom">
            <i class="bi bi-info-circle text-primary me-2"></i>{{ __('finance.transaction_info') }}
        </div>
        <div class="card-body p-3">
            <div class="row g-2 small">
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.entry_date') }}:</span>
                    <strong class="font-monospace">
                        {{ $expense->expense_date?->format('Y-m-d') ?? '-' }}
                    </strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.payment_method') }}:</span>
                    <strong class="text-capitalize">
                        {{ __('donations.method_' . $expense->payment_method) !== 'donations.method_' . $expense->payment_method 
                            ? __('donations.method_' . $expense->payment_method) 
                            : (__('finance.method_' . $expense->payment_method) !== 'finance.method_' . $expense->payment_method ? __('finance.method_' . $expense->payment_method) : ucfirst(str_replace('_', ' ', $expense->payment_method))) }}
                    </strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.invoice_number') }}:</span>
                    <span class="font-monospace">{{ $expense->invoice_number ?: '-' }}</span>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.reference_number') }}:</span>
                    <span class="font-monospace">{{ $expense->reference_number ?: '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section B: Payee & Event Info -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 px-3 fw-bold text-dark border-bottom">
            <i class="bi bi-person-badge text-primary me-2"></i>{{ __('finance.payee_event_info') }}
        </div>
        <div class="card-body p-3">
            <div class="row g-2 small">
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.payee_vendor') }}:</span>
                    <strong class="fs-6">{{ $expense->payee_name ?? $expense->vendor_name ?? '-' }}</strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('common.phone') }}:</span>
                    <span class="font-monospace">{{ $expense->vendor_phone ?: '-' }}</span>
                </div>
                <div class="col-12 mt-2">
                    <span class="text-muted d-block">{{ __('events.event') }}:</span>
                    @if($expense->event)
                        <span class="fw-bold">{{ $expense->event->title_en }}</span>
                        @if($expense->event->category)
                            <span class="badge bg-light text-muted border ms-1">{{ $expense->event->category->name_en }}</span>
                        @endif
                    @else
                        <span class="badge bg-light text-dark border">{{ __('finance.general_temple_fund') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Section C: Financial Info -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 px-3 fw-bold text-dark border-bottom">
            <i class="bi bi-diagram-3 text-primary me-2"></i>{{ __('finance.financial_info') }}
        </div>
        <div class="card-body p-3">
            <div class="row g-2 small">
                <div class="col-12">
                    <span class="text-muted d-block">{{ __('finance.expense_category') }}:</span>
                    @if($expense->expenseAccount)
                        <strong class="text-dark">{{ $expense->expenseAccount->display_name }}</strong>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
                <div class="col-12">
                    <span class="text-muted d-block">{{ __('finance.paid_from') }}:</span>
                    @if($expense->paymentAccount)
                        <strong class="text-dark">{{ $expense->paymentAccount->display_name }}</strong>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
                <div class="col-12">
                    <span class="text-muted d-block">{{ __('common.title') }}:</span>
                    <span class="fw-bold">{{ app()->getLocale() === 'bn' && $expense->title_bn ? $expense->title_bn : $expense->title_en }}</span>
                </div>
                @if($expense->notes || $expense->description)
                    <div class="col-12">
                        <span class="text-muted d-block">{{ __('common.notes') }}:</span>
                        <div class="bg-light p-2 rounded small">{{ $expense->notes ?: $expense->description }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Section D: Approval & Audit Info -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 px-3 fw-bold text-dark border-bottom">
            <i class="bi bi-shield-check text-primary me-2"></i>{{ __('finance.approval_audit_info') }}
        </div>
        <div class="card-body p-3">
            <div class="row g-2 small">
                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('common.status') }}:</span>
                    @if($expense->status === 'approved')
                        <span class="badge bg-success">{{ __('finance.approved') }}</span>
                    @elseif($expense->status === 'pending_approval' || $expense->status === 'pending')
                        <span class="badge bg-warning text-dark">{{ __('finance.pending_approval') }}</span>
                    @elseif($expense->status === 'rejected')
                        <span class="badge bg-danger">{{ __('finance.rejected') }}</span>
                    @elseif($expense->status === 'reversed')
                        <span class="badge bg-secondary">{{ __('finance.reversed') }}</span>
                    @else
                        <span class="badge bg-secondary">{{ $expense->status }}</span>
                    @endif
                </div>

                <div class="col-sm-6">
                    <span class="text-muted d-block">{{ __('finance.submitted_at') }}:</span>
                    <span>
                        {{ $expense->creator?->name ?? '-' }}
                        <div class="text-muted small">{{ $expense->created_at?->format('d M Y, h:i A') }}</div>
                    </span>
                </div>

                @if($expense->approved_by)
                    <div class="col-12">
                        <span class="text-muted d-block text-success">{{ __('finance.approved') }}:</span>
                        <span>{{ $expense->approver?->name }} on {{ $expense->approved_at?->format('d M Y, h:i A') }}</span>
                    </div>
                @endif

                @if($expense->rejected_by)
                    <div class="col-12">
                        <span class="text-muted d-block text-danger">{{ __('finance.rejected') }}:</span>
                        <span>{{ $expense->rejecter?->name }} on {{ $expense->rejected_at?->format('d M Y, h:i A') }}</span>
                        @if($expense->rejection_reason)
                            <div class="alert alert-danger py-1 px-2 small mt-1 mb-0">{{ $expense->rejection_reason }}</div>
                        @endif
                    </div>
                @endif

                @if($expense->journalEntry)
                    <div class="col-12">
                        <span class="text-muted d-block">{{ __('finance.journal_voucher') }}:</span>
                        <span class="font-monospace fw-bold text-success">{{ $expense->journalEntry->journal_number }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Section E: Attachments & Actions -->
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-header bg-white py-2 px-3 fw-bold text-dark border-bottom">
            <i class="bi bi-link-45deg text-primary me-2"></i>{{ __('finance.attachments_actions') }}
        </div>
        <div class="card-body p-3">
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('finance.expenses.show', $expense->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-text me-1"></i>{{ __('finance.view_expense_details') }}
                </a>

                @if($expense->journalEntry)
                    <a href="{{ route('finance.journals.show', $expense->journal_entry_id) }}" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-journal-text me-1"></i>{{ __('finance.journal_voucher') }}
                    </a>
                @endif

                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>{{ __('finance.print_report') }}
                </button>
            </div>
        </div>
    </div>
</div>
