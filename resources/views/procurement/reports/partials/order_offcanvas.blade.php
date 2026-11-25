<div class="order-details-content">
    <!-- Header Card -->
    <div class="card border-0 bg-light rounded-4 p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <span class="font-monospace fw-bold fs-5 text-primary">{{ $purchaseOrder->order_number }}</span>
                <h6 class="fw-bold mt-1 mb-0 text-dark">{{ $purchaseOrder->vendor?->name }}</h6>
                @if($purchaseOrder->vendor?->vendor_code)
                    <small class="text-muted font-monospace">{{ $purchaseOrder->vendor->vendor_code }}</small>
                @endif
            </div>
            <div>
                @php
                    $statusClass = match ($purchaseOrder->status) {
                        \App\Models\PurchaseOrder::STATUS_DRAFT => 'bg-secondary-subtle text-secondary border',
                        \App\Models\PurchaseOrder::STATUS_APPROVED => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                        \App\Models\PurchaseOrder::STATUS_SENT => 'bg-primary-subtle text-primary border border-primary-subtle',
                        \App\Models\PurchaseOrder::STATUS_COMPLETED => 'bg-success-subtle text-success border border-success-subtle',
                        \App\Models\PurchaseOrder::STATUS_CANCELLED => 'bg-danger-subtle text-danger border border-danger-subtle',
                        default => 'bg-light text-dark border',
                    };
                @endphp
                <span class="badge {{ $statusClass }} px-3 py-2 fs-6">
                    {{ __('procurement.status_' . $purchaseOrder->status) ?? ucfirst($purchaseOrder->status) }}
                </span>
            </div>
        </div>

        @if($purchaseOrder->notes)
            <div class="mt-2 text-muted small border-top pt-2">
                <strong>{{ __('procurement.notes') }}:</strong> {{ $purchaseOrder->notes }}
            </div>
        @endif
    </div>

    <!-- Metadata Grid -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('procurement.order_date') }}</small>
                <span class="fw-semibold text-dark">{{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d M Y') : '—' }}</span>
                @if($purchaseOrder->expected_delivery_date)
                    <br><small class="text-muted font-xs">{{ __('procurement.delivery_date_short') }}: {{ $purchaseOrder->expected_delivery_date->format('d M Y') }}</small>
                @endif
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('procurement.related_requisition') }}</small>
                <span class="fw-semibold font-monospace text-primary">
                    {{ $purchaseOrder->purchaseRequest?->request_number ?? '—' }}
                </span>
                @if($purchaseOrder->purchaseRequest?->requester)
                    <br><small class="text-muted font-xs">By: {{ $purchaseOrder->purchaseRequest->requester->name }}</small>
                @endif
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('events.event') }} / {{ __('procurement.budgets') }}</small>
                <span class="fw-semibold text-dark">{{ $purchaseOrder->event?->title_en ?? ($purchaseOrder->budget?->title ?? '—') }}</span>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('procurement.status_approved') }}</small>
                <span class="fw-semibold text-dark">{{ $purchaseOrder->approver?->name ?? '—' }}</span>
            </div>
        </div>
        @if($purchaseOrder->delivery_address)
            <div class="col-12">
                <div class="p-2 border rounded-3 bg-white">
                    <small class="text-muted d-block">{{ __('procurement.delivery_address') }}</small>
                    <span class="text-dark small">{{ $purchaseOrder->delivery_address }}</span>
                </div>
            </div>
        @endif
    </div>

    <!-- Items Table -->
    <h6 class="fw-bold text-dark mt-3 mb-2">
        <i class="bi bi-box me-1 text-primary"></i>{{ __('procurement.ordered_items') }} ({{ $purchaseOrder->items->count() }})
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
                @forelse($purchaseOrder->items as $item)
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
                            ৳{{ number_format((float) $item->unit_price, 2) }}
                        </td>
                        <td class="text-end pe-2 fw-bold text-primary">
                            ৳{{ number_format((float) $item->line_total, 2) }}
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
                    <td class="text-end pe-2 text-primary fs-6">৳{{ number_format((float) $purchaseOrder->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Related Vendor Bills -->
    <h6 class="fw-bold text-dark mt-4 mb-2">
        <i class="bi bi-receipt me-1 text-primary"></i>{{ __('procurement.related_bills') }} ({{ $purchaseOrder->bills->count() }})
    </h6>
    @if($purchaseOrder->bills->isNotEmpty())
        <div class="table-responsive border rounded-3 mb-3">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-2">{{ __('procurement.bill_ref_short') }}</th>
                        <th>{{ __('procurement.bill_date') }}</th>
                        <th class="text-end">{{ __('procurement.bill_amount') }}</th>
                        <th class="text-center">{{ __('procurement.status') }}</th>
                        <th class="text-end pe-2">{{ __('common.view') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->bills as $bill)
                        <tr>
                            <td class="ps-2 font-monospace fw-semibold">{{ $bill->bill_number }}</td>
                            <td>{{ $bill->bill_date ? $bill->bill_date->format('d M Y') : '—' }}</td>
                            <td class="text-end fw-bold text-dark">৳{{ number_format((float) $bill->total_amount, 2) }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ ucfirst($bill->status) }}</span>
                            </td>
                            <td class="text-end pe-2">
                                <a href="{{ route('procurement.bills.show', $bill->id) }}" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" target="_blank">
                                    <i class="bi bi-arrow-up-right"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-light border rounded-3 text-muted small p-2 mb-3">
            <i class="bi bi-info-circle me-1"></i> {{ __('procurement.no_related_bills_found') }}
        </div>
    @endif

    <!-- Actions / Links -->
    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="offcanvas">
            {{ __('common.close') }}
        </button>
        <a href="{{ route('procurement.orders.show', $purchaseOrder->id) }}" class="btn btn-primary rounded-pill px-3 shadow-sm" target="_blank">
            <i class="bi bi-arrow-up-right-circle me-1"></i>{{ __('common.view') }} {{ __('procurement.order_list') }}
        </a>
    </div>
</div>
