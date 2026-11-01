@extends('layouts.app')

@section('title', $vendorBill->bill_number . ' - ' . __('procurement.bill_details'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">{{ $vendorBill->bill_number }}</h1>
                {!! $vendorBill->status_badge !!}
                <span class="badge bg-light text-dark border font-monospace">
                    <i class="bi bi-file-earmark-text me-1"></i>{{ $vendorBill->vendor_invoice_number }}
                </span>
                @if($vendorBill->purchaseOrder)
                    <a href="{{ route('procurement.orders.show', $vendorBill->purchaseOrder) }}" class="badge bg-light text-primary border text-decoration-none font-monospace">
                        <i class="bi bi-box-seam me-1"></i>{{ $vendorBill->purchaseOrder->order_number }}
                    </a>
                @endif
            </div>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('procurement.bills.index') }}">{{ __('procurement.vendor_bills') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $vendorBill->bill_number }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <!-- Print Bill -->
            <a href="{{ route('procurement.bills.print', $vendorBill) }}" target="_blank" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-printer me-1"></i> {{ __('procurement.print_bill') }}
            </a>

            <!-- Edit (Draft only) -->
            @if(Auth::user()->hasPermission('manage_vendor_bills') && $vendorBill->canBeEdited())
                <a href="{{ route('procurement.bills.edit', $vendorBill) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('common.edit') }}
                </a>
            @endif

            <!-- Submit (Draft only) -->
            @if(Auth::user()->hasPermission('manage_vendor_bills') && $vendorBill->canBeSubmitted())
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#submitModal">
                    <i class="bi bi-send-check me-1"></i> {{ __('procurement.submit_for_approval') }}
                </button>
            @endif

            <!-- Verify (Submitted only) -->
            @if(Auth::user()->hasPermission('verify_vendor_bills') && $vendorBill->canBeVerified())
                <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#verifyModal">
                    <i class="bi bi-check2-circle me-1"></i> {{ __('procurement.verify_bill') }}
                </button>
            @endif

            <!-- Approve & Post (Verified only) -->
            @if(Auth::user()->hasPermission('approve_vendor_bills') && $vendorBill->canBeApproved())
                @if($vendorBill->created_by === Auth::id() && !Auth::user()->hasRole('super_admin'))
                    <button type="button" class="btn btn-success btn-sm disabled" title="{{ __('procurement.unauthorized_to_approve_own_bill') }}" data-bs-toggle="tooltip">
                        <i class="bi bi-check-circle me-1"></i> {{ __('procurement.approve_bill') }}
                    </button>
                @else
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bi bi-check-circle me-1"></i> {{ __('procurement.approve_bill') }}
                    </button>
                @endif
            @endif

            <!-- Pay Bill (Approved & Not fully paid) -->
            @if((Auth::user()->hasPermission('manage_vendor_payments') || Auth::user()->hasRole('super_admin')) && $vendorBill->status === 'approved' && ! $vendorBill->isFullyPaid())
                <a href="{{ route('procurement.payments.create', ['vendor_id' => $vendorBill->vendor_id, 'bill_id' => $vendorBill->id]) }}" class="btn btn-success btn-sm">
                    <i class="bi bi-cash-stack me-1"></i> {{ __('procurement.pay_bill') }}
                </a>
            @endif

            <!-- Reject (Submitted or Verified) -->
            @if(Auth::user()->hasPermission('approve_vendor_bills') && $vendorBill->canBeRejected())
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle me-1"></i> {{ __('procurement.reject_bill') }}
                </button>
            @endif

            <!-- Cancel -->
            @if(Auth::user()->hasPermission('cancel_vendor_bills') && $vendorBill->canBeCancelled())
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-slash-circle me-1"></i> {{ __('procurement.cancel_bill') }}
                </button>
            @endif

            <a href="{{ route('procurement.bills.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Separation of Duties Notice -->
    @if($vendorBill->status === 'verified' && $vendorBill->created_by === Auth::id() && !Auth::user()->hasRole('super_admin'))
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-info-circle-fill fs-5 me-3 text-info"></i>
            <div>
                <div class="fw-bold">{{ __('procurement.unauthorized_to_approve_own_bill') }}</div>
                <div class="small text-muted">{{ __('procurement.approve_bill_confirm') }}</div>
            </div>
        </div>
    @endif

    <!-- Rejection Alert -->
    @if($vendorBill->status === 'rejected')
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-x-circle-fill fs-5 me-2"></i>
                <span class="fw-bold">{{ __('procurement.status_rejected') }}</span>
                @if($vendorBill->rejector)
                    <span class="small ms-2">({{ __('common.by') }} {{ $vendorBill->rejector->name }} {{ $vendorBill->rejected_at?->diffForHumans() }})</span>
                @endif
            </div>
            <div class="small ps-4 fst-italic">{{ $vendorBill->rejection_reason }}</div>
        </div>
    @endif

    <!-- Cancellation Alert -->
    @if($vendorBill->status === 'cancelled')
        <div class="alert alert-secondary border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center mb-1">
                <i class="bi bi-slash-circle-fill fs-5 me-2"></i>
                <span class="fw-bold">{{ __('procurement.status_cancelled') }}</span>
                @if($vendorBill->canceller)
                    <span class="small ms-2">({{ __('common.by') }} {{ $vendorBill->canceller->name }} {{ $vendorBill->cancelled_at?->diffForHumans() }})</span>
                @endif
            </div>
            <div class="small ps-4 fst-italic">{{ $vendorBill->cancellation_reason }}</div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left: Vendor Info & Line Items -->
        <div class="col-lg-8">
            <!-- Vendor Info Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-building me-1 text-primary"></i> {{ __('procurement.vendor_details') }}
                    </h6>
                    <a href="{{ route('procurement.vendors.show', $vendorBill->vendor) }}" class="btn btn-sm btn-link text-decoration-none p-0">
                        {{ __('common.view') }} <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('procurement.vendor_name') }}</div>
                            <div class="fw-bold fs-6 text-dark">{{ $vendorBill->vendor->name }}</div>
                            @if($vendorBill->vendor->vendor_type)
                                <span class="badge bg-light text-dark border">{{ $vendorBill->vendor->vendor_type }}</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('procurement.contact_person') }}</div>
                            <div class="fw-medium">{{ $vendorBill->vendor->contact_person ?: '-' }}</div>
                            <div class="small text-muted">
                                @if($vendorBill->vendor->phone)
                                    <div><i class="bi bi-telephone me-1"></i>{{ $vendorBill->vendor->phone }}</div>
                                @endif
                                @if($vendorBill->vendor->email)
                                    <div><i class="bi bi-envelope me-1"></i>{{ $vendorBill->vendor->email }}</div>
                                @endif
                            </div>
                        </div>
                        @if($vendorBill->vendor->address)
                            <div class="col-12">
                                <div class="text-muted small">{{ __('procurement.address') }}</div>
                                <div class="small text-secondary">{{ $vendorBill->vendor->address }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-list-check me-1 text-primary"></i> {{ __('procurement.bill_items') }}
                    </h6>
                    <span class="badge bg-light text-dark border">{{ $vendorBill->items->count() }} {{ __('procurement.items') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('procurement.line_item_description') }}</th>
                                    <th>{{ __('procurement.expense_category') }}</th>
                                    <th class="text-end">{{ __('procurement.quantity') }}</th>
                                    <th>{{ __('procurement.unit') }}</th>
                                    <th class="text-end">{{ __('procurement.rate') }}</th>
                                    <th class="text-end pe-3">{{ __('procurement.item_total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendorBill->items as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $item->item_description }}</div>
                                            @if($item->purchaseOrderItem)
                                                <small class="text-muted font-monospace">
                                                    <i class="bi bi-link-45deg"></i> PO Item #{{ $item->purchase_order_item_id }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->expenseAccount)
                                                <span class="badge bg-light text-dark border">
                                                    {{ $item->expenseAccount->account_code }} - {{ $item->expenseAccount->name_en }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace">{{ number_format((float) $item->quantity, 2) }}</td>
                                        <td>{{ $item->unit ?: '-' }}</td>
                                        <td class="text-end font-monospace">৳{{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="text-end pe-3 fw-bold font-monospace">৳{{ number_format((float) $item->total_price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end fw-bold">{{ __('procurement.subtotal') }}:</th>
                                    <th class="text-end pe-3 fw-bold font-monospace">৳{{ number_format((float) $vendorBill->subtotal, 2) }}</th>
                                </tr>
                                @if($vendorBill->tax_amount > 0)
                                    <tr>
                                        <th colspan="5" class="text-end text-muted">{{ __('procurement.tax_vat_amount') }}:</th>
                                        <th class="text-end pe-3 font-monospace text-muted">+৳{{ number_format((float) $vendorBill->tax_amount, 2) }}</th>
                                    </tr>
                                @endif
                                @if($vendorBill->discount_amount > 0)
                                    <tr>
                                        <th colspan="5" class="text-end text-success">{{ __('procurement.discount_amount') }}:</th>
                                        <th class="text-end pe-3 font-monospace text-success">-৳{{ number_format((float) $vendorBill->discount_amount, 2) }}</th>
                                    </tr>
                                @endif
                                <tr class="table-primary">
                                    <th colspan="5" class="text-end fw-bold fs-6">{{ __('procurement.net_payable') }}:</th>
                                    <th class="text-end pe-3 fw-bold text-primary fs-5 font-monospace">৳{{ number_format((float) $vendorBill->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Allocations Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-cash-stack me-1 text-success"></i> {{ __('procurement.payment_allocations') }}
                    </h6>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge {{ $vendorBill->isFullyPaid() ? 'bg-success' : ($vendorBill->isPartiallyPaid() ? 'bg-warning text-dark' : 'bg-secondary') }}">
                            {{ __('procurement.total_paid') }}: ৳{{ number_format((float) $vendorBill->paid_amount, 2) }} / ৳{{ number_format((float) $vendorBill->total_amount, 2) }}
                        </span>
                        @if((Auth::user()->hasPermission('manage_vendor_payments') || Auth::user()->hasRole('super_admin')) && $vendorBill->status === 'approved' && ! $vendorBill->isFullyPaid())
                            <a href="{{ route('procurement.payments.create', ['vendor_id' => $vendorBill->vendor_id, 'bill_id' => $vendorBill->id]) }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-plus-circle me-1"></i> {{ __('procurement.pay_bill') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('procurement.payment_number') }}</th>
                                    <th>{{ __('procurement.payment_date') }}</th>
                                    <th>{{ __('procurement.payment_method') }}</th>
                                    <th class="text-end">{{ __('procurement.allocated_amount') }}</th>
                                    <th class="text-center">{{ __('common.status') }}</th>
                                    <th class="text-end pe-3">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vendorBill->allocations as $alloc)
                                    <tr>
                                        <td>
                                            @if($alloc->payment)
                                                <a href="{{ route('procurement.payments.show', $alloc->payment) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                                    {{ $alloc->payment->payment_number }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $alloc->payment?->payment_date?->format('d M Y') ?? '-' }}</td>
                                        <td>
                                            @if($alloc->payment)
                                                <span class="badge bg-light text-dark border">
                                                    {{ \App\Models\VendorPayment::supportedPaymentMethods()[$alloc->payment->payment_method] ?? ucfirst($alloc->payment->payment_method) }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-success">৳{{ number_format((float) $alloc->allocated_amount, 2) }}</td>
                                        <td class="text-center">{!! $alloc->payment?->status_badge ?? '<span class="badge bg-light">-</span>' !!}</td>
                                        <td class="text-end pe-3">
                                            @if($alloc->payment)
                                                <a href="{{ route('procurement.payments.show', $alloc->payment) }}" class="btn btn-outline-info btn-sm" title="{{ __('common.view') }}">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary opacity-50"></i>
                                            {{ __('procurement.payment_list') }} (0)
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Supporting Document & Notes -->
            <div class="row g-4 mb-4">
                @if($vendorBill->document_path)
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="mb-0 fw-bold text-dark">
                                    <i class="bi bi-paperclip me-1 text-primary"></i> {{ __('procurement.document_evidence') }}
                                </h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                                <i class="bi bi-file-earmark-arrow-down fs-1 text-primary mb-2"></i>
                                <div class="small text-muted mb-3">{{ basename($vendorBill->document_path) }}</div>
                                <a href="{{ Storage::url($vendorBill->document_path) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-download me-1"></i> {{ __('procurement.download_attachment') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                @if($vendorBill->notes)
                    <div class="{{ $vendorBill->document_path ? 'col-md-6' : 'col-12' }}">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="mb-0 fw-bold text-dark">
                                    <i class="bi bi-card-text me-1 text-primary"></i> {{ __('procurement.notes') }}
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted mb-0">{{ $vendorBill->notes }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Metadata & Audit Trail -->
        <div class="col-lg-4">
            <!-- Metadata Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-info-square me-1 text-primary"></i> {{ __('procurement.bill_details') }}
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 g-2">
                        <dt class="col-5 text-muted small">{{ __('procurement.invoice_reference') }}</dt>
                        <dd class="col-7 font-monospace fw-bold text-dark">{{ $vendorBill->vendor_invoice_number }}</dd>

                        <dt class="col-5 text-muted small">{{ __('procurement.bill_date') }}</dt>
                        <dd class="col-7">{{ $vendorBill->bill_date?->format('d M Y') }}</dd>

                        <dt class="col-5 text-muted small">{{ __('procurement.due_date') }}</dt>
                        <dd class="col-7">
                            @if($vendorBill->due_date)
                                <span class="{{ ($vendorBill->due_date < now()->toDateString() && $vendorBill->status === 'approved') ? 'text-danger fw-bold' : '' }}">
                                    {{ $vendorBill->due_date->format('d M Y') }}
                                </span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted small">{{ __('procurement.payment_status') }}</dt>
                        <dd class="col-7">
                            <span class="badge bg-warning-subtle text-dark border">{{ ucfirst($vendorBill->payment_status) }}</span>
                        </dd>

                        <dt class="col-5 text-muted small">{{ __('events.event') }}</dt>
                        <dd class="col-7 small">
                            @if($vendorBill->event)
                                <span class="badge bg-light text-dark border">{{ $vendorBill->event->title_en }}</span>
                            @else
                                <span class="text-muted">{{ __('procurement.type_general_fund') }}</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted small">{{ __('procurement.select_budget') }}</dt>
                        <dd class="col-7 small">
                            @if($vendorBill->budget)
                                <span class="badge bg-light text-dark border">{{ $vendorBill->budget->title_en }}</span>
                            @else
                                <span class="text-muted">{{ __('procurement.unbudgeted') }}</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Linked Purchase Order Card -->
            @if($vendorBill->purchaseOrder)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-box-seam me-1 text-primary"></i> {{ __('procurement.purchase_orders') }}
                        </h6>
                        <a href="{{ route('procurement.orders.show', $vendorBill->purchaseOrder) }}" class="btn btn-sm btn-link text-decoration-none p-0">
                            {{ __('common.view') }} <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="fw-bold font-monospace text-primary mb-1">{{ $vendorBill->purchaseOrder->order_number }}</div>
                        <div class="small text-muted mb-2">{{ __('procurement.total_order_amount') }}: <strong>৳{{ number_format((float) $vendorBill->purchaseOrder->total_amount, 2) }}</strong></div>
                        {!! $vendorBill->purchaseOrder->status_badge !!}
                    </div>
                </div>
            @endif

            <!-- Accounting Posting (General Ledger) Card -->
            @if($vendorBill->journal_entry_id)
                <div class="card border-0 shadow-sm mb-4 border-start border-4 border-success">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-success">
                            <i class="bi bi-journal-check me-1"></i> {{ __('procurement.journal_entry') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-1">{{ __('procurement.approve_bill_confirm') }}</div>
                        <div class="fw-bold font-monospace text-dark mb-2">
                            Entry #{{ $vendorBill->journal_entry_id }}
                        </div>
                        <span class="badge bg-success-subtle text-success border">{{ __('procurement.status_approved') }}</span>

                        @if($vendorBill->reversal_journal_entry_id)
                            <div class="mt-3 pt-3 border-top">
                                <div class="text-danger small fw-bold">{{ __('procurement.reversal_entry') }}</div>
                                <div class="font-monospace text-dark">Entry #{{ $vendorBill->reversal_journal_entry_id }}</div>
                                <span class="badge bg-danger-subtle text-danger border">{{ __('procurement.status_cancelled') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Audit Trail Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-clock-history me-1 text-primary"></i> {{ __('procurement.approval_audit_trail') }}
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <!-- Created -->
                        <li class="mb-3 d-flex align-items-start">
                            <i class="bi bi-plus-circle text-primary me-2 mt-1"></i>
                            <div>
                                <div class="fw-bold">{{ __('common.created') }}</div>
                                <div class="text-muted">{{ $vendorBill->creator?->name ?? '-' }}</div>
                                <div class="text-muted small">{{ $vendorBill->created_at->format('d M Y, h:i A') }}</div>
                            </div>
                        </li>

                        <!-- Submitted -->
                        @if($vendorBill->submitted_at)
                            <li class="mb-3 d-flex align-items-start">
                                <i class="bi bi-send text-info me-2 mt-1"></i>
                                <div>
                                    <div class="fw-bold">{{ __('procurement.status_submitted') }}</div>
                                    <div class="text-muted">{{ $vendorBill->submitter?->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $vendorBill->submitted_at->format('d M Y, h:i A') }}</div>
                                </div>
                            </li>
                        @endif

                        <!-- Verified -->
                        @if($vendorBill->verified_at)
                            <li class="mb-3 d-flex align-items-start">
                                <i class="bi bi-check2-circle text-info me-2 mt-1"></i>
                                <div>
                                    <div class="fw-bold">{{ __('procurement.status_verified') }}</div>
                                    <div class="text-muted">{{ $vendorBill->verifier?->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $vendorBill->verified_at->format('d M Y, h:i A') }}</div>
                                    @if($vendorBill->verification_notes)
                                        <div class="fst-italic text-secondary mt-1 bg-light p-1 rounded">"{{ $vendorBill->verification_notes }}"</div>
                                    @endif
                                </div>
                            </li>
                        @endif

                        <!-- Approved -->
                        @if($vendorBill->approved_at)
                            <li class="mb-3 d-flex align-items-start">
                                <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                <div>
                                    <div class="fw-bold">{{ __('procurement.status_approved') }}</div>
                                    <div class="text-muted">{{ $vendorBill->approver?->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $vendorBill->approved_at->format('d M Y, h:i A') }}</div>
                                </div>
                            </li>
                        @endif

                        <!-- Rejected -->
                        @if($vendorBill->rejected_at)
                            <li class="mb-3 d-flex align-items-start">
                                <i class="bi bi-x-circle-fill text-danger me-2 mt-1"></i>
                                <div>
                                    <div class="fw-bold">{{ __('procurement.status_rejected') }}</div>
                                    <div class="text-muted">{{ $vendorBill->rejector?->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $vendorBill->rejected_at->format('d M Y, h:i A') }}</div>
                                </div>
                            </li>
                        @endif

                        <!-- Cancelled -->
                        @if($vendorBill->cancelled_at)
                            <li class="d-flex align-items-start">
                                <i class="bi bi-slash-circle-fill text-secondary me-2 mt-1"></i>
                                <div>
                                    <div class="fw-bold">{{ __('procurement.status_cancelled') }}</div>
                                    <div class="text-muted">{{ $vendorBill->canceller?->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $vendorBill->cancelled_at->format('d M Y, h:i A') }}</div>
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
        <div class="modal-content">
            <form action="{{ route('procurement.bills.submit', $vendorBill) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="submitModalLabel">{{ __('procurement.submit_for_approval') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('procurement.submit_budget_confirm') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('procurement.submit_for_approval') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Verify Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.bills.verify', $vendorBill) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="verifyModalLabel">{{ __('procurement.verify_bill') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('procurement.verify_bill_confirm') }}</p>
                    <div class="mb-3">
                        <label for="verification_notes" class="form-label small fw-bold">{{ __('procurement.verification_notes') }}</label>
                        <textarea name="verification_notes" id="verification_notes" rows="3" class="form-control"
                                  placeholder="{{ __('procurement.verification_notes_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-info text-white">{{ __('procurement.verify_bill') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.bills.approve', $vendorBill) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">{{ __('procurement.approve_bill') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('procurement.approve_bill_confirm') }}</p>
                    <div class="alert alert-warning border-0 small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Approving this bill records an obligation in Accounts Payable (GL Code 2010). Bank/Cash accounts are NOT disbursed until an authorized payment voucher is issued.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('procurement.approve_bill') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.bills.reject', $vendorBill) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="rejectModalLabel">{{ __('procurement.reject_bill') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('procurement.reject_bill_confirm') }}</p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label small fw-bold">
                            {{ __('procurement.rejection_reason') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="3" class="form-control"
                                  placeholder="{{ __('procurement.rejection_reason_placeholder') }}" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('procurement.reject_bill') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.bills.cancel', $vendorBill) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="cancelModalLabel">{{ __('procurement.cancel_bill') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('procurement.cancel_bill_confirm') }}</p>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label small fw-bold">
                            {{ __('procurement.cancellation_reason') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="cancellation_reason" id="cancellation_reason" rows="3" class="form-control"
                                  placeholder="{{ __('procurement.cancellation_reason_placeholder') }}" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('procurement.cancel_bill') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
