@extends('layouts.app')

@section('title', __('procurement.create_request'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-cart-plus text-primary me-2"></i>{{ __('procurement.create_request') }}
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('procurement.requests.index') }}">{{ __('procurement.purchase_requests') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('procurement.create_request') }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('procurement.requests.index') }}" class="btn btn-outline-secondary btn-sm">
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

    <form action="{{ route('procurement.requests.store') }}" method="POST" id="requestForm">
        @csrf

        <!-- Requisition Overview -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-info-circle me-1 text-primary"></i> {{ __('procurement.request_details') }}
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Title -->
                    <div class="col-md-6">
                        <label for="title" class="form-label small fw-bold">
                            {{ __('procurement.request_title') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" placeholder="{{ __('procurement.request_title_placeholder') }}" required autofocus>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Request Date -->
                    <div class="col-md-3">
                        <label for="request_date" class="form-label small fw-bold">
                            {{ __('procurement.request_date') }} <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="request_date" id="request_date" class="form-control @error('request_date') is-invalid @enderror"
                               value="{{ old('request_date', date('Y-m-d')) }}" required>
                        @error('request_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Requested By -->
                    <div class="col-md-3">
                        <label for="requested_by" class="form-label small fw-bold">{{ __('procurement.requested_by') }}</label>
                        <select name="requested_by" id="requested_by" class="form-select @error('requested_by') is-invalid @enderror">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('requested_by', auth()->id()) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('requested_by')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Scope: Event -->
                    <div class="col-md-6">
                        <label for="event_id" class="form-label small fw-bold">{{ __('events.event') }} ({{ __('procurement.scope') }})</label>
                        <select name="event_id" id="event_id" class="form-select @error('event_id') is-invalid @enderror">
                            <option value="">-- {{ __('procurement.type_general_fund') }} --</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ $event->title_en }} ({{ $event->event_year }})
                                </option>
                            @endforeach
                        </select>
                        @error('event_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Budget Link -->
                    <div class="col-md-6">
                        <label for="budget_id" class="form-label small fw-bold">{{ __('procurement.select_budget') }}</label>
                        <select name="budget_id" id="budget_id" class="form-select @error('budget_id') is-invalid @enderror">
                            <option value="">-- {{ __('procurement.unbudgeted') }} / {{ __('common.none') }} --</option>
                            @foreach($approvedBudgets as $budget)
                                <option value="{{ $budget->id }}" {{ old('budget_id') == $budget->id ? 'selected' : '' }}
                                        data-event="{{ $budget->event_id }}" data-type="{{ $budget->budget_type }}">
                                    {{ $budget->title_en }} (৳{{ number_format((float) $budget->total_amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                        @error('budget_id')
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
                    <i class="bi bi-list-check me-1 text-primary"></i> {{ __('procurement.line_items') }}
                </h6>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddItem">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('procurement.add_line_item') }}
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 28%;">{{ __('procurement.item_description') }} <span class="text-danger">*</span></th>
                                <th style="width: 25%;">{{ __('procurement.select_expense_account') }} <span class="text-danger">*</span></th>
                                <th style="width: 12%;">{{ __('procurement.quantity') }} <span class="text-danger">*</span></th>
                                <th style="width: 10%;">{{ __('procurement.unit') }}</th>
                                <th style="width: 12%;">{{ __('procurement.estimated_unit_price') }} <span class="text-danger">*</span></th>
                                <th style="width: 13%;" class="text-end">{{ __('procurement.estimated_total') }}</th>
                                <th style="width: 5%;" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTbody">
                            <!-- Items inserted dynamically -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end fw-bold">{{ __('procurement.total_estimated_amount') }}:</th>
                                <th class="text-end fw-bold text-primary fs-5" id="grandTotalDisplay">৳0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Justification & Notes -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-card-text me-1 text-primary"></i> {{ __('procurement.justification') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <textarea name="justification" id="justification" rows="4" class="form-control @error('justification') is-invalid @enderror"
                                  placeholder="{{ __('procurement.justification_placeholder') }}">{{ old('justification') }}</textarea>
                        @error('justification')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-journal-text me-1 text-primary"></i> {{ __('procurement.notes') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" id="notes" rows="4" class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="{{ __('procurement.notes_placeholder') }}">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit & Actions -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="{{ route('procurement.requests.index') }}" class="btn btn-outline-secondary">
                {{ __('common.cancel') }}
            </a>
            <button type="submit" class="btn btn-primary px-4" id="btnSaveRequest">
                <i class="bi bi-check-circle me-1"></i> {{ __('common.save') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var expenseAccounts = @json($expenseAccounts);
    var itemIndex = 0;

    function renderAccountOptions(selectedId) {
        var html = '<option value="">-- {{ __("procurement.select_expense_account") }} --</option>';
        $.each(expenseAccounts, function(i, acc) {
            var selected = (acc.id == selectedId) ? 'selected' : '';
            html += '<option value="' + acc.id + '" ' + selected + '>' + acc.account_name_en + '</option>';
        });
        return html;
    }

    function addItemRow(desc, accountId, qty, unit, price) {
        desc = desc || '';
        accountId = accountId || '';
        qty = qty || 1;
        unit = unit || 'pcs';
        price = price || '';

        var tr = `
            <tr class="item-row" data-index="${itemIndex}">
                <td>
                    <input type="text" name="items[${itemIndex}][description]" class="form-control form-control-sm item-desc"
                           value="${desc}" placeholder="{{ __('procurement.item_description_placeholder') }}" required>
                </td>
                <td>
                    <select name="items[${itemIndex}][financial_account_id]" class="form-select form-select-sm item-account" required>
                        ${renderAccountOptions(accountId)}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="items[${itemIndex}][quantity]"
                           class="form-control form-control-sm text-end item-qty" value="${qty}" required>
                </td>
                <td>
                    <input type="text" name="items[${itemIndex}][unit]" class="form-control form-control-sm item-unit"
                           value="${unit}" placeholder="pcs">
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="items[${itemIndex}][estimated_unit_price]"
                           class="form-control form-control-sm text-end item-price" value="${price}" placeholder="0.00" required>
                </td>
                <td class="text-end fw-bold font-monospace item-line-total">
                    ৳0.00
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-item" title="{{ __('common.delete') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#itemsTbody').append(tr);
        itemIndex++;
        calculateTotals();
    }

    function calculateTotals() {
        var grandTotal = 0;
        $('#itemsTbody tr.item-row').each(function() {
            var qty = parseFloat($(this).find('.item-qty').val()) || 0;
            var price = parseFloat($(this).find('.item-price').val()) || 0;
            var lineTotal = qty * price;
            $(this).find('.item-line-total').text('৳' + lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            grandTotal += lineTotal;
        });
        $('#grandTotalDisplay').text('৳' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    $('#btnAddItem').on('click', function() {
        addItemRow();
    });

    $(document).on('click', '.btn-remove-item', function() {
        if ($('#itemsTbody tr.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        } else {
            alert('{{ __("procurement.at_least_one_item_required") }}');
        }
    });

    $(document).on('input change', '.item-qty, .item-price', function() {
        calculateTotals();
    });

    // Populate old items if validation failed
    var oldItems = @json(old('items', []));
    if (oldItems && oldItems.length > 0) {
        $.each(oldItems, function(i, item) {
            addItemRow(item.description, item.financial_account_id, item.quantity, item.unit, item.estimated_unit_price);
        });
    } else {
        addItemRow();
    }

    $('#requestForm').on('submit', function() {
        var btn = $('#btnSaveRequest');
        if (typeof TempleUI !== 'undefined' && TempleUI.showButtonLoader) {
            TempleUI.showButtonLoader(btn, '{{ __('common.processing') }}...');
        } else {
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> {{ __('common.processing') }}...');
        }
    });
});
</script>
@endpush
