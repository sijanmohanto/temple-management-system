@extends('layouts.app')

@section('title', __('devotee.profile_settings_title'))

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
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
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
                    <form method="POST" action="{{ route('devotee.profile.update') }}">
                        @csrf
                        @method('PUT')

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
                            <!-- Email OTP Verification Box -->
                            <div class="p-3 bg-light border rounded-3 mb-4">
                                <label for="profile_otp" class="form-label small fw-bold text-dark d-flex justify-content-between">
                                    <span><i class="bi bi-shield-lock text-danger me-1"></i> {{ __('devotee.email_otp_required') }}</span>
                                    <button type="button" class="btn btn-xs btn-outline-danger send-otp-btn" data-purpose="profile_update">
                                        <i class="bi bi-send me-1"></i> {{ __('devotee.send_otp_btn') }}
                                    </button>
                                </label>
                                <input type="text" id="profile_otp" name="otp_code" class="form-control font-monospace text-center @error('otp_code') is-invalid @enderror"
                                       maxlength="6" placeholder="000000" style="letter-spacing: 4px; font-size: 1.25rem;">
                                @error('otp_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;" id="profileOtpFeedback">
                                    {{ __('devotee.otp_input_hint', ['email' => $registeredEmail]) }}
                                </small>
                            </div>

                            <button type="submit" class="btn temple-btn-maroon px-4 rounded-pill">
                                <i class="bi bi-check-circle me-1"></i> {{ __('devotee.save_profile_changes') }}
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Change Password Card -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
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
                        <form method="POST" action="{{ route('devotee.password.update') }}">
                            @csrf
                            @method('PUT')

                            <!-- Current Password -->
                            <div class="mb-3">
                                <label for="current_password" class="form-label small fw-semibold text-secondary">{{ __('devotee.current_password') }}</label>
                                <input type="password" id="current_password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div class="mb-3">
                                <label for="new_password" class="form-label small fw-semibold text-secondary">{{ __('devotee.new_password') }}</label>
                                <input type="password" id="new_password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Confirm New Password -->
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label small fw-semibold text-secondary">{{ __('devotee.confirm_password') }}</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8">
                            </div>

                            <!-- OTP Box for Password Change -->
                            <div class="p-3 bg-light border rounded-3 mb-4">
                                <label for="password_otp" class="form-label small fw-bold text-dark d-flex justify-content-between">
                                    <span><i class="bi bi-shield-check text-danger me-1"></i> {{ __('devotee.email_otp_required') }}</span>
                                    <button type="button" class="btn btn-xs btn-outline-danger send-otp-btn" data-purpose="password_change">
                                        <i class="bi bi-send me-1"></i> {{ __('devotee.send_otp_btn') }}
                                    </button>
                                </label>
                                <input type="text" id="password_otp" name="otp_code" class="form-control font-monospace text-center @error('otp_code') is-invalid @enderror"
                                       maxlength="6" placeholder="000000" style="letter-spacing: 4px; font-size: 1.25rem;" required>
                                @error('otp_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;" id="passwordOtpFeedback">
                                    {{ __('devotee.otp_input_hint', ['email' => $registeredEmail]) }}
                                </small>
                            </div>

                            <button type="submit" class="btn temple-btn-maroon w-100 rounded-pill">
                                <i class="bi bi-shield-check me-1"></i> {{ __('devotee.update_password_btn') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.send-otp-btn').on('click', function() {
        var btn = $(this);
        var purpose = btn.data('purpose');
        var feedbackTarget = purpose === 'password_change' ? $('#passwordOtpFeedback') : $('#profileOtpFeedback');

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Sending...');

        $.ajax({
            url: "{{ route('devotee.profile.otp') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                purpose: purpose
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    feedbackTarget.html('<span class="text-success"><i class="bi bi-check-circle me-1"></i> ' + res.message + '</span>');
                    btn.html('<i class="bi bi-check2 me-1"></i> Sent');

                    var countdown = 60;
                    var timer = setInterval(function() {
                        countdown--;
                        btn.text('Resend (' + countdown + 's)');
                        if (countdown <= 0) {
                            clearInterval(timer);
                            btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i> Send OTP');
                        }
                    }, 1000);
                } else {
                    toastr.error(res.message);
                    btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i> Send OTP');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to send OTP.';
                toastr.error(msg);
                btn.prop('disabled', false).html('<i class="bi bi-send me-1"></i> Send OTP');
            }
        });
    });
});
</script>
@endpush
