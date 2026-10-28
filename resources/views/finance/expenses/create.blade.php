@extends('layouts.app')

@section('title', __('finance.new_expense_voucher'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.new_expense_voucher') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.expense_requisition_subtitle') }}</p>
        </div>
        <div>
            <a href="{{ route('finance.expenses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('finance.expenses.store') }}" method="POST" id="expenseForm">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">{{ __('common.title') }} (English)</label>
                        <input type="text" name="title_en" class="form-control @error('title_en') is-invalid @enderror" value="{{ old('title_en') }}" placeholder="e.g. Fresh Flowers & Garlands for Puja" required maxlength="200">
                        @error('title_en')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('common.title') }} (বাংলা)</label>
                        <input type="text" name="title_bn" class="form-control @error('title_bn') is-invalid @enderror" value="{{ old('title_bn') }}" placeholder="যেমন: পূজার তাজা ফুল ও মালা ক্রয়" maxlength="200">
                        @error('title_bn')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">{{ __('finance.expense_category') }}</label>
                        <select name="expense_account_id" class="form-select select2-basic @error('expense_account_id') is-invalid @enderror" required>
                            <option value="">-- {{ __('common.select') }} --</option>
                            @foreach($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('expense_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name_en }} ({{ $acc->name_bn ?: $acc->name_en }})
                                </option>
                            @endforeach
                        </select>
                        @error('expense_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">{{ __('finance.paid_from') }}</label>
                        <select name="payment_account_id" class="form-select select2-basic @error('payment_account_id') is-invalid @enderror" required>
                            <option value="">-- {{ __('common.select') }} --</option>
                            @foreach($assetAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('payment_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name_en }} ({{ $acc->name_bn ?: $acc->name_en }})
                                </option>
                            @endforeach
                        </select>
                        @error('payment_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">{{ __('common.amount') }} (৳)</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="0.00" required>
                        </div>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">{{ __('finance.entry_date') }}</label>
                        <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                        @error('expense_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">{{ __('donations.payment_method') }}</label>
                        <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                            <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="bkash" {{ old('payment_method') === 'bkash' ? 'selected' : '' }}>bKash</option>
                            <option value="nagad" {{ old('payment_method') === 'nagad' ? 'selected' : '' }}>Nagad</option>
                            <option value="rocket" {{ old('payment_method') === 'rocket' ? 'selected' : '' }}>Rocket</option>
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('finance.payee_vendor') }}</label>
                        <input type="text" name="vendor_name" class="form-control @error('vendor_name') is-invalid @enderror" value="{{ old('vendor_name') }}" placeholder="e.g. Ma Kali Flower Store" maxlength="150">
                        @error('vendor_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('finance.vendor_phone') }}</label>
                        <input type="text" name="vendor_phone" class="form-control @error('vendor_phone') is-invalid @enderror" value="{{ old('vendor_phone') }}" placeholder="01XXXXXXXXX" maxlength="30">
                        @error('vendor_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('finance.invoice_number') }}</label>
                        <input type="text" name="invoice_number" class="form-control @error('invoice_number') is-invalid @enderror" value="{{ old('invoice_number') }}" placeholder="e.g. INV-2026-88" maxlength="50">
                        @error('invoice_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('events.event') }} ({{ __('common.optional') }})</label>
                        <select name="event_id" class="form-select select2-basic @error('event_id') is-invalid @enderror">
                            <option value="">-- {{ __('finance.general_temple_no_event') }} --</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ $event->title_en }} ({{ $event->category?->name_en }})
                                </option>
                            @endforeach
                        </select>
                        @error('event_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('common.notes') }}</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Additional details or justification...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('finance.expenses.index') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a>
                    <button type="submit" class="btn btn-primary" id="btnSubmitExpense">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('common.submit') }}
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
    $('.select2-basic').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush
