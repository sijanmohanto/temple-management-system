@extends('layouts.app')

@section('title', __('devotee.profile_settings_title'))

@push('styles')
<style>
    .temple-btn-maroon,
    .btn.temple-btn-maroon {
        background: linear-gradient(135deg, #75141c, #560e15) !important;
        border: 1px solid #560e15 !important;
        color: #ffffff !important;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(117, 20, 28, 0.25);
        transition: all 0.2s ease-in-out;
    }
    .temple-btn-maroon:hover,
    .btn.temple-btn-maroon:hover,
    .temple-btn-maroon:focus,
    .btn.temple-btn-maroon:focus {
        background: linear-gradient(135deg, #560e15, #3b090e) !important;
        border-color: #3b090e !important;
        color: #ffffff !important;
        box-shadow: 0 6px 16px rgba(117, 20, 28, 0.45);
        transform: translateY(-1px);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-person-gear text-danger me-2"></i> {{ __('devotee.profile_settings_title') }}
                    </h3>
                    <p class="text-muted small mb-0">{{ __('devotee.profile_settings_subtitle') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('devotee.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-speedometer2 me-1"></i> {{ __('devotee.back_to_dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert if devotee does not have a registered email -->
    @if(! $hasRegisteredEmail)
        <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-2 text-warning"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">{{ __('devotee.no_registered_email_title') }}</h5>
                    <p class="mb-0 text-muted small">
                        {{ __('devotee.no_registered_email_notice') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left: Profile Details Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center">
                        <i class="bi bi-person-vcard text-primary me-2"></i> {{ __('devotee.personal_details') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <!-- Read-Only Identity Fields (Admin-Managed) -->
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">{{ __('people.person_code') }}</label>
                            <input type="text" class="form-control bg-light" value="{{ $person->person_code }}" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-muted">
                                {{ __('people.name_en') }} / {{ __('people.name_bn') }}
                                <span class="badge bg-secondary bg-opacity-10 text-secondary ms-1" style="font-size: 0.68rem;">{{ __('devotee.admin_managed') }}</span>
                            </label>
                            <input type="text" class="form-control bg-light" value="{{ $person->name_en }} {{ $person->name_bn ? '('.$person->name_bn.')' : '' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">
                                {{ __('people.phone') }}
                                <span class="badge bg-secondary bg-opacity-10 text-secondary ms-1" style="font-size: 0.68rem;">{{ __('devotee.admin_managed') }}</span>
                            </label>
                            <input type="text" class="form-control bg-light font-monospace" value="{{ $person->phone ?? '—' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">{{ __('people.gotra') }}</label>
                            <input type="text" class="form-control bg-light" value="{{ $person->gotra ?? '—' }}" readonly>
                        </div>
                    </div>

                    <!-- Permitted Self-Editable Fields with Email OTP -->
                    <form method="POST" action="{{ route('devotee.profile.update') }}" id="profileForm">
                        @csrf
                        @method('PUT')

                        <!-- Hidden OTP input filled by modal on verification -->
                        <input type="hidden" name="otp_code" id="profile_otp_code" value="{{ old('otp_code') }}">

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold text-secondary">{{ __('people.email') }}</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $person->email ?: $user->email) }}"
                                   placeholder="devotee@example.com" {{ ! $hasRegisteredEmail ? 'disabled' : '' }}>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted" style="font-size: 0.75rem;">{{ __('devotee.email_helper_text') }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="occupation" class="form-label small fw-semibold text-secondary">{{ __('people.occupation') }}</label>
                            <input type="text" id="occupation" name="occupation" class="form-control @error('occupation') is-invalid @enderror"
                                   value="{{ old('occupation', $person->occupation) }}"
                                   placeholder="e.g. Teacher, Business, Engineer" {{ ! $hasRegisteredEmail ? 'disabled' : '' }}>
                            @error('occupation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="present_address" class="form-label small fw-semibold text-secondary">{{ __('people.present_address') }}</label>
                            <textarea id="present_address" name="present_address" class="form-control @error('present_address') is-invalid @enderror"
                                      rows="2" {{ ! $hasRegisteredEmail ? 'disabled' : '' }}>{{ old('present_address', $person->present_address) }}</textarea>
                            @error('present_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="permanent_address" class="form-label small fw-semibold text-secondary">{{ __('people.permanent_address') }}</label>
                            <textarea id="permanent_address" name="permanent_address" class="form-control @error('permanent_address') is-invalid @enderror"
                                      rows="2" {{ ! $hasRegisteredEmail ? 'disabled' : '' }}>{{ old('permanent_address', $person->permanent_address) }}</textarea>
                            @error('permanent_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($hasRegisteredEmail)
                            <div class="pt-2">
                                <button type="button" class="btn temple-btn-maroon px-4 py-2 rounded-pill fw-semibold shadow-sm text-white" id="btnInitiateProfileSave" style="background: linear-gradient(135deg, #75141c, #560e15) !important; border: 1px solid #560e15 !important; color: #ffffff !important; box-shadow: 0 4px 12px rgba(117, 20, 28, 0.3);">
                                    <i class="bi bi-check-circle-fill me-1"></i> {{ __('devotee.save_profile_changes') }}
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Change Password Card -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center">
                        <i class="bi bi-shield-lock text-danger me-2"></i> {{ __('devotee.change_password_title') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if(! $hasRegisteredEmail)
                        <div class="alert alert-secondary text-center py-4 mb-0">
                            <i class="bi bi-lock-fill text-muted fs-3"></i>
                            <p class="small text-muted mt-2 mb-0">
                                {{ __('devotee.password_change_locked_notice') }}
                            </p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('devotee.password.update') }}" id="passwordForm">
                            @csrf
                            @method('PUT')

                            <!-- Hidden OTP input filled by modal on verification -->
                            <input type="hidden" name="otp_code" id="password_otp_code" value="{{ old('otp_code') }}">

                            <!-- Current Password -->
                            <div class="mb-3">
                                <label for="current_password" class="form-label small fw-semibold text-secondary">{{ __('devotee.current_password') }} <span class="text-danger">*</span></label>
                                <input type="password" id="current_password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div class="mb-3">
                                <label for="new_password" class="form-label small fw-semibold text-secondary">{{ __('devotee.new_password') }} <span class="text-danger">*</span></label>
                                <input type="password" id="new_password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Confirm New Password -->
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label small fw-semibold text-secondary">{{ __('devotee.confirm_password') }} <span class="text-danger">*</span></label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8">
                            </div>

                            <button type="button" class="btn temple-btn-maroon w-100 py-2 rounded-pill fw-semibold shadow-sm text-white" id="btnInitiatePasswordSave" style="background: linear-gradient(135deg, #75141c, #560e15) !important; border: 1px solid #560e15 !important; color: #ffffff !important; box-shadow: 0 4px 12px rgba(117, 20, 28, 0.3);">
                                <i class="bi bi-shield-lock-fill me-1"></i> {{ __('devotee.update_password_btn') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Security OTP Verification Modal -->
<div class="modal fade" id="otpVerificationModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="otpModalLabel">
                    <i class="bi bi-shield-lock-fill text-danger me-2"></i>
                    {{ __('devotee.otp_modal_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="otpModalCloseBtn"></button>
            </div>
            
            <div class="modal-body p-4 text-center">
                <!-- State 1: Sending OTP in Progress -->
                <div id="otpSendingState" class="py-4">
                    <div class="spinner-border text-danger mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">{{ __('devotee.otp_sending_title') }}</h5>
                    <p class="text-muted small mb-0 px-3" id="otpSendingInstruction">
                        {{ __('devotee.otp_sending_instruction', ['email' => $registeredEmail]) }}
                    </p>
                </div>

                <!-- State 2: OTP Entry Form (Shown after email is dispatched) -->
                <div id="otpEntryState" class="d-none">
                    <div class="text-center mb-4">
                        <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 d-inline-flex align-items-center justify-content-center mb-2" style="width: 56px; height: 56px;">
                            <i class="bi bi-envelope-check-fill fs-2"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1" id="otpSentTitle">{{ __('devotee.email_otp_required') }}</h6>
                        <p class="text-muted small mb-0 px-2" id="otpSentInstruction">
                            {{ __('devotee.otp_sent_instruction', ['email' => $registeredEmail]) }}
                        </p>
                    </div>

                    <!-- 6-Digit OTP Input -->
                    <div class="mb-4 text-center">
                        <label for="modalOtpInput" class="form-label small fw-bold text-dark mb-2">
                            {{ __('devotee.otp_enter_code') }}
                        </label>
                        <input type="text" id="modalOtpInput" class="form-control font-monospace text-center mx-auto"
                               maxlength="6" placeholder="{{ __('devotee.otp_code_placeholder') }}" autocomplete="one-time-code"
                               style="max-width: 250px; letter-spacing: 8px; font-size: 1.6rem; font-weight: bold; border-width: 2px;">
                        <div class="invalid-feedback text-center mt-2 d-none" id="modalOtpError"></div>
                    </div>

                    <!-- Countdown & Resend Section -->
                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 border mb-2 small">
                        <div>
                            <span class="text-muted me-1"><i class="bi bi-clock me-1"></i></span>
                            <span class="fw-semibold text-secondary" id="otpCountdownTimer">01:00</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="modalResendOtpBtn" disabled>
                            <i class="bi bi-arrow-clockwise me-1"></i> {{ __('devotee.otp_resend_btn') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top py-3 px-4 d-flex justify-content-between" id="otpModalFooter">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">
                    {{ __('common.cancel') }}
                </button>
                <button type="button" class="btn temple-btn-maroon btn-sm px-4 d-none fw-semibold shadow-sm text-white" id="modalVerifySubmitBtn" style="background: linear-gradient(135deg, #75141c, #560e15) !important; border: 1px solid #560e15 !important; color: #ffffff !important;">
                    <i class="bi bi-shield-check me-1"></i> {{ __('devotee.otp_verify_btn') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var activePurpose = null;
    var activeForm = null;
    var countdownInterval = null;
    var otpModal = new bootstrap.Modal(document.getElementById('otpVerificationModal'));
    var registeredEmail = @json($registeredEmail);

    function startCountdown(seconds) {
        clearInterval(countdownInterval);
        var remaining = seconds;
        var timerDisplay = $('#otpCountdownTimer');
        var resendBtn = $('#modalResendOtpBtn');

        resendBtn.prop('disabled', true);

        function updateDisplay() {
            var mins = Math.floor(remaining / 60);
            var secs = remaining % 60;
            var formatted = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
            timerDisplay.text(formatted);

            if (remaining <= 0) {
                clearInterval(countdownInterval);
                timerDisplay.html('<span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i> Expired</span>');
                resendBtn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i> {{ __("devotee.otp_resend_btn") }}');
            }
        }

        updateDisplay();
        countdownInterval = setInterval(function() {
            remaining--;
            updateDisplay();
        }, 1000);
    }

    function dispatchOtpRequest() {
        // Show Sending State, hide Entry State
        $('#otpSendingState').removeClass('d-none');
        $('#otpEntryState').addClass('d-none');
        $('#modalVerifySubmitBtn').addClass('d-none');
        $('#modalOtpInput').val('').removeClass('is-invalid');
        $('#modalOtpError').addClass('d-none').text('');

        $.ajax({
            url: "{{ route('devotee.profile.otp') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                purpose: activePurpose
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    var displayEmail = res.masked_email || res.email || registeredEmail;
                    var sentText = "{{ __('devotee.otp_sent_instruction', ['email' => ':email']) }}".replace(':email', displayEmail);
                    $('#otpSentInstruction').text(sentText);

                    // Transition to Entry State
                    $('#otpSendingState').addClass('d-none');
                    $('#otpEntryState').removeClass('d-none');
                    $('#modalVerifySubmitBtn').removeClass('d-none');

                    // Focus OTP input and start 60s countdown
                    setTimeout(function() {
                        $('#modalOtpInput').focus();
                    }, 300);

                    startCountdown(60);
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message || 'Failed to dispatch verification code.');
                    otpModal.hide();
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to send verification code.';
                toastr.error(msg);
                otpModal.hide();
            }
        });
    }

    // 1. Profile Update Form Trigger
    $('#btnInitiateProfileSave').on('click', function(e) {
        e.preventDefault();
        var emailInput = $('#email');
        if (emailInput.val() && !emailInput[0].checkValidity()) {
            toastr.error("{{ __('devotee.otp_fill_required_error') }}");
            emailInput.focus();
            return;
        }

        activePurpose = 'profile_update';
        activeForm = $('#profileForm');
        otpModal.show();
        dispatchOtpRequest();
    });

    // 2. Password Update Form Trigger
    $('#btnInitiatePasswordSave').on('click', function(e) {
        e.preventDefault();
        var currentPass = $('#current_password').val();
        var newPass = $('#new_password').val();
        var confirmPass = $('#password_confirmation').val();

        if (!currentPass || !newPass || !confirmPass) {
            toastr.error("{{ __('devotee.otp_fill_required_error') }}");
            return;
        }

        if (newPass.length < 8) {
            toastr.error("{{ __('validation.min.string', ['attribute' => 'password', 'min' => 8]) }}");
            $('#new_password').focus();
            return;
        }

        if (newPass !== confirmPass) {
            toastr.error("{{ __('validation.confirmed', ['attribute' => 'password']) }}");
            $('#password_confirmation').focus();
            return;
        }

        activePurpose = 'password_change';
        activeForm = $('#passwordForm');
        otpModal.show();
        dispatchOtpRequest();
    });

    // 3. Resend OTP Button in Modal
    $('#modalResendOtpBtn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Sending...');

        $.ajax({
            url: "{{ route('devotee.profile.otp') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                purpose: activePurpose
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    startCountdown(60);
                    $('#modalOtpInput').val('').focus();
                } else {
                    toastr.error(res.message);
                    btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i> {{ __("devotee.otp_resend_btn") }}');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to resend code.';
                toastr.error(msg);
                btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i> {{ __("devotee.otp_resend_btn") }}');
            }
        });
    });

    // 4. Verify & Confirm Button in Modal
    $('#modalVerifySubmitBtn').on('click', function() {
        var code = $('#modalOtpInput').val().trim();
        if (code.length !== 6) {
            $('#modalOtpInput').addClass('is-invalid');
            $('#modalOtpError').removeClass('d-none').text("{{ __('devotee.otp_invalid_code_length') }}");
            $('#modalOtpInput').focus();
            return;
        }

        $('#modalOtpInput').removeClass('is-invalid');
        $('#modalOtpError').addClass('d-none');

        // Loading state on verify button
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> {{ __("common.processing") }}...');

        // Populate hidden input and submit form
        if (activeForm) {
            activeForm.find('input[name="otp_code"]').val(code);
            activeForm.submit();
        }
    });

    // Enter key submits inside modal OTP input
    $('#modalOtpInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#modalVerifySubmitBtn').click();
        }
    });

    // Clean up timer when modal is closed
    $('#otpVerificationModal').on('hidden.bs.modal', function() {
        clearInterval(countdownInterval);
        $('#modalVerifySubmitBtn').prop('disabled', false).html('<i class="bi bi-shield-check me-1"></i> {{ __("devotee.otp_verify_btn") }}');
    });
});
</script>
@endpush
