@extends('layouts.app')

@section('title', __('finance.new_fund_transfer'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.new_fund_transfer') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.transfer_note') }}</p>
        </div>
        <div>
            <a href="{{ route('finance.transfers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Transfer Form Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('finance.transfers.store') }}" method="POST" id="transferForm">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">{{ __('finance.from_account') }}</label>
                        <select name="from_account_id" id="fromAccountSelect" class="form-select select2-basic @error('from_account_id') is-invalid @enderror" required>
                            <option value="">-- {{ __('common.select') }} --</option>
                            @foreach($assetAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('from_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name_en }} (৳{{ number_format($acc->calculateBalance(), 2) }})
                                </option>
                            @endforeach
                        </select>
                        @error('from_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">{{ __('finance.to_account') }}</label>
                        <select name="to_account_id" id="toAccountSelect" class="form-select select2-basic @error('to_account_id') is-invalid @enderror" required>
                            <option value="">-- {{ __('common.select') }} --</option>
                            @foreach($assetAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('to_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name_en }} (৳{{ number_format($acc->calculateBalance(), 2) }})
                                </option>
                            @endforeach
                        </select>
                        @error('to_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">{{ __('finance.transfer_amount') }} (৳)</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="0.00" required>
                        </div>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('finance.fee_amount') }} (৳)</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0" name="fee_amount" class="form-control @error('fee_amount') is-invalid @enderror" value="{{ old('fee_amount', '0.00') }}" placeholder="0.00">
                        </div>
                        <div class="text-muted small">{{ __('finance.fee_deduction_note') }}</div>
                        @error('fee_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">{{ __('finance.transfer_date') }}</label>
                        <input type="date" name="transfer_date" class="form-control @error('transfer_date') is-invalid @enderror" value="{{ old('transfer_date', date('Y-m-d')) }}" required>
                        @error('transfer_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('finance.reference_number') }}</label>
                        <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number') }}" placeholder="e.g. Bank Deposit Slip / Transaction ID" maxlength="50">
                        @error('reference_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('common.notes') }}</label>
                        <input type="text" name="notes" class="form-control @error('notes') is-invalid @enderror" value="{{ old('notes') }}" placeholder="e.g. Weekly cash deposit from temple donation hundi to bank account" maxlength="1000">
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('finance.transfers.index') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a>
                    <button type="submit" class="btn btn-primary" id="btnSubmitTransfer">
                        <i class="bi bi-arrow-left-right me-1"></i> {{ __('finance.new_fund_transfer') }}
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
