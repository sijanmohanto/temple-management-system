@extends('layouts.app')

@section('title', __('portal.receivers.edit_account'))

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('portal.receivers.index') }}">{{ __('portal.receiver_accounts') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('portal.receivers.edit_account') }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ __('portal.receivers.edit_account') }}</h1>
            <p class="text-muted small mb-0">{{ $receiver->account_name }} &bull; {{ $receiver->account_number }}</p>
        </div>
        <div>
            <a href="{{ route('portal.receivers.show', $receiver) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('portal.actions.back_to_list') }}
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">{{ __('portal.receivers.edit_account') }}</h5>
                    <div>
                        <span class="badge {{ $receiver->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $receiver->is_active ? __('common.active') : __('common.inactive') }}
                        </span>
                        <span class="badge {{ $receiver->is_verified ? 'bg-info text-dark' : 'bg-warning text-dark' }} ms-1">
                            {{ $receiver->is_verified ? __('portal.receivers.verified') : __('portal.receivers.unverified') }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill fs-4 me-3 text-primary"></i>
                        <div class="small">
                            {{ __('portal.receivers.receiver_notice') }}
                        </div>
                    </div>

                    <form action="{{ route('portal.receivers.update', $receiver) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Associated Event / Collection Purpose --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="event_id" class="form-label fw-bold">
                                    {{ __('portal.receivers.associated_event') }}
                                </label>
                                <select name="event_id" id="event_id" class="form-select select2-enable @error('event_id') is-invalid @enderror">
                                    <option value="">-- {{ __('portal.receivers.all_events_general') }} --</option>
                                    @foreach($events as $ev)
                                        <option value="{{ $ev->id }}" {{ old('event_id', $receiver->event_id) == $ev->id ? 'selected' : '' }}>
                                            {{ $ev->name }} ({{ $ev->category?->name ?: __('common.general') }}) – {{ $ev->event_year }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted">Select specific festival/event or leave as general temple collection.</div>
                                @error('event_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            {{-- Payment Method --}}
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label fw-bold">
                                    {{ __('portal.receivers.payment_method') }} <span class="text-danger">*</span>
                                </label>
                                <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                    <option value="bkash" {{ old('payment_method', $receiver->payment_method) === 'bkash' ? 'selected' : '' }}>{{ __('portal.payment_methods.bkash') }}</option>
                                    <option value="nagad" {{ old('payment_method', $receiver->payment_method) === 'nagad' ? 'selected' : '' }}>{{ __('portal.payment_methods.nagad') }}</option>
                                    <option value="rocket" {{ old('payment_method', $receiver->payment_method) === 'rocket' ? 'selected' : '' }}>{{ __('portal.payment_methods.rocket') }}</option>
                                    <option value="bank_transfer" {{ old('payment_method', $receiver->payment_method) === 'bank_transfer' ? 'selected' : '' }}>{{ __('portal.payment_methods.bank_transfer') }}</option>
                                    <option value="other" {{ old('payment_method', $receiver->payment_method) === 'other' ? 'selected' : '' }}>{{ __('portal.payment_methods.other') }}</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Account Type --}}
                            <div class="col-md-6">
                                <label for="account_type" class="form-label fw-bold">
                                    {{ __('portal.receivers.account_type') }} <span class="text-danger">*</span>
                                </label>
                                <select name="account_type" id="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                                    <option value="merchant" {{ old('account_type', $receiver->account_type) === 'merchant' ? 'selected' : '' }}>{{ __('portal.account_types.merchant') }}</option>
                                    <option value="personal" {{ old('account_type', $receiver->account_type) === 'personal' ? 'selected' : '' }}>{{ __('portal.account_types.personal') }}</option>
                                    <option value="current" {{ old('account_type', $receiver->account_type) === 'current' ? 'selected' : '' }}>{{ __('portal.account_types.current') }}</option>
                                    <option value="savings" {{ old('account_type', $receiver->account_type) === 'savings' ? 'selected' : '' }}>{{ __('portal.account_types.savings') }}</option>
                                    <option value="agent" {{ old('account_type', $receiver->account_type) === 'agent' ? 'selected' : '' }}>{{ __('portal.account_types.agent') }}</option>
                                </select>
                                @error('account_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Account Name & Number --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="account_name" class="form-label fw-bold">
                                    {{ __('portal.receivers.account_name') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="account_name" id="account_name"
                                       class="form-control @error('account_name') is-invalid @enderror"
                                       value="{{ old('account_name', $receiver->account_name) }}" required maxlength="255">
                                @error('account_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="account_number" class="form-label fw-bold">
                                    {{ __('portal.receivers.account_number') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="account_number" id="account_number"
                                       class="form-control @error('account_number') is-invalid @enderror"
                                       value="{{ old('account_number', $receiver->account_number) }}" required maxlength="100">
                                @error('account_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Bank Details Section --}}
                        <div id="bankDetailsSection" class="p-3 mb-3 bg-light rounded border {{ old('payment_method', $receiver->payment_method) === 'bank_transfer' ? '' : 'd-none' }}">
                            <h6 class="fw-bold mb-3 text-secondary">
                                <i class="bi bi-bank me-1"></i> {{ __('portal.payment_methods.bank_transfer') }}
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="bank_name" class="form-label fw-bold">
                                        {{ __('portal.receivers.bank_name') }} <span class="text-danger" id="bankNameRequiredStar">*</span>
                                    </label>
                                    <input type="text" name="bank_name" id="bank_name"
                                           class="form-control @error('bank_name') is-invalid @enderror"
                                           value="{{ old('bank_name', $receiver->bank_name) }}" maxlength="255">
                                    @error('bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="branch_name" class="form-label fw-bold">
                                        {{ __('portal.receivers.branch_name') }}
                                    </label>
                                    <input type="text" name="branch_name" id="branch_name"
                                           class="form-control @error('branch_name') is-invalid @enderror"
                                           value="{{ old('branch_name', $receiver->branch_name) }}" maxlength="255">
                                    @error('branch_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="routing_number" class="form-label fw-bold">
                                        {{ __('portal.receivers.routing_number') }}
                                    </label>
                                    <input type="text" name="routing_number" id="routing_number"
                                           class="form-control @error('routing_number') is-invalid @enderror"
                                           value="{{ old('routing_number', $receiver->routing_number) }}" maxlength="100">
                                    @error('routing_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="swift_code" class="form-label fw-bold">
                                        {{ __('portal.receivers.swift_code') }}
                                    </label>
                                    <input type="text" name="swift_code" id="swift_code"
                                           class="form-control @error('swift_code') is-invalid @enderror"
                                           value="{{ old('swift_code', $receiver->swift_code) }}" maxlength="50">
                                    @error('swift_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Current QR Code & Upload --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="qr_code" class="form-label fw-bold">
                                    {{ __('portal.receivers.qr_code') }}
                                </label>
                                @if($receiver->qr_code_image_path)
                                    <div class="d-flex align-items-center gap-3 mb-2 p-2 bg-light rounded border">
                                        <img src="{{ $receiver->qr_code_url }}" alt="QR" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remove_qr_code" id="remove_qr_code" value="1">
                                            <label class="form-check-label text-danger small" for="remove_qr_code">
                                                {{ __('portal.receivers.remove_qr') }}
                                            </label>
                                        </div>
                                    </div>
                                @endif
                                <input type="file" name="qr_code" id="qr_code"
                                       class="form-control @error('qr_code') is-invalid @enderror"
                                       accept=".jpeg,.jpg,.png,.webp">
                                <div class="form-text text-muted">{{ __('portal.receivers.qr_code_help') }}</div>
                                @error('qr_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="financial_account_id" class="form-label fw-bold">
                                    {{ __('portal.receivers.financial_account') }}
                                </label>
                                <select name="financial_account_id" id="financial_account_id" class="form-select select2-enable @error('financial_account_id') is-invalid @enderror">
                                    <option value="">-- {{ __('common.none') }} / Unlinked --</option>
                                    @foreach($financialAccounts as $finAccount)
                                        <option value="{{ $finAccount->id }}" {{ old('financial_account_id', $receiver->financial_account_id) == $finAccount->id ? 'selected' : '' }}>
                                            {{ $finAccount->account_code }} — {{ $finAccount->name }} ({{ strtoupper($finAccount->account_type) }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted">{{ __('portal.receivers.financial_account_help') }}</div>
                                @error('financial_account_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Instructions --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="instructions_en" class="form-label fw-bold">
                                    {{ __('portal.receivers.instructions_en') }}
                                </label>
                                <textarea name="instructions_en" id="instructions_en" rows="3"
                                          class="form-control @error('instructions_en') is-invalid @enderror"
                                          maxlength="2000">{{ old('instructions_en', $receiver->instructions_en) }}</textarea>
                                @error('instructions_en')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="instructions_bn" class="form-label fw-bold font-bengali">
                                    {{ __('portal.receivers.instructions_bn') }}
                                </label>
                                <textarea name="instructions_bn" id="instructions_bn" rows="3"
                                          class="form-control font-bengali @error('instructions_bn') is-invalid @enderror"
                                          maxlength="2000">{{ old('instructions_bn', $receiver->instructions_bn) }}</textarea>
                                @error('instructions_bn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Display Order & Switches --}}
                        <div class="row g-3 mb-4 align-items-center">
                            <div class="col-md-4">
                                <label for="display_order" class="form-label fw-bold">
                                    {{ __('portal.receivers.display_order') }}
                                </label>
                                <input type="number" name="display_order" id="display_order"
                                       class="form-control @error('display_order') is-invalid @enderror"
                                       value="{{ old('display_order', $receiver->display_order) }}" min="0" max="9999">
                                @error('display_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 pt-3">
                                <div class="form-check form-switch p-3 bg-light rounded border">
                                    <input class="form-check-input ms-0 me-3" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $receiver->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="is_active">
                                        {{ __('common.active') }}
                                    </label>
                                </div>
                            </div>
                            @if(auth()->user()->can('verify_receiver_accounts'))
                                <div class="col-md-4 pt-3">
                                    <div class="form-check form-switch p-3 bg-light rounded border">
                                        <input class="form-check-input ms-0 me-3" type="checkbox" name="is_verified" id="is_verified" value="1" {{ old('is_verified', $receiver->is_verified) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="is_verified">
                                            {{ __('portal.receivers.verified') }}
                                        </label>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Form Actions --}}
                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <a href="{{ route('portal.receivers.show', $receiver) }}" class="btn btn-outline-secondary">
                                {{ __('portal.actions.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> {{ __('portal.actions.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    function toggleBankSection() {
        const method = $('#payment_method').val();
        if (method === 'bank_transfer') {
            $('#bankDetailsSection').removeClass('d-none');
            $('#bank_name').prop('required', true);
            $('#bankNameRequiredStar').removeClass('d-none');
        } else {
            $('#bankDetailsSection').addClass('d-none');
            $('#bank_name').prop('required', false);
            $('#bankNameRequiredStar').addClass('d-none');
        }
    }

    $('#payment_method').on('change', toggleBankSection);
    toggleBankSection();
});
</script>
@endpush
