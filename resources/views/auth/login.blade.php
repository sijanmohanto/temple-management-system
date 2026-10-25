@extends('layouts.guest')

@section('title', __('auth.login_title'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header p-4 text-center text-white" style="background: linear-gradient(135deg, var(--temple-maroon), var(--temple-maroon-dark));">
                <span class="temple-emblem mx-auto mb-2" style="width: 48px; height: 48px; font-size: 1.5rem;">ॐ</span>
                <h4 class="fw-bold mb-1">{{ __('auth.login_title') }}</h4>
                <p class="small text-light opacity-75 mb-0">{{ __('auth.login_subtitle') }}</p>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <!-- Email Input -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold text-secondary">
                            <i class="bi bi-envelope me-1 text-primary"></i> {{ __('auth.email_label') }}
                        </label>
                        <input type="email"
                               class="form-control form-control-lg @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="{{ __('auth.email_placeholder') }}"
                               required
                               autocomplete="email"
                               autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password Input -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold text-secondary">
                            <i class="bi bi-shield-lock me-1 text-primary"></i> {{ __('auth.password_label') }}
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control form-control-lg @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   placeholder="{{ __('auth.password_placeholder') }}"
                                   required
                                   autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="remember">
                                {{ __('auth.remember_me') }}
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-lg temple-btn-maroon py-2 fw-bold">
                            <i class="bi bi-box-arrow-in-right me-2"></i> {{ __('auth.sign_in_btn') }}
                        </button>
                    </div>
                </form>

                <!-- Quick Test Credentials Helper (Phase 1 Testing Convenience) -->
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="text-muted fw-bold">{{ __('auth.demo_accounts') }}</small>
                        <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Phase 1 Fast Switch</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button"
                                class="btn btn-xs btn-outline-danger autofill-btn"
                                data-email="admin@temple.local"
                                data-pass="Admin@123456">
                            <i class="bi bi-shield-shaded me-1"></i> Super Admin
                        </button>
                        <button type="button"
                                class="btn btn-xs btn-outline-success autofill-btn"
                                data-email="treasurer@temple.local"
                                data-pass="Treasurer@123456">
                            <i class="bi bi-cash-stack me-1"></i> Treasurer
                        </button>
                        <button type="button"
                                class="btn btn-xs btn-outline-primary autofill-btn"
                                data-email="member@temple.local"
                                data-pass="Member@123456">
                            <i class="bi bi-award me-1"></i> Committee
                        </button>
                        <button type="button"
                                class="btn btn-xs btn-outline-secondary autofill-btn"
                                data-email="devotee@temple.local"
                                data-pass="Devotee@123456">
                            <i class="bi bi-person me-1"></i> Devotee
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2 fst-italic" style="font-size: 0.72rem;">
                        {{ __('auth.demo_accounts_hint') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle password visibility
        $('#togglePasswordBtn').on('click', function() {
            var pwdField = $('#password');
            var icon = $('#togglePasswordIcon');
            if (pwdField.attr('type') === 'password') {
                pwdField.attr('type', 'text');
                icon.removeClass('bi-eye').addClass('bi-eye-slash');
            } else {
                pwdField.attr('type', 'password');
                icon.removeClass('bi-eye-slash').addClass('bi-eye');
            }
        });

        // Demo autofill click
        $('.autofill-btn').on('click', function() {
            var email = $(this).data('email');
            var pass = $(this).data('pass');
            $('#email').val(email);
            $('#password').val(pass);
        });
    });
</script>
@endpush
