@extends('layouts.app')

@section('title', __('procurement.create_budget'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-plus-circle text-primary me-2"></i>{{ __('procurement.create_budget') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('procurement.procurement_preview_desc') }}</p>
        </div>
        <div>
            <a href="{{ route('procurement.budgets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('procurement.budgets.store') }}" method="POST" id="budgetForm">
                @csrf

                <!-- Basic Information -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label required small fw-bold">{{ __('common.title') }} (English)</label>
                        <input type="text" name="title_en" class="form-control form-control-sm @error('title_en') is-invalid @enderror" value="{{ old('title_en') }}" placeholder="e.g. Annual Temple Maintenance & Seva Budget" required maxlength="200">
                        @error('title_en')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('common.title') }} (বাংলা)</label>
                        <input type="text" name="title_bn" class="form-control form-control-sm @error('title_bn') is-invalid @enderror" value="{{ old('title_bn') }}" placeholder="যেমন: বার্ষিক মন্দির পরিচালনা ও সেবা বাজেট" maxlength="200">
                        @error('title_bn')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required small fw-bold">{{ __('procurement.budget_type') }}</label>
                        <select name="budget_type" id="budgetTypeSelect" class="form-select form-select-sm @error('budget_type') is-invalid @enderror" required>
                            <option value="annual" {{ old('budget_type') === 'annual' ? 'selected' : '' }}>{{ __('procurement.type_annual') }}</option>
                            <option value="event" {{ old('budget_type') === 'event' ? 'selected' : '' }}>{{ __('procurement.type_event') }}</option>
                            <option value="general_fund" {{ old('budget_type') === 'general_fund' ? 'selected' : '' }}>{{ __('procurement.type_general_fund') }}</option>
                        </select>
                        @error('budget_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required small fw-bold">{{ __('procurement.fiscal_year') }}</label>
                        <input type="number" name="fiscal_year" class="form-control form-control-sm @error('fiscal_year') is-invalid @enderror" value="{{ old('fiscal_year', now()->year) }}" min="2020" max="2050" required>
                        @error('fiscal_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4" id="eventFieldWrapper" style="{{ old('budget_type') === 'event' ? '' : 'display: none;' }}">
                        <label class="form-label required small fw-bold">{{ __('events.event') }}</label>
                        <select name="event_id" id="eventSelect" class="form-select form-select-sm select2-basic @error('event_id') is-invalid @enderror">
                            <option value="">-- {{ __('procurement.select_event') }} --</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ $event->title_en }} ({{ $event->category?->name_en ?? 'Event' }})
                                </option>
                            @endforeach
                        </select>
                        @error('event_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold">{{ __('common.notes') }}</label>
                        <textarea name="notes" class="form-control form-control-sm @error('notes') is-invalid @enderror" rows="2" placeholder="{{ __('common.notes') }}...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Dynamic Line Items Section -->
                <div class="d-flex justify-content-between align-items-center mb-3 pt-3 border-top">
                    <h5 class="h6 mb-0 fw-bold text-dark">
                        <i class="bi bi-list-check me-1 text-primary"></i>{{ __('procurement.line_items') }}
                    </h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddLineItem">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('procurement.add_line_item') }}
                    </button>
                </div>

                @error('items')
                    <div class="alert alert-danger py-2 small">{{ $message }}</div>
                @enderror

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 45%;">{{ __('procurement.select_expense_account') }} <span class="text-danger">*</span></th>
                                <th style="width: 25%;">{{ __('procurement.allocated_amount') }} (৳) <span class="text-danger">*</span></th>
                                <th style="width: 25%;">{{ __('common.notes') }}</th>
                                <th style="width: 5%;" class="text-center">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            @php
                                $oldItems = old('items', [
                                    ['financial_account_id' => '', 'allocated_amount' => '', 'notes' => '']
                                ]);
                            @endphp
                            @foreach($oldItems as $idx => $item)
                                <tr class="item-row">
                                    <td>
                                        <select name="items[{{ $idx }}][financial_account_id]" class="form-select form-select-sm account-select" required>
                                            <option value="">-- {{ __('procurement.select_expense_account') }} --</option>
                                            @foreach($expenseAccounts as $acc)
                                                <option value="{{ $acc->id }}" {{ ($item['financial_account_id'] ?? '') == $acc->id ? 'selected' : '' }}>
                                                    {{ $acc->account_code }} - {{ $acc->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">৳</span>
                                            <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][allocated_amount]" class="form-control text-end amount-input" value="{{ $item['allocated_amount'] ?? '' }}" placeholder="0.00" required>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][notes]" class="form-control form-control-sm" value="{{ $item['notes'] ?? '' }}" placeholder="{{ __('common.notes') }}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row" title="{{ __('procurement.remove_line_item') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th class="text-end fw-bold">{{ __('procurement.total_allocated') }}:</th>
                                <th class="text-end fw-bold text-primary h5 mb-0" id="totalAllocationDisplay">৳0.00</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('procurement.budgets.index') }}" class="btn btn-outline-secondary btn-sm">
                        {{ __('common.cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm px-4" id="btnSubmitBudget">
                        <i class="bi bi-check-lg me-1"></i> {{ __('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2-basic').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        });
    }

    // Toggle event selector based on budget type
    $('#budgetTypeSelect').on('change', function() {
        if ($(this).val() === 'event') {
            $('#eventFieldWrapper').slideDown(150);
            $('#eventSelect').prop('required', true);
        } else {
            $('#eventFieldWrapper').slideUp(150);
            $('#eventSelect').prop('required', false).val('').trigger('change');
        }
    });

    var rowIdx = {{ count($oldItems) }};

    function recalculateTotal() {
        var total = 0;
        $('.amount-input').each(function() {
            var val = parseFloat($(this).val()) || 0;
            total += val;
        });
        $('#totalAllocationDisplay').text('৳' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    // Add line item
    $('#btnAddLineItem').on('click', function() {
        var optionsHtml = '<option value="">-- {{ __("procurement.select_expense_account") }} --</option>';
        @foreach($expenseAccounts as $acc)
            optionsHtml += '<option value="{{ $acc->id }}">{{ $acc->account_code }} - {{ addslashes($acc->account_name) }}</option>';
        @endforeach

        var newRow = `
            <tr class="item-row">
                <td>
                    <select name="items[${rowIdx}][financial_account_id]" class="form-select form-select-sm account-select" required>
                        ${optionsHtml}
                    </select>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">৳</span>
                        <input type="number" step="0.01" min="0.01" name="items[${rowIdx}][allocated_amount]" class="form-control text-end amount-input" placeholder="0.00" required>
                    </div>
                </td>
                <td>
                    <input type="text" name="items[${rowIdx}][notes]" class="form-control form-control-sm" placeholder="{{ __('common.notes') }}">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row" title="{{ __('procurement.remove_line_item') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#itemsTableBody').append(newRow);
        rowIdx++;
    });

    // Remove line item
    $(document).on('click', '.btn-remove-row', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            recalculateTotal();
        } else {
            if (window.TempleUI && window.TempleUI.toast) {
                TempleUI.toast.warning('{{ __("procurement.at_least_one_item_required") }}');
            }
        }
    });

    $(document).on('input', '.amount-input', recalculateTotal);
    recalculateTotal();
});
</script>
@endpush
