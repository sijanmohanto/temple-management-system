@extends('layouts.app')

@section('title', $purchaseOrder->order_number . ' - ' . __('procurement.order_details'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">{{ $purchaseOrder->order_number }}</h1>
                {!! $purchaseOrder->status_badge !!}
                @if($purchaseOrder->purchaseRequest)
                    <a href="{{ route('procurement.requests.show', $purchaseOrder->purchaseRequest) }}" class="badge bg-light text-primary border text-decoration-none">
                        <i class="bi bi-link-45deg me-1"></i>{{ $purchaseOrder->purchaseRequest->title }}
                    </a>
                @endif
            </div>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('procurement.orders.index') }}">{{ __('procurement.purchase_orders') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $purchaseOrder->order_number }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <!-- Print Order -->
            <a href="{{ route('procurement.orders.print', $purchaseOrder) }}" target="_blank" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-printer me-1"></i> {{ __('procurement.print_order') }}
            </a>

            <!-- Edit (Draft only) -->
            @if(Auth::user()->hasPermission('manage_purchase_orders') && $purchaseOrder->canBeEdited())
                <a href="{{ route('procurement.orders.edit', $purchaseOrder) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('common.edit') }}
                </a>
            @endif

            <!-- Approve (Draft only) -->
            @if(Auth::user()->hasPermission('approve_purchase_orders') && $purchaseOrder->canBeApproved())
                @if($purchaseOrder->created_by === Auth::id() && !Auth::user()->hasRole('super_admin'))
                    <button type="button" class="btn btn-success btn-sm disabled" title="{{ __('procurement.unauthorized_to_approve_own_order') }}" data-bs-toggle="tooltip">
                        <i class="bi bi-check-circle me-1"></i> {{ __('common.approve') }}
                    </button>
                @else
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bi bi-check-circle me-1"></i> {{ __('common.approve') }}
                    </button>
                @endif
            @endif

            <!-- Mark as Sent (Approved only) -->
            @if(Auth::user()->hasPermission('manage_purchase_orders') && $purchaseOrder->canBeSent())
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendModal">
                    <i class="bi bi-send me-1"></i> {{ __('procurement.mark_as_sent') }}
                </button>
            @endif

            <!-- Mark as Completed (Approved or Sent) -->
            @if(Auth::user()->hasPermission('manage_purchase_orders') && $purchaseOrder->canBeCompleted())
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#completeModal">
                    <i class="bi bi-check2-all me-1"></i> {{ __('procurement.mark_as_completed') }}
                </button>
            @endif

            <!-- Cancel Order -->
            @if(Auth::user()->hasPermission('manage_purchase_orders') && $purchaseOrder->canBeCancelled())
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-slash-circle me-1"></i> {{ __('common.cancel') }}
                </button>
            @endif

            <a href="{{ route('procurement.orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Separation of Duties Warning if Creator is Viewing Draft -->
    @if($purchaseOrder->status === 'draft' && $purchaseOrder->created_by === Auth::id() && !Auth::user()->hasRole('super_admin'))
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-info-circle-fill fs-5 me-3 text-info"></i>
            <div>
                <div class="fw-bold">{{ __('procurement.unauthorized_to_approve_own_order') }}</div>
                <div class="small text-muted">{{ __('procurement.approve_order_confirm') }}</div>
            </div>
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
                    <a href="{{ route('procurement.vendors.show', $purchaseOrder->vendor) }}" class="btn btn-sm btn-link text-decoration-none p-0">
                        {{ __('common.view') }} <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('procurement.vendor_name') }}</div>
                            <div class="fw-bold fs-6 text-dark">{{ $purchaseOrder->vendor->name }}</div>
                            @if($purchaseOrder->vendor->vendor_type)
                                <span class="badge bg-light text-dark border">{{ $purchaseOrder->vendor->vendor_type }}</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">{{ __('procurement.contact_person') }}</div>
                            <div class="fw-medium">{{ $purchaseOrder->vendor->contact_person ?: '-' }}</div>
                            <div class="small text-muted">
                                @if($purchaseOrder->vendor->phone)
                                    <div><i class="bi bi-telephone me-1"></i>{{ $purchaseOrder->vendor->phone }}</div>
                                @endif
                                @if($purchaseOrder->vendor->email)
                                    <div><i class="bi bi-envelope me-1"></i>{{ $purchaseOrder->vendor->email }}</div>
                                @endif
                            </div>
                        </div>
                        @if($purchaseOrder->vendor->address)
                            <div class="col-12">
                                <div class="text-muted small">{{ __('procurement.vendor_address') }}</div>
                                <div class="small">{{ $purchaseOrder->vendor->address }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-list-check me-1 text-primary"></i> {{ __('procurement.line_items') }}
                    </h6>
                    <span class="badge bg-primary-subtle text-primary">{{ $purchaseOrder->items->count() }} {{ __('procurement.items') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('procurement.item_description') }}</th>
                                    <th>{{ __('procurement.select_expense_account') }}</th>
                                    <th class="text-center">{{ __('procurement.quantity') }}</th>
                                    <th class="text-end">{{ __('procurement.unit_price') }}</th>
                                    <th class="text-end pe-3">{{ __('procurement.total_price') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->items as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $item->description }}</div>
                                            @if($item->notes)
                                                <small class="text-muted"><i class="bi bi-info me-1"></i>{{ $item->notes }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $item->expenseAccount?->account_name_en }}</div>
                                        </td>
                                        <td class="text-center font-monospace">
                                            {{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} {{ $item->unit }}
                                        </td>
                                        <td class="text-end font-monospace">
                                            ৳{{ number_format((float) $item->unit_price, 2) }}
                                        </td>
                                        <td class="text-end fw-bold font-monospace pe-3">
                                            ৳{{ number_format((float) $item->total_price, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end fw-bold">{{ __('procurement.subtotal') }}:</th>
                                    <th class="text-end fw-bold font-monospace pe-3">
                                        ৳{{ number_format((float) $purchaseOrder->subtotal, 2) }}
                                    </th>
                                </tr>
                                @if((float) $purchaseOrder->discount_amount > 0)
                                    <tr>
                                        <th colspan="4" class="text-end fw-bold text-danger">{{ __('procurement.discount_amount') }}:</th>
                                        <th class="text-end fw-bold font-monospace text-danger pe-3">
                                            -৳{{ number_format((float) $purchaseOrder->discount_amount, 2) }}
                                        </th>
                                    </tr>
                                @endif
                                <tr>
                                    <th colspan="4" class="text-end fw-bold fs-6">{{ __('procurement.total_order_amount') }}:</th>
                                    <th class="text-end fw-bold text-primary fs-5 pe-3 font-monospace">
                                        ৳{{ number_format((float) $purchaseOrder->total_amount, 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Terms & Conditions / Delivery Address / Notes -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-geo-alt me-1 text-primary"></i> {{ __('procurement.delivery_address') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0 text-muted">{{ $purchaseOrder->delivery_address ?: __('common.none') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-file-earmark-ruled me-1 text-primary"></i> {{ __('procurement.terms_conditions') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0 text-muted">{{ $purchaseOrder->terms_conditions ?: __('common.none') }}</p>
                        </div>
                    </div>
                </div>
                @if($purchaseOrder->notes)
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="mb-0 fw-bold text-dark">
                                    <i class="bi bi-journal-text me-1 text-primary"></i> {{ __('procurement.notes') }}
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0 text-muted">{{ $purchaseOrder->notes }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Order Metadata & Lifecycle Details -->
        <div class="col-lg-4">
            <!-- Metadata Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-1 text-primary"></i> {{ __('procurement.order_details') }}
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">{{ __('procurement.order_number') }}</dt>
                        <dd class="col-sm-7 fw-bold font-monospace">{{ $purchaseOrder->order_number }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.order_date') }}</dt>
                        <dd class="col-sm-7">{{ $purchaseOrder->order_date?->format('d M Y') }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.expected_delivery_date') }}</dt>
                        <dd class="col-sm-7">{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d M Y') : '-' }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.scope') }}</dt>
                        <dd class="col-sm-7">
                            @if($purchaseOrder->event)
                                <span class="badge bg-light text-dark border">{{ $purchaseOrder->event->title_en }}</span>
                            @else
                                <span class="badge bg-light text-secondary border">{{ __('procurement.type_general_fund') }}</span>
                            @endif
                        </dd>

                        @if($purchaseOrder->purchaseRequest)
                            <dt class="col-sm-5 text-muted small">{{ __('procurement.source_request') }}</dt>
                            <dd class="col-sm-7">
                                <a href="{{ route('procurement.requests.show', $purchaseOrder->purchaseRequest) }}" class="text-decoration-none">
                                    {{ $purchaseOrder->purchaseRequest->title }}
                                </a>
                            </dd>
                        @endif

                        @if($purchaseOrder->budget)
                            <dt class="col-sm-5 text-muted small">{{ __('procurement.select_budget') }}</dt>
                            <dd class="col-sm-7">
                                <a href="{{ route('procurement.budgets.show', $purchaseOrder->budget) }}" class="text-decoration-none">
                                    {{ $purchaseOrder->budget->title_en }}
                                </a>
                            </dd>
                        @endif

                        <hr class="my-2">

                        <dt class="col-sm-5 text-muted small">{{ __('common.created_by') }}</dt>
                        <dd class="col-sm-7 small">{{ $purchaseOrder->creator?->name ?? '-' }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('common.created_at') }}</dt>
                        <dd class="col-sm-7 small">{{ $purchaseOrder->created_at->format('d M Y, h:i A') }}</dd>

                        @if($purchaseOrder->approved_by)
                            <dt class="col-sm-5 text-muted small">{{ __('procurement.status_approved') }}</dt>
                            <dd class="col-sm-7 small text-success">{{ $purchaseOrder->approver?->name }} ({{ $purchaseOrder->approved_at?->format('d M Y') }})</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.orders.approve', $purchaseOrder) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveModalLabel">
                        <i class="bi bi-check-circle me-1"></i> {{ __('common.approve') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('procurement.approve_order_confirm') }}</p>
                    <div class="mt-3 p-3 bg-light rounded">
                        <div><strong>{{ __('procurement.order_number') }}:</strong> <span class="font-monospace">{{ $purchaseOrder->order_number }}</span></div>
                        <div><strong>{{ __('procurement.vendor') }}:</strong> {{ $purchaseOrder->vendor->name }}</div>
                        <div><strong>{{ __('procurement.total_order_amount') }}:</strong> ৳{{ number_format((float) $purchaseOrder->total_amount, 2) }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle me-1"></i> {{ __('common.approve') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Send Modal -->
<div class="modal fade" id="sendModal" tabindex="-1" aria-labelledby="sendModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.orders.send', $purchaseOrder) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="sendModalLabel">
                        <i class="bi bi-send me-1"></i> {{ __('procurement.mark_as_sent') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('procurement.send_order_confirm') }}</p>
                    <div class="mt-3 p-3 bg-light rounded">
                        <div><strong>{{ __('procurement.order_number') }}:</strong> <span class="font-monospace">{{ $purchaseOrder->order_number }}</span></div>
                        <div><strong>{{ __('procurement.vendor') }}:</strong> {{ $purchaseOrder->vendor->name }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-send me-1"></i> {{ __('procurement.mark_as_sent') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.orders.complete', $purchaseOrder) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="completeModalLabel">
                        <i class="bi bi-check2-all me-1"></i> {{ __('procurement.mark_as_completed') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('procurement.complete_order_confirm') }}</p>
                    <div class="mt-3 p-3 bg-light rounded">
                        <div><strong>{{ __('procurement.order_number') }}:</strong> <span class="font-monospace">{{ $purchaseOrder->order_number }}</span></div>
                        <div><strong>{{ __('procurement.vendor') }}:</strong> {{ $purchaseOrder->vendor->name }}</div>
                        <div><strong>{{ __('procurement.total_order_amount') }}:</strong> ৳{{ number_format((float) $purchaseOrder->total_amount, 2) }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check2-all me-1"></i> {{ __('procurement.mark_as_completed') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.orders.cancel', $purchaseOrder) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelModalLabel">
                        <i class="bi bi-slash-circle me-1"></i> {{ __('common.cancel') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('procurement.cancel_order_confirm') }}</p>
                    <div class="mb-3">
                        <label for="cancel_reason" class="form-label fw-bold">{{ __('procurement.rejection_reason') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="cancel_reason" name="reason" rows="3" required placeholder="{{ __('procurement.rejection_reason_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-slash-circle me-1"></i> {{ __('common.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
