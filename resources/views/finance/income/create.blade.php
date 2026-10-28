@extends('layouts.app')

@section('title', __('finance.new_misc_income'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.new_misc_income') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.misc_income_subtitle') }}</p>
        </div>
        <div>
            <a href="{{ route('finance.income.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Income Form Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('finance.income.store') }}" method="POST" id="incomeForm">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">{{ __('common.title') }} (English)</label>
                        <input type="text" name="title_en" class="form-control @error('title_en') is-invalid @enderror" value="{{ old('title_en') }}" placeholder="e.g. Sale of Old Temple Scrap / Iron" required maxlength="200">
                        @error('title_en')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('common.title') }} (বাংলা)</label>
                        <input type="text" name="title_bn" class="form-control @error('title_bn') is-invalid @enderror" value="{{ old('title_bn') }}" placeholder="যেমন: মন্দিরের পুরাতন সামগ্রী বিক্রয় বাবদ আয়" maxlength="200">
                        @error('title_bn')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">{{ __('finance.income_category') }}</label>
                        <select name="income_account_id" class="form-select select2-basic @error('income_account_id') is-invalid @enderror" required>
                            <option value="">-- {{ __('common.select') }} --</option>
                            @foreach($incomeAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('income_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name_en }} ({{ $acc->name_bn ?: $acc->name_en }})
                                </option>
                            @endforeach
                        </select>
                        @error('income_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">{{ __('finance.deposit_account') }}</label>
                        <select name="deposit_account_id" class="form-select select2-basic @error('deposit_account_id') is-invalid @enderror" required>
                            <option value="">-- {{ __('common.select') }} --</option>
                            @foreach($assetAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('deposit_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name_en }} ({{ $acc->name_bn ?: $acc->name_en }})
                                </option>
                            @endforeach
                        </select>
                        @error('deposit_account_id')
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
                        <label class="form-label required">{{ __('finance.income_date') }}</label>
                        <input type="date" name="income_date" class="form-control @error('income_date') is-invalid @enderror" value="{{ old('income_date', date('Y-m-d')) }}" required>
                        @error('income_date')
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
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('finance.payer_name') }}</label>
                        <input type="text" name="payer_name" class="form-control @error('payer_name') is-invalid @enderror" value="{{ old('payer_name') }}" placeholder="e.g. Commercial Vendor or Devotee" maxlength="150">
                        @error('payer_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('finance.reference_number') }}</label>
                        <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number') }}" placeholder="e.g. Money Receipt / Challan No." maxlength="50">
                        @error('reference_number')
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
                                    {{ $event->title_en }}
                                </option>
                            @endforeach
                        </select>
                        @error('event_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('common.notes') }}</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Additional remarks or explanation...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('finance.income.index') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a>
                    <button type="submit" class="btn btn-primary" id="btnSubmitIncome">
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
