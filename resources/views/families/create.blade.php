@extends('layouts.app')

@section('title', __('families.add_family'))

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('families.add_family') }}</h1>
            <p class="text-muted small mb-0">{{ __('families.family_units') }}</p>
        </div>
        <a href="{{ route('families.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-house-add text-primary me-2"></i> Household Unit Information
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('families.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('families.family_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="family_name" class="form-control @error('family_name') is-invalid @enderror" 
                                       value="{{ old('family_name') }}" required placeholder="e.g. Chatterjee Family (চট্টোপাধ্যায় পরিবার)">
                                @error('family_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('families.primary_contact') }}</label>
                                <select name="primary_contact_person_id" class="form-select select2 @error('primary_contact_person_id') is-invalid @enderror" data-placeholder="{{ __('families.select_primary_contact') }}">
                                    <option value="">{{ __('families.select_primary_contact') }}</option>
                                    @foreach($people as $person)
                                        <option value="{{ $person->id }}" {{ old('primary_contact_person_id') == $person->id ? 'selected' : '' }}>
                                            {{ $person->name_en }} @if($person->name_bn) ({{ $person->name_bn }}) @endif — {{ $person->person_code }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('primary_contact_person_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('families.phone') }}</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone') }}" placeholder="+88017XXXXXXXX">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('families.area') }}</label>
                                <input type="text" name="area" class="form-control @error('area') is-invalid @enderror" 
                                       value="{{ old('area') }}" placeholder="e.g. Uttara, Dhanmondi, Old Town">
                                @error('area')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('families.status') }} <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>{{ __('common.status_active') }}</option>
                                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>{{ __('common.status_inactive') }}</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('families.address') }}</label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="House / Flat, Street, Area...">{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('families.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Remarks about household...">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm"
                                    style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                                <i class="bi bi-check-circle me-1"></i> {{ __('common.save') }}
                            </button>
                            <a href="{{ route('families.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                                {{ __('common.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
