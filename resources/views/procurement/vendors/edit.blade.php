@extends('layouts.app')

@section('title', __('procurement.edit_vendor').' - '.$vendor->name)

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 mb-1 text-gray-800 fw-bold">
                    <i class="bi bi-pencil-square text-primary me-2"></i>{{ __('procurement.edit_vendor') }}
                </h1>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('procurement.vendors.index') }}">{{ __('procurement.vendors') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('procurement.vendors.show', $vendor) }}">{{ $vendor->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('procurement.edit_vendor') }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('procurement.vendors.show', $vendor) }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-eye me-1"></i> {{ __('common.view') }}
            </a>
            <a href="{{ route('procurement.vendors.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('procurement.vendors.update', $vendor) }}" method="POST" id="vendorEditForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Left Column: Business & Contact Information -->
            <div class="col-lg-8">
                <!-- Business Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-building me-1 text-primary"></i> {{ __('procurement.vendor_details') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Name -->
                            <div class="col-md-7">
                                <label for="name" class="form-label small fw-bold">
                                    {{ __('procurement.vendor_name') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $vendor->name) }}" placeholder="{{ __('procurement.vendor_name_placeholder') }}" required autofocus>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Vendor Type -->
                            <div class="col-md-5">
                                <label for="vendor_type" class="form-label small fw-bold">{{ __('procurement.vendor_type') }}</label>
                                <select name="vendor_type" id="vendor_type" class="form-select @error('vendor_type') is-invalid @enderror">
                                    <option value="">-- {{ __('procurement.select_vendor_type') }} --</option>
                                    @foreach($vendorTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('vendor_type', $vendor->vendor_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('vendor_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tax ID / Trade License -->
                            <div class="col-md-6">
                                <label for="tax_id" class="form-label small fw-bold">{{ __('procurement.tax_id') }}</label>
                                <input type="text" name="tax_id" id="tax_id" class="form-control @error('tax_id') is-invalid @enderror"
                                       value="{{ old('tax_id', $vendor->tax_id) }}" placeholder="{{ __('procurement.tax_id_placeholder') }}">
                                @error('tax_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Address -->
                            <div class="col-12">
                                <label for="address" class="form-label small fw-bold">{{ __('procurement.address') }}</label>
                                <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror"
                                          placeholder="{{ __('procurement.address_placeholder') }}">{{ old('address', $vendor->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Person Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-person-lines-fill me-1 text-primary"></i> {{ __('procurement.contact_person') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Contact Person Name -->
                            <div class="col-md-12">
                                <label for="contact_person" class="form-label small fw-bold">{{ __('procurement.contact_person') }}</label>
                                <input type="text" name="contact_person" id="contact_person" class="form-control @error('contact_person') is-invalid @enderror"
                                       value="{{ old('contact_person', $vendor->contact_person) }}" placeholder="{{ __('procurement.contact_person_placeholder') }}">
                                @error('contact_person')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6">
                                <label for="phone" class="form-label small fw-bold">{{ __('procurement.phone') }}</label>
                                <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $vendor->phone) }}" placeholder="{{ __('procurement.phone_placeholder') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label for="email" class="form-label small fw-bold">{{ __('procurement.email') }}</label>
                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $vendor->email) }}" placeholder="{{ __('procurement.email_placeholder') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Status & Notes -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-gear me-1 text-primary"></i> {{ __('procurement.is_active') }} &amp; {{ __('procurement.notes') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Active Toggle -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" {{ old('is_active', $vendor->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold small" for="is_active">{{ __('procurement.active_vendor') }}</label>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label small fw-bold">{{ __('procurement.notes') }}</label>
                            <textarea name="notes" id="notes" rows="4" class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="{{ __('procurement.notes_placeholder') }}">{{ old('notes', $vendor->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="btnUpdateVendor">
                                <i class="bi bi-check-circle me-1"></i> {{ __('common.update') }}
                            </button>
                            <a href="{{ route('procurement.vendors.show', $vendor) }}" class="btn btn-outline-secondary">
                                {{ __('common.cancel') }}
                            </a>
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
    $('#vendorEditForm').on('submit', function() {
        var btn = $('#btnUpdateVendor');
        if (typeof TempleUI !== 'undefined' && TempleUI.showButtonLoader) {
            TempleUI.showButtonLoader(btn, '{{ __('common.processing') }}...');
        } else {
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> {{ __('common.processing') }}...');
        }
    });
});
</script>
@endpush
