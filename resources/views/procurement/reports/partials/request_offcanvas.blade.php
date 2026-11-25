<div class="requisition-details-content">
    <!-- Header Card -->
    <div class="card border-0 bg-light rounded-4 p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <span class="font-monospace fw-bold fs-5 text-primary">{{ $purchaseRequest->request_number }}</span>
                <h5 class="fw-bold mt-1 mb-0 text-dark">{{ $purchaseRequest->title }}</h5>
            </div>
            <div>
                @php
                    $statusClass = match ($purchaseRequest->status) {
                        \App\Models\PurchaseRequest::STATUS_DRAFT => 'bg-secondary-subtle text-secondary border',
                        \App\Models\PurchaseRequest::STATUS_SUBMITTED => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                        \App\Models\PurchaseRequest::STATUS_APPROVED => 'bg-success-subtle text-success border border-success-subtle',
                        \App\Models\PurchaseRequest::STATUS_REJECTED => 'bg-danger-subtle text-danger border border-danger-subtle',
                        \App\Models\PurchaseRequest::STATUS_CANCELLED => 'bg-dark-subtle text-dark border',
                        default => 'bg-light text-dark border',
                    };
                @endphp
                <span class="badge {{ $statusClass }} px-3 py-2 fs-6">
                    {{ __('procurement.status_' . $purchaseRequest->status) ?? ucfirst($purchaseRequest->status) }}
                </span>
            </div>
        </div>

        @if($purchaseRequest->justification)
            <div class="mt-2 text-muted small border-top pt-2">
                <strong>{{ __('procurement.scope') }}:</strong> {{ $purchaseRequest->justification }}
            </div>
        @endif
    </div>

    <!-- Metadata Grid -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('procurement.requested_by_short') }}</small>
                <span class="fw-semibold text-dark">{{ $purchaseRequest->requester?->name ?? '—' }}</span>
                @if($purchaseRequest->requester?->email)
                    <br><small class="text-muted font-xs">{{ $purchaseRequest->requester->email }}</small>
                @endif
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('procurement.request_date_short') }}</small>
                <span class="fw-semibold text-dark">{{ $purchaseRequest->request_date ? $purchaseRequest->request_date->format('d M Y') : '—' }}</span>
                @if($purchaseRequest->required_date)
                    <br><small class="text-muted font-xs">{{ __('procurement.required_date_short') }}: {{ $purchaseRequest->required_date->format('d M Y') }}</small>
                @endif
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('events.event') }}</small>
                <span class="fw-semibold text-dark">{{ $purchaseRequest->event?->title_en ?? '—' }}</span>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('procurement.select_budget') }}</small>
                <span class="fw-semibold text-dark">{{ $purchaseRequest->budget?->title ?? '—' }}</span>
            </div>
        </div>
    </div>

    <!-- Approval / Rejection Info -->
    @if($purchaseRequest->status === \App\Models\PurchaseRequest::STATUS_APPROVED)
        <div class="alert alert-success border-success-subtle rounded-3 py-2 px-3 mb-3 d-flex align-items-center justify-content-between">
            <div>
                <i class="bi bi-check-circle-fill me-1"></i>
                <strong>{{ __('procurement.status_approved') }}:</strong> {{ $purchaseRequest->approver?->name ?? 'Authorized' }}
            </div>
            <small class="text-muted">{{ $purchaseRequest->approved_at ? $purchaseRequest->approved_at->format('d M Y, h:i A') : '' }}</small>
        </div>
    @elseif($purchaseRequest->status === \App\Models\PurchaseRequest::STATUS_REJECTED)
        <div class="alert alert-danger border-danger-subtle rounded-3 py-2 px-3 mb-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <i class="bi bi-x-circle-fill me-1"></i>
                    <strong>{{ __('procurement.status_rejected') }}:</strong> {{ $purchaseRequest->rejecter?->name ?? 'Authorized' }}
                </div>
                <small class="text-muted">{{ $purchaseRequest->rejected_at ? $purchaseRequest->rejected_at->format('d M Y, h:i A') : '' }}</small>
            </div>
            @if($purchaseRequest->rejection_reason)
                <div class="mt-1 pt-1 border-top border-danger-subtle font-xs">
                    <strong>{{ __('procurement.rejection_reason') }}:</strong> {{ $purchaseRequest->rejection_reason }}
                </div>
            @endif
        </div>
    @endif

    <!-- Related Purchase Order -->
    @if($purchaseRequest->purchaseOrder)
        <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 rounded-4 p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-primary text-white mb-1">{{ __('procurement.related_po_information') }}</span>
                    <h6 class="fw-bold mb-0 text-dark">{{ $purchaseRequest->purchaseOrder->order_number }}</h6>
                    <small class="text-muted">{{ $purchaseRequest->purchaseOrder->vendor?->name }} &bull; ৳{{ number_format((float) $purchaseRequest->purchaseOrder->total_amount, 2) }}</small>
                </div>
                <a href="{{ route('procurement.orders.show', $purchaseRequest->purchaseOrder->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-1"></i>{{ __('common.view') }}
                </a>
            </div>
        </div>
    @endif

    <!-- Items Table -->
    <h6 class="fw-bold text-dark mt-3 mb-2">
        <i class="bi bi-list-check text-primary me-1"></i>{{ __('procurement.requested_items') }} ({{ $purchaseRequest->items->count() }})
    </h6>
    <div class="table-responsive border rounded-3 mb-3">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-2">{{ __('common.description') }}</th>
                    <th>{{ __('procurement.account_head_short') }}</th>
                    <th class="text-center">{{ __('common.quantity') }}</th>
                    <th class="text-end">{{ __('procurement.unit_price') }}</th>
                    <th class="text-end pe-2">{{ __('procurement.total_price') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseRequest->items as $item)
                    <tr>
                        <td class="ps-2">
                            <span class="fw-semibold text-dark">{{ $item->description }}</span>
                            @if($item->notes)
                                <br><small class="text-muted font-xs">{{ $item->notes }}</small>
                            @endif
                        </td>
                        <td>
                            <small class="text-secondary">{{ $item->expenseAccount?->account_name ?? '—' }}</small>
                        </td>
                        <td class="text-center">
                            {{ number_format((float) $item->quantity, 2) }} {{ $item->unit ?? '' }}
                        </td>
                        <td class="text-end">
                            ৳{{ number_format((float) $item->estimated_unit_price, 2) }}
                        </td>
                        <td class="text-end pe-2 fw-bold text-primary">
                            ৳{{ number_format((float) $item->estimated_total, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">No items listed.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end ps-2">{{ __('common.total') }}:</td>
                    <td class="text-end pe-2 text-primary fs-6">৳{{ number_format((float) $purchaseRequest->total_estimated_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Actions / Links -->
    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="offcanvas">
            {{ __('common.close') }}
        </button>
        <a href="{{ route('procurement.requests.show', $purchaseRequest->id) }}" class="btn btn-primary rounded-pill px-3 shadow-sm" target="_blank">
            <i class="bi bi-arrow-up-right-circle me-1"></i>{{ __('common.view') }} {{ __('procurement.request_list') }}
        </a>
    </div>
</div>
