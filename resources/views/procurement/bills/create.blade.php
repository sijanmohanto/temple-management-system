@extends('layouts.app')

@section('title', __('procurement.create_bill'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-file-earmark-plus text-primary me-2"></i>{{ __('procurement.create_bill') }}
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('procurement.bills.index') }}">{{ __('procurement.vendor_bills') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('procurement.create_bill') }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('procurement.bills.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('procurement.bills.store') }}" method="POST" enctype="multipart/form-data" id="billForm">
        @csrf

        <!-- Optional PO Linkage Banner / Selector -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-link-45deg me-1 text-primary"></i> {{ __('procurement.select_po_optional') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <label for="purchase_order_id" class="form-label small fw-bold">{{ __('procurement.purchase_orders') }}</label>
                        <select name="purchase_order_id" id="purchase_order_id" class="form-select @error('purchase_order_id') is-invalid @enderror">
                            <option value="">-- {{ __('common.none') }} ({{ __('procurement.type_general_fund') }} / Direct Bill) --</option>
                            @foreach($availableOrders as $po)
                                <option value="{{ $po->id }}" {{ (old('purchase_order_id', $linkedPo?->id) == $po->id) ? 'selected' : '' }}>
                                    {{ $po->order_number }} — {{ $po->vendor->name }} (৳{{ number_format((float) $po->total_amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small">{{ __('procurement.order_status_ineligible_for_billing') }}</div>
                        @error('purchase_order_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 d-flex align-items-end pt-3">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btnLoadPoItems" style="display: none;">
                            <i class="bi bi-arrow-repeat me-1"></i> {{ __('procurement.load_po_items') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendor & Bill Info Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-info-circle me-1 text-primary"></i> {{ __('procurement.vendor_bill_information') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Vendor Selector -->
                    <div class="col-md-6">
                        <label for="vendor_id" class="form-label small fw-bold">
                            {{ __('procurement.vendor') }} <span class="text-danger">*</span>
                        </label>
                        <select name="vendor_id" id="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required>
                            <option value="">-- {{ __('procurement.vendor_required') }} --</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ (old('vendor_id', $linkedPo?->vendor_id) == $vendor->id) ? 'selected' : '' }}>
                                    {{ $vendor->name }}{{ $vendor->phone ? ' ('.$vendor->phone.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('vendor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Vendor Invoice Number -->
                    <div class="col-md-6">
                        <label for="vendor_invoice_number" class="form-label small fw-bold">
                            {{ __('procurement.vendor_invoice_number') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="vendor_invoice_number" id="vendor_invoice_number"
                               class="form-control font-monospace @error('vendor_invoice_number') is-invalid @enderror"
                               value="{{ old('vendor_invoice_number') }}"
                               placeholder="{{ __('procurement.vendor_invoice_number_placeholder') }}" required>
                        @error('vendor_invoice_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Bill Date -->
                    <div class="col-md-3">
                        <label for="bill_date" class="form-label small fw-bold">
                            {{ __('procurement.bill_date') }} <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="bill_date" id="bill_date"
                               class="form-control @error('bill_date') is-invalid @enderror"
                               value="{{ old('bill_date', date('Y-m-d')) }}" required>
                        @error('bill_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Due Date -->
                    <div class="col-md-3">
                        <label for="due_date" class="form-label small fw-bold">
                            {{ __('procurement.due_date') }}
                        </label>
                        <input type="date" name="due_date" id="due_date"
                               class="form-control @error('due_date') is-invalid @enderror"
                               value="{{ old('due_date') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Scope: Event -->
                    <div class="col-md-3">
                        <label for="event_id" class="form-label small fw-bold">{{ __('events.event') }}</label>
                        <select name="event_id" id="event_id" class="form-select @error('event_id') is-invalid @enderror">
                            <option value="">-- {{ __('procurement.type_general_fund') }} --</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ (old('event_id', $linkedPo?->event_id) == $event->id) ? 'selected' : '' }}>
                                    {{ $event->title_en }} ({{ $event->event_year }})
                                </option>
                            @endforeach
                        </select>
                        @error('event_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Budget Link -->
                    <div class="col-md-3">
                        <label for="budget_id" class="form-label small fw-bold">{{ __('procurement.select_budget') }}</label>
                        <select name="budget_id" id="budget_id" class="form-select @error('budget_id') is-invalid @enderror">
                            <option value="">-- {{ __('procurement.unbudgeted') }} --</option>
                            @foreach($approvedBudgets as $budget)
                                <option value="{{ $budget->id }}" {{ (old('budget_id', $linkedPo?->budget_id) == $budget->id) ? 'selected' : '' }}>
                                    {{ $budget->title_en }}
                                </option>
                            @endforeach
                        </select>
                        @error('budget_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Supporting Document File Upload -->
                    <div class="col-md-12">
                        <label for="document" class="form-label small fw-bold">
                            {{ __('procurement.document_evidence') }}
                        </label>
                        <input type="file" name="document" id="document"
                               class="form-control @error('document') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text small">{{ __('procurement.document_evidence_help') }}</div>
                        @error('document')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-list-check me-1 text-primary"></i> {{ __('procurement.bill_items') }}
                </h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddItem">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('procurement.add_item') }}
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">{{ __('procurement.line_item_description') }} <span class="text-danger">*</span></th>
                                <th style="width: 25%;">{{ __('procurement.expense_category') }} <span class="text-danger">*</span></th>
                                <th style="width: 10%;">{{ __('procurement.quantity') }} <span class="text-danger">*</span></th>
                                <th style="width: 8%;">{{ __('procurement.unit') }}</th>
                                <th style="width: 12%;">{{ __('procurement.rate') }} <span class="text-danger">*</span></th>
                                <th style="width: 15%;" class="text-end">{{ __('procurement.item_total') }}</th>
                                <th style="width: 5%;" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTbody">
                            <!-- Injected dynamically -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end fw-bold">{{ __('procurement.subtotal') }}:</th>
                                <th class="text-end fw-bold font-monospace fs-6" id="subtotalDisplay">৳0.00</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end fw-bold align-middle">{{ __('procurement.tax_vat_amount') }}:</th>
                                <th class="text-end">
                                    <input type="number" step="0.01" min="0" name="tax_amount" id="taxInput"
                                           class="form-control form-control-sm text-end font-monospace"
                                           value="{{ old('tax_amount', 0) }}" placeholder="0.00">
                                </th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end fw-bold align-middle">{{ __('procurement.discount_amount') }}:</th>
                                <th class="text-end">
                                    <input type="number" step="0.01" min="0" name="discount_amount" id="discountInput"
                                           class="form-control form-control-sm text-end font-monospace"
                                           value="{{ old('discount_amount', 0) }}" placeholder="0.00">
                                </th>
                                <th></th>
                            </tr>
                            <tr class="table-primary">
                                <th colspan="5" class="text-end fw-bold">{{ __('procurement.net_payable') }}:</th>
                                <th class="text-end fw-bold text-primary fs-5" id="grandTotalDisplay">৳0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-card-text me-1 text-primary"></i> {{ __('procurement.notes') }}
                </h6>
            </div>
            <div class="card-body">
                <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror"
                          placeholder="{{ __('procurement.terms_conditions_placeholder') }}">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Submit & Actions -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="{{ route('procurement.bills.index') }}" class="btn btn-outline-secondary">
                {{ __('common.cancel') }}
            </a>
            <button type="submit" class="btn btn-primary px-4" id="btnSaveBill">
                <i class="bi bi-check-circle me-1"></i> {{ __('common.save') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let rowIndex = 0;
    const expenseAccounts = @json($expenseAccounts);

    function createRow(data = {}) {
        const idx = rowIndex++;
        const desc = data.item_description || '';
        const acctId = data.financial_account_id || '';
        const qty = data.remaining_quantity !== undefined ? data.remaining_quantity : (data.quantity || 1);
        const unit = data.unit || 'pcs';
        const price = data.unit_price || 0;
        const poItemId = data.id || data.purchase_order_item_id || '';

        let acctOptions = '<option value="">-- {{ __("procurement.select_expense_account") }} --</option>';
        expenseAccounts.forEach(function(acc) {
            const selected = (acc.id == acctId) ? 'selected' : '';
            acctOptions += `<option value="${acc.id}" ${selected}>${acc.account_code} - ${acc.name_en}</option>`;
        });

        const rowHtml = `
            <tr class="item-row" data-row="${idx}">
                <td>
                    <input type="text" name="items[${idx}][item_description]" class="form-control form-control-sm item-desc"
                           value="${desc}" placeholder="{{ __('procurement.line_item_description') }}" required>
                    <input type="hidden" name="items[${idx}][purchase_order_item_id]" value="${poItemId}">
                </td>
                <td>
                    <select name="items[${idx}][financial_account_id]" class="form-select form-select-sm item-account" required>
                        ${acctOptions}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.001" min="0.001" name="items[${idx}][quantity]"
                           class="form-control form-control-sm item-qty text-end" value="${qty}" required>
                </td>
                <td>
                    <input type="text" name="items[${idx}][unit]" class="form-control form-control-sm item-unit"
                           value="${unit}" placeholder="pcs">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="items[${idx}][unit_price]"
                           class="form-control form-control-sm item-price text-end font-monospace" value="${price}" required>
                </td>
                <td class="text-end fw-bold font-monospace item-total">
                    ৳0.00
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row p-1" title="{{ __('common.delete') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#itemsTbody').append(rowHtml);
        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0;
        $('#itemsTbody tr.item-row').each(function() {
            const qty = parseFloat($(this).find('.item-qty').val()) || 0;
            const price = parseFloat($(this).find('.item-price').val()) || 0;
            const total = qty * price;
            subtotal += total;
            $(this).find('.item-total').text('৳' + total.toFixed(2));
        });

        const tax = parseFloat($('#taxInput').val()) || 0;
        const discount = parseFloat($('#discountInput').val()) || 0;
        const grandTotal = Math.max(0, subtotal + tax - discount);

        $('#subtotalDisplay').text('৳' + subtotal.toFixed(2));
        $('#grandTotalDisplay').text('৳' + grandTotal.toFixed(2));
    }

    $('#btnAddItem').on('click', function() {
        createRow();
    });

    $(document).on('click', '.btn-remove-row', function() {
        if ($('#itemsTbody tr.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        } else {
            TempleUI.showToast('{{ __("procurement.at_least_one_item_required") }}', 'warning');
        }
    });

    $(document).on('input change', '.item-qty, .item-price, #taxInput, #discountInput', function() {
        calculateTotals();
    });

    function loadPoItems(poId) {
        if (!poId) {
            $('#btnLoadPoItems').hide();
            return;
        }

        $('#btnLoadPoItems').show();
        $.ajax({
            url: '{{ url("/procurement/bills/po-items") }}/' + poId,
            type: 'GET',
            dataType: 'json',
            beforeSend: function() {
                TempleUI.showLoader();
            },
            success: function(res) {
                if (res.purchase_order) {
                    if (res.purchase_order.vendor_id) {
                        $('#vendor_id').val(res.purchase_order.vendor_id).trigger('change');
                    }
                    if (res.purchase_order.event_id) {
                        $('#event_id').val(res.purchase_order.event_id).trigger('change');
                    }
                    if (res.purchase_order.budget_id) {
                        $('#budget_id').val(res.purchase_order.budget_id).trigger('change');
                    }
                }

                if (res.items && res.items.length > 0) {
                    $('#itemsTbody').empty();
                    rowIndex = 0;
                    res.items.forEach(function(item) {
                        if (item.remaining_quantity > 0) {
                            createRow(item);
                        }
                    });
                    calculateTotals();
                    TempleUI.showToast(res.items.length + ' item(s) loaded from Purchase Order.', 'success');
                }
            },
            error: function(xhr) {
                TempleUI.showToast('Failed to load items from Purchase Order.', 'error');
            },
            complete: function() {
                TempleUI.hideLoader();
            }
        });
    }

    $('#purchase_order_id').on('change', function() {
        const poId = $(this).val();
        if (poId) {
            loadPoItems(poId);
        } else {
            $('#btnLoadPoItems').hide();
        }
    });

    $('#btnLoadPoItems').on('click', function() {
        const poId = $('#purchase_order_id').val();
        if (poId) {
            loadPoItems(poId);
        }
    });

    // Initial item row or pre-fill
    const prefillPoId = '{{ old("purchase_order_id", $linkedPo?->id) }}';
    if (prefillPoId) {
        loadPoItems(prefillPoId);
    } else {
        createRow();
    }
});
</script>
@endpush
