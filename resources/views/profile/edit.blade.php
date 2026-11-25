@extends('layouts.app')

@section('title', __('common.profile_settings'))

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
                        <i class="bi bi-person-gear text-danger me-2"></i> {{ __('common.profile_settings') }}
                    </h3>
                    <p class="text-muted small mb-0">{{ __('devotee.profile_settings_subtitle') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ auth()->user()->hasRole('devotee') ? route('devotee.dashboard') : route('dashboard') }}" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-speedometer2 me-1"></i> {{ __('devotee.back_to_dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Profile Information Form -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-person-lines-fill text-primary me-2"></i> {{ __('common.profile_information') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label small fw-semibold text-secondary">
                                {{ __('auth.login_title') == 'Sign In to Temple Portal' ? 'Full Name' : 'পূর্ণ নাম' }}
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $user->name) }}"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold text-secondary">
                                {{ __('auth.email_label') }}
                            </label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email', $user->email) }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label for="phone" class="form-label small fw-semibold text-secondary">
                                {{ __('common.phone_number') }}
                            </label>
                            <input type="text"
                                   class="form-control @error('phone') is-invalid @enderror font-monospace"
                                   id="phone"
                                   name="phone"
                                   value="{{ old('phone', $user->phone) }}"
                                   placeholder="+8801700000000">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Preferred Locale -->
                        <div class="mb-3">
                            <label for="preferred_locale" class="form-label small fw-semibold text-secondary">
                                {{ __('common.preferred_language') }}
                            </label>
                            <select class="form-select @error('preferred_locale') is-invalid @enderror"
                                    id="preferred_locale"
                                    name="preferred_locale"
                                    required>
                                <option value="en" {{ old('preferred_locale', $user->preferred_locale) === 'en' ? 'selected' : '' }}>
                                    English (Default)
                                </option>
                                <option value="bn" {{ old('preferred_locale', $user->preferred_locale) === 'bn' ? 'selected' : '' }}>
                                    বাংলা (Bengali)
                                </option>
                            </select>
                            @error('preferred_locale')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="mb-4">
                            <label for="address" class="form-label small fw-semibold text-secondary">
                                {{ __('common.address') }}
                            </label>
                            <textarea class="form-control @error('address') is-invalid @enderror"
                                      id="address"
                                      name="address"
                                      rows="3">{{ old('address', $user->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end pt-2">
                            <button type="submit"
                                    class="btn temple-btn-maroon px-4 py-2 fw-semibold rounded-pill shadow-sm text-white"
                                    style="background: linear-gradient(135deg, #75141c, #560e15) !important; border: 1px solid #560e15 !important; color: #ffffff !important; box-shadow: 0 4px 12px rgba(117, 20, 28, 0.3);">
                                <i class="bi bi-check-circle-fill me-1"></i> {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Password Change Form -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-shield-lock text-danger me-2"></i> {{ __('auth.password_label') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf
                        @method('PUT')

                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label small fw-semibold text-secondary">
                                {{ __('common.current_password') }}
                            </label>
                            <input type="password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   id="current_password"
                                   name="current_password"
                                   required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label small fw-semibold text-secondary">
                                {{ __('common.new_password') }}
                            </label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="new_password"
                                   name="password"
                                   required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label small fw-semibold text-secondary">
                                {{ __('common.confirm_password') }}
                            </label>
                            <input type="password"
                                   class="form-control"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   required>
                        </div>

                        <div class="d-flex justify-content-end pt-2">
                            <button type="submit"
                                    class="btn temple-btn-maroon px-4 py-2 fw-semibold rounded-pill shadow-sm text-white"
                                    style="background: linear-gradient(135deg, #75141c, #560e15) !important; border: 1px solid #560e15 !important; color: #ffffff !important; box-shadow: 0 4px 12px rgba(117, 20, 28, 0.3);">
                                <i class="bi bi-shield-lock-fill me-1"></i> {{ __('devotee.update_password_btn') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
