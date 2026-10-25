@extends('layouts.app')

@section('title', __('committees.edit_committee') . ' — ' . $committee->name)

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-light text-dark border font-monospace">{{ $committee->code }}</span>
                <h1 class="h3 fw-bold mb-0 text-dark">{{ __('committees.edit_committee') }}</h1>
            </div>
            <p class="text-muted small mb-0">{{ $committee->name_en }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('committees.show', $committee) }}" class="btn btn-outline-primary rounded-pill px-3">
                <i class="bi bi-eye me-1"></i> {{ __('common.view') }}
            </a>
            <a href="{{ route('committees.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-pencil-square text-primary me-2"></i> Edit Committee Setup
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('committees.update', $committee) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('committees.code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" 
                                       value="{{ old('code', $committee->code) }}" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('committees.status') }} <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', $committee->status) === 'active' ? 'selected' : '' }}>{{ __('committees.status_active') }}</option>
                                    <option value="inactive" {{ old('status', $committee->status) === 'inactive' ? 'selected' : '' }}>{{ __('committees.status_inactive') }}</option>
                                    <option value="dissolved" {{ old('status', $committee->status) === 'dissolved' ? 'selected' : '' }}>{{ __('committees.status_dissolved') }}</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('committees.name_en') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror" 
                                       value="{{ old('name_en', $committee->name_en) }}" required>
                                @error('name_en')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('committees.name_bn') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name_bn" class="form-control @error('name_bn') is-invalid @enderror" 
                                       value="{{ old('name_bn', $committee->name_bn) }}" required>
                                @error('name_bn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('committees.term_start_date') }}</label>
                                <input type="date" name="term_start_date" class="form-control @error('term_start_date') is-invalid @enderror" 
                                       value="{{ old('term_start_date', $committee->term_start_date?->toDateString()) }}">
                                @error('term_start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('committees.term_end_date') }}</label>
                                <input type="date" name="term_end_date" class="form-control @error('term_end_date') is-invalid @enderror" 
                                       value="{{ old('term_end_date', $committee->term_end_date?->toDateString()) }}">
                                @error('term_end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('committees.description_en') }}</label>
                                <textarea name="description_en" class="form-control" rows="2">{{ old('description_en', $committee->description_en) }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('committees.description_bn') }}</label>
                                <textarea name="description_bn" class="form-control" rows="2">{{ old('description_bn', $committee->description_bn) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm"
                                    style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                                <i class="bi bi-check-circle me-1"></i> {{ __('common.save_changes') }}
                            </button>
                            <a href="{{ route('committees.show', $committee) }}" class="btn btn-outline-secondary rounded-pill px-3">
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
