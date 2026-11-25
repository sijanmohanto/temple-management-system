<div class="bill-details-content">
    <!-- Header Card -->
    <div class="card border-0 bg-light rounded-4 p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <span class="font-monospace fw-bold fs-5 text-primary">{{ $vendorBill->bill_number }}</span>
                @if($vendorBill->vendor_invoice_number)
                    <div class="text-muted small font-monospace">{{ __('procurement.vendor_invoice_number') }}: {{ $vendorBill->vendor_invoice_number }}</div>
                @endif
                <h6 class="fw-bold mt-1 mb-0 text-dark">{{ $vendorBill->vendor?->name }}</h6>
            </div>
            <div>
                @php
                    $isOverdue = ($outstandingBalance > 0.00 && $vendorBill->due_date && $vendorBill->due_date->isPast());
                @endphp
                @if($outstandingBalance <= 0.00)
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                        <i class="bi bi-check-circle me-1"></i>{{ __('procurement.payment_paid') }}
                    </span>
                @elseif($isOverdue)
                    <span class="badge bg-danger text-white px-3 py-2 fs-6">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ __('procurement.overdue') }}
                    </span>
                @elseif($postedAllocationsTotal > 0.00)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 fs-6">
                        <i class="bi bi-pie-chart me-1"></i>{{ __('procurement.payment_partial') }}
                    </span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6">
                        <i class="bi bi-clock me-1"></i>{{ __('procurement.payment_unpaid') }}
                    </span>
                @endif
            </div>
        </div>

        @if($vendorBill->notes)
            <div class="mt-2 text-muted small border-top pt-2">
                <strong>{{ __('procurement.notes') }}:</strong> {{ $vendorBill->notes }}
            </div>
        @endif
    </div>

    <!-- Financial Balances Grid -->
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="p-2 border rounded-3 bg-white text-center">
                <small class="text-muted d-block">{{ __('procurement.bill_amount_short') }}</small>
                <span class="fw-bold text-dark fs-6">৳{{ number_format((float) $vendorBill->total_amount, 2) }}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded-3 bg-white text-center">
                <small class="text-muted d-block">{{ __('procurement.paid_amount_short') }}</small>
                <span class="fw-bold text-success fs-6">৳{{ number_format((float) $postedAllocationsTotal, 2) }}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 border rounded-3 bg-white text-center">
                <small class="text-muted d-block">{{ __('procurement.outstanding_short') }}</small>
                <span class="fw-bold {{ $outstandingBalance > 0.00 ? 'text-danger' : 'text-muted' }} fs-6">
                    ৳{{ number_format((float) $outstandingBalance, 2) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('procurement.bill_date') }}</small>
                <span class="fw-semibold text-dark">{{ $vendorBill->bill_date ? $vendorBill->bill_date->format('d M Y') : '—' }}</span>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('procurement.due_date') }}</small>
                <span class="fw-semibold {{ $isOverdue ? 'text-danger' : 'text-dark' }}">
                    {{ $vendorBill->due_date ? $vendorBill->due_date->format('d M Y') : '—' }}
                    @if($isOverdue)
                        <span class="badge bg-danger text-white ms-1 font-xs">{{ __('procurement.overdue') }}</span>
                    @endif
                </span>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('procurement.related_po_information') }}</small>
                <span class="fw-semibold font-monospace text-primary">
                    {{ $vendorBill->purchaseOrder?->order_number ?? '—' }}
                </span>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-white">
                <small class="text-muted d-block">{{ __('procurement.vendor_code') }}</small>
                <span class="fw-semibold font-monospace text-dark">
                    {{ $vendorBill->vendor?->vendor_code ?? '—' }}
                </span>
                @if($vendorBill->vendor?->phone)
                    <br><small class="text-muted font-xs"><i class="bi bi-telephone me-1"></i>{{ $vendorBill->vendor->phone }}</small>
                @endif
            </div>
        </div>
    </div>

    <!-- Bill Items -->
    <h6 class="fw-bold text-dark mt-3 mb-2">
        <i class="bi bi-list-columns-reverse me-1 text-primary"></i>{{ __('procurement.bill_items') }} ({{ $vendorBill->items->count() }})
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
                @forelse($vendorBill->items as $item)
                    <tr>
                        <td class="ps-2">
                            <span class="fw-semibold text-dark">{{ $item->description }}</span>
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
                        <td class="text-end pe-2 fw-bold text-dark">
                            ৳{{ number_format((float) $item->total_price, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">No line items.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end ps-2">{{ __('common.total') }}:</td>
                    <td class="text-end pe-2 text-dark fs-6">৳{{ number_format((float) $vendorBill->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Authoritative Payment History & Allocations -->
    <h6 class="fw-bold text-dark mt-4 mb-2">
        <i class="bi bi-cash-stack me-1 text-success"></i>{{ __('procurement.payment_history') }} ({{ $vendorBill->allocations->count() }})
    </h6>
    @if($vendorBill->allocations->isNotEmpty())
        <div class="table-responsive border rounded-3 mb-3">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-2">{{ __('procurement.payment_number') }}</th>
                        <th>{{ __('procurement.payment_date') }}</th>
                        <th>{{ __('procurement.payment_method_short') }}</th>
                        <th>{{ __('procurement.disbursed_account_short') }}</th>
                        <th class="text-end">{{ __('procurement.allocated_amount') }}</th>
                        <th class="text-center">{{ __('procurement.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendorBill->allocations as $alloc)
                        @php
                            $payment = $alloc->payment;
                            $isPosted = ($payment && $payment->status === \App\Models\VendorPayment::STATUS_POSTED);
                            $isReversed = ($payment && $payment->status === \App\Models\VendorPayment::STATUS_REVERSED);
                        @endphp
                        <tr class="{{ $isReversed ? 'table-light text-muted' : '' }}">
                            <td class="ps-2">
                                <span class="font-monospace fw-semibold {{ $isReversed ? 'text-decoration-line-through text-muted' : 'text-primary' }}">
                                    {{ $payment?->payment_number ?? '—' }}
                                </span>
                                @if($isReversed && $payment?->reversal_reason)
                                    <br><small class="text-danger font-xs"><i class="bi bi-arrow-counterclockwise me-1"></i>Reversed: {{ $payment->reversal_reason }}</small>
                                @endif
                            </td>
                            <td>{{ $payment?->payment_date ? $payment->payment_date->format('d M Y') : '—' }}</td>
                            <td>
                                <span class="badge bg-light text-dark border font-xs">
                                    {{ ucfirst(str_replace('_', ' ', $payment?->payment_method ?? '')) }}
                                </span>
                            </td>
                            <td>
                                <small class="text-secondary">{{ $payment?->financialAccount?->account_name ?? ($payment?->financialAccount?->name_en ?? '—') }}</small>
                            </td>
                            <td class="text-end fw-bold {{ $isPosted ? 'text-success' : ($isReversed ? 'text-decoration-line-through text-muted' : 'text-secondary') }}">
                                ৳{{ number_format((float) $alloc->allocated_amount, 2) }}
                            </td>
                            <td class="text-center">
                                @if($isPosted)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle font-xs">Posted</span>
                                @elseif($isReversed)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-xs">Reversed</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border font-xs">{{ ucfirst($payment?->status ?? '') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end ps-2">Net Posted Allocations:</td>
                        <td class="text-end text-success fs-6">৳{{ number_format((float) $postedAllocationsTotal, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="alert alert-light border rounded-3 text-muted small p-2 mb-3">
            <i class="bi bi-info-circle me-1"></i> {{ __('procurement.no_payments_recorded_for_bill') }}
        </div>
    @endif

    <!-- Actions / Links -->
    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="offcanvas">
            {{ __('common.close') }}
        </button>
        <a href="{{ route('procurement.bills.show', $vendorBill->id) }}" class="btn btn-primary rounded-pill px-3 shadow-sm" target="_blank">
            <i class="bi bi-arrow-up-right-circle me-1"></i>{{ __('common.view') }} {{ __('procurement.bill_list') }}
        </a>
    </div>
</div>
