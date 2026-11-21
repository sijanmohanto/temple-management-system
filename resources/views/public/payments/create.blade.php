@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', __('portal.public.submit_payment') . ' — ' . $event->name . ' — ' . __('common.app_name'))

@section('content')
<div class="py-2">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 small bg-white p-2 px-3 rounded-3 shadow-sm border">
            <li class="breadcrumb-item">
                <a href="{{ auth()->check() ? route('devotee.dashboard') : route('home') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> {{ auth()->check() ? __('devotee.my_dashboard') : __('portal.public.home') }}
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('public.events.index') }}" class="text-decoration-none text-muted">
                    {{ __('portal.public.events') }}
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('public.events.show', $event->slug) }}" class="text-decoration-none text-muted">
                    {{ $event->name }}
                </a>
            </li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                {{ __('portal.public.submit_payment') }}
            </li>
        </ol>
    </nav>

    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background: linear-gradient(135deg, #560e15 0%, #75141c 50%, #b45309 100%);">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-warning text-dark px-3 py-1 font-monospace">{{ $event->event_code }}</span>
                        <span class="badge bg-white bg-opacity-25 text-white px-3 py-1">{{ $event->event_year }}</span>
                    </div>
                    <h1 class="h2 fw-bold mb-1">{{ __('portal.public.submit_payment') }}</h1>
                    <p class="text-white-50 mb-0 fs-6">
                        {{ $event->name }} — {{ __('portal.public.submit_payment_subtitle') }}
                    </p>
                </div>
                <div>
                    <a href="{{ auth()->check() ? route('devotee.dashboard') : route('public.events.show', $event->slug) }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> {{ auth()->check() ? __('devotee.back_to_dashboard') : __('portal.public.back_to_events') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert / Validation Errors -->
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-4" role="alert">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                <h6 class="mb-0 fw-bold">{{ __('common.validation_error_title') ?? 'Please correct the following errors:' }}</h6>
            </div>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('public.events.pay.submit', $event->slug) }}" method="POST" enctype="multipart/form-data" id="publicPaymentForm">
        @csrf

        <div class="row g-4">
            <!-- Left Column: Form Inputs -->
            <div class="col-lg-7">
                <!-- Section 1: Registered Devotee Information (Read-only) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center">
                            <i class="bi bi-person-badge text-primary me-2"></i>
                            {{ __('portal.public.devotee_info_title') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">{{ __('portal.public.devotee_name') }}</label>
                                <div class="p-2 px-3 bg-light rounded-3 border fw-semibold text-dark">
                                    {{ app()->getLocale() === 'bn' && !empty($donor->name_bn) ? $donor->name_bn : $donor->name_en }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">{{ __('portal.public.devotee_code') }}</label>
                                <div class="p-2 px-3 bg-light rounded-3 border font-monospace fw-bold text-secondary">
                                    {{ $donor->person_code }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">{{ __('portal.public.devotee_phone') }}</label>
                                <div class="p-2 px-3 bg-light rounded-3 border text-dark">
                                    <i class="bi bi-telephone text-muted me-1"></i> {{ $donor->phone ?: '—' }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">{{ __('portal.public.devotee_email') }}</label>
                                <div class="p-2 px-3 bg-light rounded-3 border text-dark">
                                    <i class="bi bi-envelope text-muted me-1"></i> {{ $donor->email ?: '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Pledge Selection (if pledges exist) -->
                @if($pledges->isNotEmpty())
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center">
                                <i class="bi bi-bullseye text-warning me-2"></i>
                                {{ __('portal.public.pledge_info_title') }}
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small mb-3">{{ __('portal.public.pledge_select') }}:</p>

                            <div class="d-flex flex-column gap-2">
                                @foreach($pledges as $p)
                                    @php
                                        $due = max(0.0, round((float) $p->pledged_amount - (float) $p->verified_paid_amount, 2));
                                        $isChecked = old('pledge_id', $selectedPledgeId) == $p->id || ($loop->first && !old('pledge_id') && !$selectedPledgeId);
                                    @endphp
                                    <div class="form-check p-3 rounded-3 border {{ $isChecked ? 'border-primary bg-primary bg-opacity-10' : 'bg-light' }} pledge-radio-card" style="cursor: pointer;">
                                        <input class="form-check-input ms-0 me-3" type="radio" name="pledge_id" id="pledge_{{ $p->id }}" value="{{ $p->id }}" {{ $isChecked ? 'checked' : '' }} data-due="{{ $due }}">
                                        <label class="form-check-label w-100" for="pledge_{{ $p->id }}" style="cursor: pointer;">
                                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1">
                                                <div>
                                                    <span class="fw-bold font-monospace text-dark">{{ $p->pledge_code }}</span>
                                                    <span class="badge bg-secondary ms-1">{{ $p->pledge_year }}</span>
                                                </div>
                                                <div class="small">
                                                    <span class="text-muted">{{ __('portal.public.table_pledged') }}:</span> <strong class="text-dark">৳{{ number_format($p->pledged_amount, 2) }}</strong> |
                                                    <span class="text-muted">{{ __('portal.public.table_paid') }}:</span> <span class="text-success fw-bold">৳{{ number_format($p->verified_paid_amount, 2) }}</span> |
                                                    <span class="text-muted">{{ __('portal.public.remaining_due') }}:</span> <span class="text-danger fw-bold">৳{{ number_format($due, 2) }}</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach

                                <!-- Voluntary Donation Option -->
                                <div class="form-check p-3 rounded-3 border {{ old('pledge_id') === '' ? 'border-primary bg-primary bg-opacity-10' : 'bg-light' }} pledge-radio-card" style="cursor: pointer;">
                                    <input class="form-check-input ms-0 me-3" type="radio" name="pledge_id" id="pledge_none" value="" {{ old('pledge_id') === '' ? 'checked' : '' }} data-due="0">
                                    <label class="form-check-label w-100" for="pledge_none" style="cursor: pointer;">
                                        <span class="fw-bold text-dark">{{ __('portal.public.voluntary_donation') }}</span>
                                        <span class="text-muted small d-block">{{ __('portal.public.zero_pledge_notice') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Section 3: Payment Details -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center">
                            <i class="bi bi-cash-stack text-success me-2"></i>
                            {{ __('portal.public.submit_payment') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- Payment Method Selector -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-2">
                                {{ __('portal.public.select_payment_method') }} <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2">
                                @php
                                    $methods = [
                                        'bkash' => [
                                            'name' => 'bKash',
                                            'full_logo' => asset('assets/images/methods/bkash-full-logo.png'),
                                            'short_logo' => asset('assets/images/methods/bkash-short-logo.png')
                                        ],
                                        'nagad' => [
                                            'name' => 'Nagad',
                                            'full_logo' => asset('assets/images/methods/nagad-full-logo.png'),
                                            'short_logo' => asset('assets/images/methods/nagad-short-logo.png')
                                        ],
                                        'rocket' => [
                                            'name' => 'Rocket',
                                            'full_logo' => asset('assets/images/methods/rocket-full-logo.png'),
                                            'short_logo' => asset('assets/images/methods/rocket-short-logo.png')
                                        ],
                                        'bank_transfer' => [
                                            'name' => __('portal.payment_methods.bank_transfer'),
                                            'full_logo' => asset('assets/images/methods/bank.svg'),
                                            'short_logo' => asset('assets/images/methods/bank.svg')
                                        ],
                                    ];
                                    $selectedMethod = old('payment_method', 'bkash');
                                @endphp
                                @foreach($methods as $methodKey => $meta)
                                    <div class="col-6 col-sm-3">
                                        <input type="radio" class="btn-check payment-method-radio" name="payment_method" id="method_{{ $methodKey }}" value="{{ $methodKey }}" {{ $selectedMethod === $methodKey ? 'checked' : '' }} autocomplete="off">
                                        <label class="payment-method-card w-100" for="method_{{ $methodKey }}">
                                            <div class="method-icon-box">
                                                <img src="{{ $meta['full_logo'] }}" alt="{{ $meta['name'] }}" loading="lazy">
                                            </div>
                                            <span class="method-title">{{ $meta['name'] }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Receiver Account Dropdown -->
                        <div class="mb-4">
                            <label for="receiver_account_id" class="form-label fw-bold text-dark mb-1">
                                {{ __('portal.public.select_receiver_account') }} <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('receiver_account_id') is-invalid @enderror" id="receiver_account_id" name="receiver_account_id" required>
                                <option value="">-- {{ __('portal.public.select_receiver_account') }} --</option>
                                @foreach($receiverAccounts as $acc)
                                    <option value="{{ $acc->id }}"
                                            data-method="{{ $acc->payment_method }}"
                                            data-account-name="{{ $acc->account_name }}"
                                            data-account-number="{{ $acc->account_number }}"
                                            data-account-type="{{ $acc->type_name }}"
                                            data-bank-name="{{ $acc->bank_name }}"
                                            data-branch-name="{{ $acc->branch_name }}"
                                            data-routing="{{ $acc->routing_number }}"
                                            data-swift="{{ $acc->swift_code }}"
                                            data-instructions="{{ $acc->instructions }}"
                                            data-qr="{{ $acc->qr_code_url }}"
                                            {{ old('receiver_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->account_name }} ({{ $acc->account_number }}) [{{ $acc->type_name }}]
                                    </option>
                                @endforeach
                            </select>
                            @error('receiver_account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Amount & Date -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label fw-bold text-dark mb-1">
                                    {{ __('portal.public.amount') }} <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold">৳</span>
                                    <input type="number" step="0.01" min="1" max="10000000" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" placeholder="{{ __('portal.public.amount_placeholder') }}" required>
                                </div>
                                @error('amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="payment_date" class="form-label fw-bold text-dark mb-1">
                                    {{ __('portal.public.payment_date') }} <span class="text-danger">*</span>
                                </label>
                                <input type="date" max="{{ date('Y-m-d') }}" class="form-control @error('payment_date') is-invalid @enderror" id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Sender Mobile & Transaction Reference -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6" id="senderMobileContainer">
                                <label for="sender_mobile" class="form-label fw-bold text-dark mb-1">
                                    {{ __('portal.public.sender_mobile') }} <span class="text-danger" id="senderMobileRequiredStar">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                                    <input type="text" class="form-control @error('sender_mobile') is-invalid @enderror" id="sender_mobile" name="sender_mobile" value="{{ old('sender_mobile', $donor->phone) }}" placeholder="{{ __('portal.public.sender_mobile_placeholder') }}">
                                </div>
                                <div class="form-text small text-muted">{{ __('portal.public.sender_mobile_help') }}</div>
                                @error('sender_mobile')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6" id="trxRefContainer">
                                <label for="transaction_reference" class="form-label fw-bold text-dark mb-1">
                                    {{ __('portal.public.transaction_reference') }} <span class="text-danger" id="trxRefRequiredStar">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-hash"></i></span>
                                    <input type="text" class="form-control font-monospace @error('transaction_reference') is-invalid @enderror" id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}" placeholder="{{ __('portal.public.transaction_reference_placeholder') }}">
                                </div>
                                <div class="form-text small text-muted">{{ __('portal.public.transaction_reference_help') }}</div>
                                @error('transaction_reference')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Payment Time (Optional) & Payment Proof (File) -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="payment_time" class="form-label fw-semibold text-dark mb-1">
                                    {{ __('portal.public.payment_time') }}
                                </label>
                                <input type="time" class="form-control" id="payment_time" name="payment_time" value="{{ old('payment_time') }}">
                            </div>

                            <div class="col-md-6">
                                <label for="payment_proof" class="form-label fw-semibold text-dark mb-1">
                                    {{ __('portal.public.payment_proof') }}
                                </label>
                                <input type="file" class="form-control @error('payment_proof') is-invalid @enderror" id="payment_proof" name="payment_proof" accept="image/jpeg,image/png,image/webp,application/pdf">
                                <div class="form-text small text-muted">{{ __('portal.public.payment_proof_help') }}</div>
                                @error('payment_proof')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Notes / Remarks -->
                        <div class="mb-4">
                            <label for="payment_notes" class="form-label fw-semibold text-dark mb-1">
                                {{ __('portal.public.payment_notes') }}
                            </label>
                            <textarea class="form-control" id="payment_notes" name="payment_notes" rows="2" placeholder="{{ __('portal.public.payment_notes') }}">{{ old('payment_notes') }}</textarea>
                        </div>

                        <!-- Financial Ledger Disclaimer Notice -->
                        <div class="alert alert-warning d-flex align-items-start gap-2 rounded-3 border mb-4">
                            <i class="bi bi-info-circle-fill fs-5 text-warning flex-shrink-0 mt-1"></i>
                            <div class="small">
                                {{ __('portal.public.financial_disclaimer') }}
                            </div>
                        </div>

                        <!-- Form Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn temple-btn-maroon btn-lg fw-bold shadow-sm py-3" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                                <i class="bi bi-check2-circle me-1" id="submitIcon"></i>
                                <span id="submitBtnText">{{ __('portal.public.submit_btn') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Receiver Account Details Card & QR -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 85px;">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center">
                            <i class="bi bi-building text-warning me-2"></i>
                            {{ __('portal.public.receiver_account_details') }}
                        </h5>
                    </div>
                    <div class="card-body p-4" id="accountDetailsDisplay">
                        <div id="noAccountSelected" class="text-center py-4 text-muted">
                            <i class="bi bi-wallet2 fs-1 text-secondary opacity-50 mb-2"></i>
                            <p class="small mb-0">{{ __('portal.public.select_receiver_account') }}</p>
                        </div>

                        <div id="accountInfoCard" class="d-none">
                            <!-- Account Header Info -->
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="payment-method-badge method-bkash" id="infoMethodBadgeContainer">
                                        <img id="infoMethodLogo" src="{{ asset('assets/images/methods/bkash-short-logo.png') }}" alt="bKash" class="method-logo">
                                        <span id="infoMethodText">bKash</span>
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary border" id="infoTypeBadge">Merchant</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1" id="infoAccountName">—</h6>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="font-monospace fs-5 fw-bold text-danger" id="infoAccountNumber">—</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="copyAccBtn" title="{{ __('portal.public.copy_account_number') }}">
                                        <i class="bi bi-clipboard me-1"></i> <span id="copyBtnText">{{ __('portal.public.copy_account_number') }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Bank Transfer Extra Details -->
                            <div id="bankDetailsContainer" class="d-none mb-3">
                                <div class="p-3 bg-light rounded-3 border small">
                                    <div class="mb-1"><strong>{{ __('portal.public.bank_name') }}:</strong> <span id="infoBankName">—</span></div>
                                    <div class="mb-1"><strong>{{ __('portal.public.branch_name') }}:</strong> <span id="infoBranchName">—</span></div>
                                    <div class="mb-1"><strong>{{ __('portal.public.routing_number') }}:</strong> <span id="infoRoutingNumber" class="font-monospace">—</span></div>
                                    <div id="swiftRow" class="d-none"><strong>{{ __('portal.public.swift_code') }}:</strong> <span id="infoSwiftCode" class="font-monospace">—</span></div>
                                </div>
                            </div>

                            <!-- QR Code Preview -->
                            <div id="qrCodeContainer" class="d-none text-center mb-3">
                                <div class="p-3 bg-light rounded-3 border d-inline-block w-100">
                                    <div class="small fw-semibold text-muted mb-2">
                                        <i class="bi bi-qr-code-scan me-1"></i> {{ __('portal.public.scan_qr') }}
                                    </div>
                                    <img src="" id="infoQrImage" alt="Payment QR Code" class="img-fluid rounded border shadow-sm" style="max-height: 220px;">
                                </div>
                            </div>

                            <!-- Instructions Box -->
                            <div id="instructionsContainer" class="d-none">
                                <div class="alert alert-info small mb-0 rounded-3">
                                    <h6 class="alert-heading fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> {{ __('portal.public.instructions') }}</h6>
                                    <p class="mb-0" id="infoInstructions">—</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const $methodRadios = $('.payment-method-radio');
    const $receiverSelect = $('#receiver_account_id');
    const $accountDetailsDisplay = $('#accountDetailsDisplay');
    const $noAccountSelected = $('#noAccountSelected');
    const $accountInfoCard = $('#accountInfoCard');
    const $infoMethodBadge = $('#infoMethodBadge');
    const $infoTypeBadge = $('#infoTypeBadge');
    const $infoAccountName = $('#infoAccountName');
    const $infoAccountNumber = $('#infoAccountNumber');
    const $bankDetailsContainer = $('#bankDetailsContainer');
    const $infoBankName = $('#infoBankName');
    const $infoBranchName = $('#infoBranchName');
    const $infoRoutingNumber = $('#infoRoutingNumber');
    const $swiftRow = $('#swiftRow');
    const $infoSwiftCode = $('#infoSwiftCode');
    const $qrCodeContainer = $('#qrCodeContainer');
    const $infoQrImage = $('#infoQrImage');
    const $instructionsContainer = $('#instructionsContainer');
    const $infoInstructions = $('#infoInstructions');
    const $copyAccBtn = $('#copyAccBtn');
    const $copyBtnText = $('#copyBtnText');
    const $form = $('#publicPaymentForm');
    const $submitBtn = $('#submitBtn');
    const $submitSpinner = $('#submitSpinner');
    const $submitIcon = $('#submitIcon');
    const $submitBtnText = $('#submitBtnText');

    // Filter receiver accounts by selected payment method
    function filterReceiverAccounts() {
        const selectedMethod = $('input[name="payment_method"]:checked').val();
        let firstAvailableVal = null;
        let hasSelectedOption = false;

        $receiverSelect.find('option').each(function() {
            const $option = $(this);
            const optMethod = $option.data('method');

            if (!optMethod) {
                // Default placeholder option
                $option.show();
                return;
            }

            if (optMethod === selectedMethod) {
                $option.show();
                if (!firstAvailableVal) {
                    firstAvailableVal = $option.val();
                }
                if ($option.is(':selected')) {
                    hasSelectedOption = true;
                }
            } else {
                $option.hide();
                if ($option.is(':selected')) {
                    $option.prop('selected', false);
                }
            }
        });

        // If previously selected option is not for current method, select the first valid option
        if (!hasSelectedOption && firstAvailableVal) {
            $receiverSelect.val(firstAvailableVal);
        }

        updateAccountDisplay();
        toggleRequiredFields(selectedMethod);
    }

    // Toggle fields based on payment method
    function toggleRequiredFields(method) {
        const isBank = (method === 'bank_transfer');
        if (isBank) {
            $('#senderMobileRequiredStar').addClass('d-none');
            $('#trxRefRequiredStar').addClass('d-none');
            $('#sender_mobile').prop('required', false);
            $('#transaction_reference').prop('required', false);
        } else {
            $('#senderMobileRequiredStar').removeClass('d-none');
            $('#trxRefRequiredStar').removeClass('d-none');
            $('#sender_mobile').prop('required', true);
            $('#transaction_reference').prop('required', true);
        }
    }

    // Update the right-side account preview card
    function updateAccountDisplay() {
        const selectedOption = $receiverSelect.find('option:selected');
        const accountId = selectedOption.val();

        if (!accountId) {
            $noAccountSelected.removeClass('d-none');
            $accountInfoCard.addClass('d-none');
            return;
        }

        const method = selectedOption.data('method') || '';
        const name = selectedOption.data('account-name') || '';
        const number = selectedOption.data('account-number') || '';
        const type = selectedOption.data('account-type') || '';
        const bank = selectedOption.data('bank-name') || '';
        const branch = selectedOption.data('branch-name') || '';
        const routing = selectedOption.data('routing') || '';
        const swift = selectedOption.data('swift') || '';
        const instructions = selectedOption.data('instructions') || '';
        const qr = selectedOption.data('qr') || '';

        const methodLogos = {
            bkash: "{{ asset('assets/images/methods/bkash-short-logo.png') }}",
            nagad: "{{ asset('assets/images/methods/nagad-short-logo.png') }}",
            rocket: "{{ asset('assets/images/methods/rocket-short-logo.png') }}",
            bank_transfer: "{{ asset('assets/images/methods/bank.svg') }}",
            cash: "{{ asset('assets/images/methods/cash.svg') }}"
        };
        const methodLabels = {
            bkash: "bKash",
            nagad: "Nagad",
            rocket: "Rocket",
            bank_transfer: "{{ __('portal.payment_methods.bank_transfer') }}",
            cash: "{{ __('donations.method_cash') ?? 'Cash' }}"
        };

        const logoSrc = methodLogos[method] || methodLogos['cash'];
        const labelText = methodLabels[method] || method.toUpperCase();

        $('#infoMethodLogo').attr('src', logoSrc).attr('alt', labelText);
        $('#infoMethodText').text(labelText);
        $('#infoMethodBadgeContainer').attr('class', 'payment-method-badge method-' + method);
        $infoTypeBadge.text(type);
        $infoAccountName.text(name);
        $infoAccountNumber.text(number);

        // Bank info
        if (method === 'bank_transfer' && (bank || branch || routing)) {
            $infoBankName.text(bank || '—');
            $infoBranchName.text(branch || '—');
            $infoRoutingNumber.text(routing || '—');
            if (swift) {
                $infoSwiftCode.text(swift);
                $swiftRow.removeClass('d-none');
            } else {
                $swiftRow.addClass('d-none');
            }
            $bankDetailsContainer.removeClass('d-none');
        } else {
            $bankDetailsContainer.addClass('d-none');
        }

        // QR code
        if (qr) {
            $infoQrImage.attr('src', qr);
            $qrCodeContainer.removeClass('d-none');
        } else {
            $qrCodeContainer.addClass('d-none');
        }

        // Instructions
        if (instructions && instructions.trim() !== '') {
            $infoInstructions.text(instructions);
            $instructionsContainer.removeClass('d-none');
        } else {
            $instructionsContainer.addClass('d-none');
        }

        $noAccountSelected.addClass('d-none');
        $accountInfoCard.removeClass('d-none');
    }

    // Copy account number
    $copyAccBtn.on('click', function() {
        const textToCopy = $infoAccountNumber.text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                $copyBtnText.text('{{ __("portal.public.copied") }}');
                setTimeout(() => $copyBtnText.text('{{ __("portal.public.copy_account_number") }}'), 2000);
            });
        }
    });

    // Pledge card selection sync
    $('.pledge-radio-card').on('click', function() {
        const $radio = $(this).find('input[type="radio"]');
        $radio.prop('checked', true);
        $('.pledge-radio-card').removeClass('border-primary bg-primary bg-opacity-10').addClass('bg-light');
        $(this).addClass('border-primary bg-primary bg-opacity-10').removeClass('bg-light');

        const due = parseFloat($radio.data('due'));
        if (due > 0 && !$('#amount').val()) {
            $('#amount').val(due.toFixed(2));
        }
    });

    // Method selection trigger
    $methodRadios.on('change', function() {
        filterReceiverAccounts();
    });

    // Account selection trigger
    $receiverSelect.on('change', function() {
        updateAccountDisplay();
    });

    // Form submission state
    $form.on('submit', function() {
        $submitBtn.prop('disabled', true);
        $submitSpinner.removeClass('d-none');
        $submitIcon.addClass('d-none');
        $submitBtnText.text('{{ __("portal.public.submitting_btn") }}');
    });

    // Initial setup
    filterReceiverAccounts();
});
</script>
@endpush
