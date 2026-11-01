@extends('layouts.app')

@section('title', __('procurement.edit_payment'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-pencil-square text-primary me-2"></i>{{ __('procurement.edit_payment') }}: {{ $vendorPayment->payment_number }}
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('procurement.payments.index') }}">{{ __('procurement.vendor_payments') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('procurement.payments.show', $vendorPayment) }}">{{ $vendorPayment->payment_number }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('procurement.edit_payment') }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('procurement.payments.show', $vendorPayment) }}" class="btn btn-outline-secondary btn-sm">
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

    <form action="{{ route('procurement.payments.update', $vendorPayment) }}" method="POST" enctype="multipart/form-data" id="paymentForm">
        @csrf
        @method('PUT')

        <!-- Vendor & Disbursement Details Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-info-circle me-1 text-primary"></i> {{ __('procurement.payment_information') }}
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
                            @foreach($vendors as $ven)
                                <option value="{{ $ven->id }}" {{ (old('vendor_id', $vendorPayment->vendor_id) == $ven->id) ? 'selected' : '' }}>
                                    {{ $ven->name }}{{ $ven->phone ? ' ('.$ven->phone.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('vendor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Payment Date -->
                    <div class="col-md-3">
                        <label for="payment_date" class="form-label small fw-bold">
                            {{ __('procurement.payment_date') }} <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="payment_date" id="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                               value="{{ old('payment_date', $vendorPayment->payment_date?->format('Y-m-d')) }}" required>
                        @error('payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Payment Method -->
                    <div class="col-md-3">
                        <label for="payment_method" class="form-label small fw-bold">
                            {{ __('procurement.payment_method') }} <span class="text-danger">*</span>
                        </label>
                        <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                            @foreach($paymentMethods as $mKey => $mLabel)
                                <option value="{{ $mKey }}" {{ old('payment_method', $vendorPayment->payment_method) === $mKey ? 'selected' : '' }}>
                                    {{ $mLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Disbursed Asset Account (Cash/Bank) -->
                    <div class="col-md-6">
                        <label for="financial_account_id" class="form-label small fw-bold">
                            {{ __('procurement.payment_account') }} <span class="text-danger">*</span>
                        </label>
                        <select name="financial_account_id" id="financial_account_id" class="form-select @error('financial_account_id') is-invalid @enderror" required>
                            @foreach($assetAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('financial_account_id', $vendorPayment->financial_account_id) == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->account_code }} - {{ app()->getLocale() === 'bn' ? ($acc->account_name_bn ?: $acc->account_name_en) : $acc->account_name_en }}
                                </option>
                            @endforeach
                        </select>
                        @error('financial_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Payment Amount -->
                    <div class="col-md-3">
                        <label for="amount" class="form-label small fw-bold">
                            {{ __('procurement.payment_amount') }} (৳) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                               class="form-control font-monospace fw-bold text-success @error('amount') is-invalid @enderror"
                               value="{{ old('amount', (float)$vendorPayment->amount) }}" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Reference Number -->
                    <div class="col-md-3">
                        <label for="reference_number" class="form-label small fw-bold">
                            {{ __('procurement.reference_number') }}
                        </label>
                        <input type="text" name="reference_number" id="reference_number"
                               class="form-control @error('reference_number') is-invalid @enderror"
                               value="{{ old('reference_number', $vendorPayment->reference_number) }}"
                               placeholder="{{ __('procurement.reference_number_placeholder') }}">
                        @error('reference_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Document Attachment -->
                    <div class="col-md-6">
                        <label for="document" class="form-label small fw-bold">
                            {{ __('procurement.document_evidence') }}
                        </label>
                        <input type="file" name="document" id="document" class="form-control @error('document') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @if($vendorPayment->document_path)
                            <div class="mt-1 small">
                                <a href="{{ asset('storage/' . $vendorPayment->document_path) }}" target="_blank" class="text-primary text-decoration-none">
                                    <i class="bi bi-file-earmark-check me-1"></i> {{ __('procurement.download_attachment') }}
                                </a>
                            </div>
                        @endif
                        <div class="form-text small">{{ __('procurement.document_evidence_help') }}</div>
                        @error('document')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="col-md-6">
                        <label for="notes" class="form-label small fw-bold">
                            {{ __('common.notes') }}
                        </label>
                        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="{{ __('common.optional') }}">{{ old('notes', $vendorPayment->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Bill Payment Allocations Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-list-check me-1 text-primary"></i> {{ __('procurement.payment_allocations') }}
                </h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearAllocations">
                        <i class="bi bi-eraser me-1"></i> {{ __('procurement.clear_allocations') }}
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table class="table table-hover align-middle mb-0" id="allocationsTable">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('procurement.bill_number') }}</th>
                                <th>{{ __('procurement.invoice_reference') }}</th>
                                <th>{{ __('procurement.bill_date') }}</th>
                                <th>{{ __('procurement.due_date') }}</th>
                                <th class="text-end">{{ __('procurement.total_price') }}</th>
                                <th class="text-end">{{ __('procurement.total_paid') }}</th>
                                <th class="text-end text-danger">{{ __('procurement.total_outstanding') }}</th>
                                <th class="text-end" style="width: 200px;">{{ __('procurement.allocated_amount') }} (৳) <span class="text-danger">*</span></th>
                                <th class="text-center" style="width: 140px;">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="allocationsBody">
                            @php
                                $existingMap = $vendorPayment->allocations->keyBy('vendor_bill_id');
                            @endphp
                            @forelse($payableBills as $idx => $b)
                                @php
                                    $prevAlloc = $existingMap->get($b->id);
                                    $allocatedVal = $prevAlloc ? (float)$prevAlloc->allocated_amount : 0.00;
                                    $availOutstanding = (float)$b->total_amount - (float)$b->paid_amount + $allocatedVal;
                                @endphp
                                <tr data-bill-id="{{ $b->id }}" data-outstanding="{{ $availOutstanding }}">
                                    <td class="fw-bold font-monospace text-primary">
                                        {{ $b->bill_number }}
                                        <input type="hidden" name="allocations[{{ $idx }}][vendor_bill_id]" value="{{ $b->id }}">
                                    </td>
                                    <td class="font-monospace fw-semibold">{{ $b->vendor_invoice_number }}</td>
                                    <td>{{ $b->bill_date?->format('d M Y') }}</td>
                                    <td>{{ $b->due_date?->format('d M Y') ?? '-' }}</td>
                                    <td class="text-end font-monospace">৳{{ number_format((float)$b->total_amount, 2) }}</td>
                                    <td class="text-end font-monospace text-muted">৳{{ number_format((float)$b->paid_amount, 2) }}</td>
                                    <td class="text-end font-monospace fw-bold text-danger">৳{{ number_format($availOutstanding, 2) }}</td>
                                    <td class="text-end">
                                        <input type="number" step="0.01" min="0" max="{{ $availOutstanding }}"
                                               name="allocations[{{ $idx }}][allocated_amount]"
                                               class="form-control form-control-sm text-end font-monospace fw-bold allocated-input"
                                               value="{{ $allocatedVal > 0 ? number_format($allocatedVal, 2, '.', '') : '' }}" placeholder="0.00">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-autofill" title="{{ __('procurement.auto_allocate') }}">
                                            <i class="bi bi-magic me-1"></i> {{ __('procurement.auto_allocate') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="bi bi-info-circle me-1"></i> {{ __('procurement.no_payable_bills') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Allocation Summary Footer -->
                <div class="bg-light p-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span id="allocationStatusBadge" class="badge bg-secondary fs-6">
                            {{ __('procurement.unallocated_amount') }}: ৳0.00
                        </span>
                    </div>
                    <div class="d-flex gap-4">
                        <div>
                            <span class="text-muted small fw-bold d-block">{{ __('procurement.payment_amount') }}:</span>
                            <span class="fs-5 fw-bold text-success font-monospace" id="displayPaymentAmount">৳{{ number_format((float)$vendorPayment->amount, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-muted small fw-bold d-block">{{ __('procurement.total_allocated') }}:</span>
                            <span class="fs-5 fw-bold text-primary font-monospace" id="displayTotalAllocated">৳{{ number_format((float)$vendorPayment->amount, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Submission Actions -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex justify-content-end gap-2">
                <a href="{{ route('procurement.payments.show', $vendorPayment) }}" class="btn btn-outline-secondary">
                    {{ __('common.cancel') }}
                </a>
                <button type="submit" class="btn btn-primary" id="btnSubmitPayment">
                    <i class="bi bi-check2-circle me-1"></i> {{ __('common.update') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#vendor_id').select2({
        theme: 'bootstrap-5',
        placeholder: "{{ __('procurement.vendor_required') }}",
        width: '100%'
    });

    function formatMoney(amount) {
        return '৳' + parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function calculateAllocations() {
        var paymentAmount = parseFloat($('#amount').val()) || 0;
        var totalAllocated = 0;

        $('.allocated-input').each(function() {
            var val = parseFloat($(this).val()) || 0;
            totalAllocated += val;
        });

        totalAllocated = Math.round(totalAllocated * 100) / 100;
        paymentAmount = Math.round(paymentAmount * 100) / 100;

        $('#displayPaymentAmount').text(formatMoney(paymentAmount));
        $('#displayTotalAllocated').text(formatMoney(totalAllocated));

        var diff = Math.round((paymentAmount - totalAllocated) * 100) / 100;
        var statusBadge = $('#allocationStatusBadge');

        if (paymentAmount > 0 && Math.abs(diff) < 0.001) {
            statusBadge.removeClass('bg-secondary bg-warning bg-danger').addClass('bg-success');
            statusBadge.html('<i class="bi bi-check-circle me-1"></i> Matched (৳0.00)');
            $('#btnSubmitPayment').prop('disabled', false);
        } else if (diff > 0) {
            statusBadge.removeClass('bg-secondary bg-success bg-danger').addClass('bg-warning text-dark');
            statusBadge.html('<i class="bi bi-exclamation-circle me-1"></i> Unallocated: ' + formatMoney(diff));
        } else {
            statusBadge.removeClass('bg-secondary bg-success bg-warning text-dark').addClass('bg-danger');
            statusBadge.html('<i class="bi bi-x-circle me-1"></i> Overallocated: ' + formatMoney(Math.abs(diff)));
        }
    }

    // Auto-fill button click handler
    $(document).on('click', '.btn-autofill', function() {
        var tr = $(this).closest('tr');
        var outstanding = parseFloat(tr.data('outstanding')) || 0;
        var paymentAmount = parseFloat($('#amount').val()) || 0;

        if (paymentAmount <= 0) {
            $('#amount').val(outstanding.toFixed(2));
            tr.find('.allocated-input').val(outstanding.toFixed(2));
        } else {
            var currentlyAllocatedOther = 0;
            $('.allocated-input').not(tr.find('.allocated-input')).each(function() {
                currentlyAllocatedOther += parseFloat($(this).val()) || 0;
            });
            var remainingUnallocated = Math.max(0, paymentAmount - currentlyAllocatedOther);
            var allocAmount = Math.min(outstanding, remainingUnallocated);
            tr.find('.allocated-input').val(allocAmount > 0 ? allocAmount.toFixed(2) : outstanding.toFixed(2));
        }

        calculateAllocations();
    });

    $(document).on('input', '#amount, .allocated-input', function() {
        calculateAllocations();
    });

    $('#btnClearAllocations').on('click', function() {
        $('.allocated-input').val('');
        calculateAllocations();
    });

    $('#paymentForm').on('submit', function(e) {
        var paymentAmount = parseFloat($('#amount').val()) || 0;
        var totalAllocated = 0;
        var hasValidAllocation = false;

        $('.allocated-input').each(function() {
            var val = parseFloat($(this).val()) || 0;
            if (val > 0) {
                totalAllocated += val;
                hasValidAllocation = true;
            }
        });

        if (paymentAmount <= 0) {
            e.preventDefault();
            TempleUI.showToast("{{ __('procurement.payment_amount_must_be_positive') }}", 'warning');
            return false;
        }

        if (!hasValidAllocation) {
            e.preventDefault();
            TempleUI.showToast("{{ __('procurement.at_least_one_bill_allocation_required') }}", 'warning');
            return false;
        }

        totalAllocated = Math.round(totalAllocated * 100) / 100;
        paymentAmount = Math.round(paymentAmount * 100) / 100;

        if (Math.abs(totalAllocated - paymentAmount) > 0.01) {
            e.preventDefault();
            TempleUI.showToast("{{ __('procurement.allocations_sum_must_equal_payment_amount', ['allocated' => 'TOTAL_ALLOC', 'total' => 'TOTAL_PAY']) }}".replace('TOTAL_ALLOC', totalAllocated.toFixed(2)).replace('TOTAL_PAY', paymentAmount.toFixed(2)), 'danger');
            return false;
        }

        TempleUI.btnLoader('#btnSubmitPayment', true);
    });

    calculateAllocations();
});
</script>
@endpush
