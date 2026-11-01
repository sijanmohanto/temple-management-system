@extends('layouts.app')

@section('title', $vendorPayment->payment_number . ' - ' . __('procurement.payment_details'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">{{ $vendorPayment->payment_number }}</h1>
                {!! $vendorPayment->status_badge !!}
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-wallet2 me-1"></i>{{ \App\Models\VendorPayment::supportedPaymentMethods()[$vendorPayment->payment_method] ?? ucfirst(str_replace('_', ' ', $vendorPayment->payment_method)) }}
                </span>
                @if($vendorPayment->reference_number)
                    <span class="badge bg-light text-muted border font-monospace">
                        Ref: {{ $vendorPayment->reference_number }}
                    </span>
                @endif
            </div>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('procurement.payments.index') }}">{{ __('procurement.vendor_payments') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $vendorPayment->payment_number }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <!-- Print Payment Voucher -->
            <a href="{{ route('procurement.payments.print', $vendorPayment) }}" target="_blank" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-printer me-1"></i> {{ __('procurement.print_payment_voucher') }}
            </a>

            <!-- Edit (Draft or Rejected only) -->
            @if((Auth::user()->hasPermission('manage_vendor_payments') || Auth::user()->hasRole('super_admin')) && $vendorPayment->canBeEdited())
                <a href="{{ route('procurement.payments.edit', $vendorPayment) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('common.edit') }}
                </a>
            @endif

            <!-- Submit (Draft only) -->
            @if((Auth::user()->hasPermission('submit_vendor_payments') || Auth::user()->hasRole('super_admin')) && $vendorPayment->canBeSubmitted())
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#submitModal">
                    <i class="bi bi-send-check me-1"></i> {{ __('procurement.submit_payment') }}
                </button>
            @endif

            <!-- Verify (Submitted only) -->
            @if((Auth::user()->hasPermission('verify_vendor_payments') || Auth::user()->hasRole('super_admin')) && $vendorPayment->canBeVerified())
                <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#verifyModal">
                    <i class="bi bi-check2-circle me-1"></i> {{ __('procurement.verify_payment') }}
                </button>
            @endif

            <!-- Authorize / Approve (Verified only) -->
            @if((Auth::user()->hasPermission('approve_vendor_payments') || Auth::user()->hasRole('super_admin')) && $vendorPayment->canBeApproved())
                @if($vendorPayment->created_by === Auth::id() && !Auth::user()->hasRole('super_admin'))
                    <button type="button" class="btn btn-success btn-sm disabled" title="{{ __('procurement.unauthorized_to_approve_own_payment') }}" data-bs-toggle="tooltip">
                        <i class="bi bi-check-circle me-1"></i> {{ __('procurement.approve_payment') }}
                    </button>
                @else
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bi bi-check-circle me-1"></i> {{ __('procurement.approve_payment') }}
                    </button>
                    @if(Auth::user()->hasPermission('post_vendor_payments') || Auth::user()->hasRole('super_admin'))
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#approveAndPostModal">
                            <i class="bi bi-lightning-charge me-1"></i> {{ __('procurement.approve_and_post') }}
                        </button>
                    @endif
                @endif
            @endif

            <!-- Post to Ledger (Approved only) -->
            @if((Auth::user()->hasPermission('post_vendor_payments') || Auth::user()->hasRole('super_admin')) && $vendorPayment->canBePosted())
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#postModal">
                    <i class="bi bi-journal-arrow-down me-1"></i> {{ __('procurement.post_to_ledger') }}
                </button>
            @endif

            <!-- Reject (Submitted or Verified) -->
            @if((Auth::user()->hasPermission('approve_vendor_payments') || Auth::user()->hasPermission('verify_vendor_payments') || Auth::user()->hasRole('super_admin')) && $vendorPayment->canBeRejected())
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle me-1"></i> {{ __('procurement.reject_payment') }}
                </button>
            @endif

            <!-- Cancel (Draft, Submitted, Verified, Approved) -->
            @if((Auth::user()->hasPermission('cancel_vendor_payments') || Auth::user()->hasRole('super_admin')) && $vendorPayment->canBeCancelled())
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-slash-circle me-1"></i> {{ __('procurement.cancel_payment') }}
                </button>
            @endif

            <!-- Reverse (Posted only) -->
            @if((Auth::user()->hasPermission('reverse_vendor_payments') || Auth::user()->hasRole('super_admin')) && $vendorPayment->canBeReversed())
                <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#reverseModal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('procurement.reverse_payment') }}
                </button>
            @endif

            <a href="{{ route('procurement.payments.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Separation of Duties Notice -->
    @if(in_array($vendorPayment->status, ['submitted', 'verified']) && $vendorPayment->created_by === Auth::id() && !Auth::user()->hasRole('super_admin'))
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-info-circle-fill fs-5 me-3 text-info"></i>
            <div>
                <div class="fw-bold">{{ __('procurement.unauthorized_to_approve_own_payment') }}</div>
                <div class="small text-muted">{{ __('procurement.approve_payment_confirm') }}</div>
            </div>
        </div>
    @endif

    <!-- Rejection Alert -->
    @if($vendorPayment->status === 'rejected')
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('procurement.status_rejected') }}</h6>
            <p class="mb-0 small">{{ $vendorPayment->rejection_reason }}</p>
            <small class="text-muted d-block mt-1">{{ __('procurement.rejected_by') }}: {{ $vendorPayment->rejecter?->name }} ({{ $vendorPayment->rejected_at?->format('d M Y, h:i A') }})</small>
        </div>
    @endif

    <!-- Cancellation Alert -->
    @if($vendorPayment->status === 'cancelled')
        <div class="alert alert-dark border-0 shadow-sm mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-slash-circle-fill me-2"></i>{{ __('procurement.status_cancelled') }}</h6>
            <p class="mb-0 small">{{ $vendorPayment->cancellation_reason }}</p>
            <small class="text-muted d-block mt-1">{{ __('procurement.cancelled_by') }}: {{ $vendorPayment->canceller?->name }} ({{ $vendorPayment->cancelled_at?->format('d M Y, h:i A') }})</small>
        </div>
    @endif

    <!-- Reversal Alert -->
    @if($vendorPayment->status === 'reversed')
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-arrow-counterclockwise me-2"></i>{{ __('procurement.status_reversed') }}</h6>
            <p class="mb-0 small">{{ $vendorPayment->reversal_reason }}</p>
            <small class="text-muted d-block mt-1">{{ __('procurement.reversed_by') }}: {{ $vendorPayment->reverser?->name }} ({{ $vendorPayment->reversed_at?->format('d M Y, h:i A') }})</small>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Details (Left Column) -->
        <div class="col-lg-8">
            <!-- Settled Vendor Bills Table -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-receipt me-1 text-primary"></i> {{ __('procurement.settled_bills') }}
                    </h6>
                    <span class="badge bg-primary rounded-pill">{{ $vendorPayment->allocations->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('procurement.bill_number') }}</th>
                                    <th>{{ __('procurement.invoice_reference') }}</th>
                                    <th>{{ __('procurement.bill_date') }}</th>
                                    <th class="text-end">{{ __('procurement.total_price') }}</th>
                                    <th class="text-end text-success">{{ __('procurement.allocated_amount') }}</th>
                                    <th class="text-center">{{ __('procurement.payment_status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendorPayment->allocations as $alloc)
                                    <tr>
                                        <td>
                                            @if($alloc->bill)
                                                <a href="{{ route('procurement.bills.show', $alloc->bill) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                                    {{ $alloc->bill->bill_number }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="font-monospace fw-semibold">{{ $alloc->bill?->vendor_invoice_number ?? '-' }}</td>
                                        <td>{{ $alloc->bill?->bill_date?->format('d M Y') ?? '-' }}</td>
                                        <td class="text-end font-monospace">৳{{ number_format((float) ($alloc->bill?->total_amount ?? 0), 2) }}</td>
                                        <td class="text-end font-monospace fw-bold text-success">৳{{ number_format((float) $alloc->allocated_amount, 2) }}</td>
                                        <td class="text-center">
                                            @if($alloc->bill)
                                                @if($alloc->bill->isFullyPaid())
                                                    <span class="badge bg-success">{{ __('procurement.paid') }}</span>
                                                @elseif($alloc->bill->isPartiallyPaid())
                                                    <span class="badge bg-warning text-dark">{{ __('procurement.partially_paid') }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ __('procurement.unpaid') }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end fw-bold">{{ __('procurement.total_allocated') }}:</th>
                                    <th class="text-end font-monospace fw-bold text-success fs-6">৳{{ number_format((float) $vendorPayment->amount, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Accounting Ledger Posting Section -->
            @if($vendorPayment->journalEntry || $vendorPayment->reversalJournalEntry)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-journal-text me-1 text-primary"></i> {{ __('procurement.journal_entry') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($vendorPayment->journalEntry)
                            <div class="p-3 bg-light rounded mb-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold font-monospace text-primary">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ $vendorPayment->journalEntry->entry_number }}
                                    </span>
                                    <span class="badge bg-success">{{ __('finance.posted') }}</span>
                                </div>
                                <div class="small text-muted mb-2">
                                    {{ $vendorPayment->journalEntry->description }} ({{ $vendorPayment->journalEntry->entry_date?->format('d M Y') }})
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered bg-white mb-0">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>{{ __('finance.account') }}</th>
                                                <th class="text-end">{{ __('finance.debit') }}</th>
                                                <th class="text-end">{{ __('finance.credit') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small font-monospace">
                                            @foreach($vendorPayment->journalEntry->lines as $line)
                                                <tr>
                                                    <td>
                                                        {{ $line->account?->account_code }} -
                                                        {{ app()->getLocale() === 'bn' ? ($line->account?->account_name_bn ?: $line->account?->account_name_en) : $line->account?->account_name_en }}
                                                    </td>
                                                    <td class="text-end {{ $line->debit_amount > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
                                                        {{ $line->debit_amount > 0 ? '৳' . number_format((float) $line->debit_amount, 2) : '-' }}
                                                    </td>
                                                    <td class="text-end {{ $line->credit_amount > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
                                                        {{ $line->credit_amount > 0 ? '৳' . number_format((float) $line->credit_amount, 2) : '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="{{ route('finance.journals.show', $vendorPayment->journalEntry) }}" class="btn btn-link btn-sm p-0 text-decoration-none">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('procurement.view_ledger_entry') }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if($vendorPayment->reversalJournalEntry)
                            <div class="p-3 bg-danger-subtle rounded border border-danger-subtle">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold font-monospace text-danger">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ $vendorPayment->reversalJournalEntry->entry_number }}
                                    </span>
                                    <span class="badge bg-danger">{{ __('procurement.reversal_entry') }}</span>
                                </div>
                                <div class="small text-muted mb-2">
                                    {{ $vendorPayment->reversalJournalEntry->description }} ({{ $vendorPayment->reversalJournalEntry->entry_date?->format('d M Y') }})
                                </div>
                                <div class="mt-2 text-end">
                                    <a href="{{ route('finance.journals.show', $vendorPayment->reversalJournalEntry) }}" class="btn btn-link btn-sm p-0 text-danger text-decoration-none">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('procurement.view_reversal_entry') }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Notes Card -->
            @if($vendorPayment->notes)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-card-text me-1 text-primary"></i> {{ __('common.notes') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-muted">{{ $vendorPayment->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar Info (Right Column) -->
        <div class="col-lg-4">
            <!-- Financial & Payment Summary Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-cash me-1 text-primary"></i> {{ __('procurement.financial_breakdown') }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">{{ __('procurement.payment_amount') }}:</span>
                        <span class="fs-4 fw-bold font-monospace text-success">৳{{ number_format((float) $vendorPayment->amount, 2) }}</span>
                    </div>

                    <hr class="my-3">

                    <div class="mb-2">
                        <small class="text-muted d-block">{{ __('procurement.payment_date') }}</small>
                        <span class="fw-semibold text-dark">{{ $vendorPayment->payment_date?->format('d M Y') }}</span>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted d-block">{{ __('procurement.payment_method') }}</small>
                        <span class="fw-semibold text-dark">
                            {{ \App\Models\VendorPayment::supportedPaymentMethods()[$vendorPayment->payment_method] ?? ucfirst(str_replace('_', ' ', $vendorPayment->payment_method)) }}
                        </span>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted d-block">{{ __('procurement.disbursed_account') }}</small>
                        <span class="fw-semibold text-dark">
                            {{ $vendorPayment->financialAccount?->account_code }} -
                            {{ app()->getLocale() === 'bn' ? ($vendorPayment->financialAccount?->account_name_bn ?: $vendorPayment->financialAccount?->account_name_en) : $vendorPayment->financialAccount?->account_name_en }}
                        </span>
                    </div>

                    @if($vendorPayment->reference_number)
                        <div class="mb-2">
                            <small class="text-muted d-block">{{ __('procurement.reference_number') }}</small>
                            <span class="font-monospace fw-semibold text-dark">{{ $vendorPayment->reference_number }}</span>
                        </div>
                    @endif

                    @if($vendorPayment->document_path)
                        <div class="mt-3 pt-3 border-top">
                            <a href="{{ asset('storage/' . $vendorPayment->document_path) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-paperclip me-1"></i> {{ __('procurement.download_attachment') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Vendor Information Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-building me-1 text-primary"></i> {{ __('procurement.vendor') }}
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">
                        <a href="{{ route('procurement.vendors.show', $vendorPayment->vendor) }}" class="text-decoration-none text-dark">
                            {{ $vendorPayment->vendor->name }}
                        </a>
                    </h6>
                    <span class="badge bg-light text-muted border font-monospace mb-3">{{ $vendorPayment->vendor->vendor_code }}</span>

                    @if($vendorPayment->vendor->contact_person)
                        <div class="mb-2 small">
                            <span class="text-muted">{{ __('procurement.contact_person') }}:</span>
                            <span class="fw-semibold">{{ $vendorPayment->vendor->contact_person }}</span>
                        </div>
                    @endif

                    @if($vendorPayment->vendor->phone)
                        <div class="mb-2 small">
                            <span class="text-muted">{{ __('common.phone') }}:</span>
                            <span class="fw-semibold">{{ $vendorPayment->vendor->phone }}</span>
                        </div>
                    @endif

                    @if($vendorPayment->vendor->address)
                        <div class="small">
                            <span class="text-muted">{{ __('common.address') }}:</span>
                            <div>{{ $vendorPayment->vendor->address }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Approval & Audit Trail Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-clock-history me-1 text-primary"></i> {{ __('procurement.approval_audit_trail') }}
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <!-- Created -->
                        <li class="mb-3 d-flex gap-3">
                            <div class="text-primary fs-5"><i class="bi bi-plus-circle"></i></div>
                            <div>
                                <div class="fw-semibold text-dark">{{ __('procurement.created_by') }}</div>
                                <div class="text-muted">{{ $vendorPayment->creator?->name ?? 'System' }}</div>
                                <div class="text-muted small">{{ $vendorPayment->created_at?->format('d M Y, h:i A') }}</div>
                            </div>
                        </li>

                        <!-- Submitted -->
                        @if($vendorPayment->submitted_at)
                            <li class="mb-3 d-flex gap-3">
                                <div class="text-warning fs-5"><i class="bi bi-send-check"></i></div>
                                <div>
                                    <div class="fw-semibold text-dark">{{ __('procurement.status_submitted') }}</div>
                                    <div class="text-muted">{{ $vendorPayment->submitter?->name }}</div>
                                    <div class="text-muted small">{{ $vendorPayment->submitted_at?->format('d M Y, h:i A') }}</div>
                                </div>
                            </li>
                        @endif

                        <!-- Verified -->
                        @if($vendorPayment->verified_at)
                            <li class="mb-3 d-flex gap-3">
                                <div class="text-info fs-5"><i class="bi bi-check2-circle"></i></div>
                                <div>
                                    <div class="fw-semibold text-dark">{{ __('procurement.verified_by') }}</div>
                                    <div class="text-muted">{{ $vendorPayment->verifier?->name }}</div>
                                    <div class="text-muted small">{{ $vendorPayment->verified_at?->format('d M Y, h:i A') }}</div>
                                    @if($vendorPayment->verification_notes)
                                        <div class="small text-muted fst-italic mt-1">"{{ $vendorPayment->verification_notes }}"</div>
                                    @endif
                                </div>
                            </li>
                        @endif

                        <!-- Approved -->
                        @if($vendorPayment->approved_at)
                            <li class="mb-3 d-flex gap-3">
                                <div class="text-success fs-5"><i class="bi bi-check-circle"></i></div>
                                <div>
                                    <div class="fw-semibold text-dark">{{ __('procurement.approved_by') }}</div>
                                    <div class="text-muted">{{ $vendorPayment->approver?->name }}</div>
                                    <div class="text-muted small">{{ $vendorPayment->approved_at?->format('d M Y, h:i A') }}</div>
                                </div>
                            </li>
                        @endif

                        <!-- Posted -->
                        @if($vendorPayment->posted_at)
                            <li class="mb-3 d-flex gap-3">
                                <div class="text-success fs-5"><i class="bi bi-journal-check"></i></div>
                                <div>
                                    <div class="fw-semibold text-dark">{{ __('procurement.status_posted') }}</div>
                                    <div class="text-muted">{{ $vendorPayment->poster?->name }}</div>
                                    <div class="text-muted small">{{ $vendorPayment->posted_at?->format('d M Y, h:i A') }}</div>
                                </div>
                            </li>
                        @endif

                        <!-- Reversed -->
                        @if($vendorPayment->reversed_at)
                            <li class="d-flex gap-3">
                                <div class="text-danger fs-5"><i class="bi bi-arrow-counterclockwise"></i></div>
                                <div>
                                    <div class="fw-semibold text-danger">{{ __('procurement.status_reversed') }}</div>
                                    <div class="text-muted">{{ $vendorPayment->reverser?->name }}</div>
                                    <div class="text-muted small">{{ $vendorPayment->reversed_at?->format('d M Y, h:i A') }}</div>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Submit Modal -->
<div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('procurement.payments.submit', $vendorPayment) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="submitModalLabel">{{ __('procurement.submit_payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('procurement.submit_payment_confirm') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('procurement.submit_payment') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Verify Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('procurement.payments.verify', $vendorPayment) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="verifyModalLabel">{{ __('procurement.verify_payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">{{ __('procurement.verify_payment_confirm') }}</p>
                    <div class="mb-2">
                        <label for="verification_notes" class="form-label small fw-bold">{{ __('procurement.verification_notes') }}</label>
                        <textarea name="verification_notes" id="verification_notes" class="form-control" rows="2" placeholder="{{ __('common.optional') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-info text-white">{{ __('procurement.verify_payment') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Authorize / Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('procurement.payments.approve', $vendorPayment) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="approveModalLabel">{{ __('procurement.approve_payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('procurement.approve_payment_confirm') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('procurement.approve_payment') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Post Modal -->
<div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('procurement.payments.post', $vendorPayment) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="postModalLabel">{{ __('procurement.post_to_ledger') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('procurement.post_to_ledger_confirm') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('procurement.post_to_ledger') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Approve and Post Modal -->
<div class="modal fade" id="approveAndPostModal" tabindex="-1" aria-labelledby="approveAndPostModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('procurement.payments.approve-and-post', $vendorPayment) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="approveAndPostModalLabel">{{ __('procurement.approve_and_post') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('procurement.approve_and_post_confirm') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('procurement.approve_and_post') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('procurement.payments.reject', $vendorPayment) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger" id="rejectModalLabel">{{ __('procurement.reject_payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">{{ __('procurement.reject_payment_confirm') }}</p>
                    <div class="mb-2">
                        <label for="rejection_reason" class="form-label small fw-bold">
                            {{ __('procurement.rejection_reason') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3" required
                                  placeholder="{{ __('procurement.rejection_reason_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('procurement.reject_payment') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('procurement.payments.cancel', $vendorPayment) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger" id="cancelModalLabel">{{ __('procurement.cancel_payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">{{ __('procurement.cancel_payment_confirm') }}</p>
                    <div class="mb-2">
                        <label for="cancellation_reason" class="form-label small fw-bold">
                            {{ __('procurement.cancellation_reason') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="cancellation_reason" id="cancellation_reason" class="form-control" rows="3" required
                                  placeholder="{{ __('procurement.cancellation_reason_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('procurement.cancel_payment') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reverse Modal -->
<div class="modal fade" id="reverseModal" tabindex="-1" aria-labelledby="reverseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('procurement.payments.reverse', $vendorPayment) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-warning" id="reverseModalLabel">{{ __('procurement.reverse_payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">{{ __('procurement.reverse_payment_confirm') }}</p>
                    <div class="mb-2">
                        <label for="reversal_reason" class="form-label small fw-bold">
                            {{ __('procurement.reversal_reason') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="reversal_reason" id="reversal_reason" class="form-control" rows="3" required
                                  placeholder="{{ __('procurement.reversal_reason_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-warning">{{ __('procurement.reverse_payment') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
