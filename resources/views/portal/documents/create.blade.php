@extends('layouts.app')

@section('title', __('portal.documents.upload_document'))

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('portal.documents.index') }}">{{ __('portal.event_documents') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('portal.documents.upload_document') }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ __('portal.documents.upload_document') }}</h1>
            <p class="text-muted small mb-0">{{ __('portal.event_documents_subtitle') }}</p>
        </div>
        <div>
            <a href="{{ route('portal.documents.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('portal.actions.back_to_list') }}
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 fw-semibold">{{ __('portal.documents.upload_document') }}</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('portal.documents.store') }}" method="POST" enctype="multipart/form-data" id="documentUploadForm">
                        @csrf

                        {{-- Event Selector --}}
                        <div class="mb-3">
                            <label for="event_id" class="form-label fw-bold">
                                {{ __('portal.documents.event') }} <span class="text-danger">*</span>
                            </label>
                            <select name="event_id" id="event_id" class="form-select select2-enable @error('event_id') is-invalid @enderror" required>
                                <option value="">-- {{ __('common.select') }} --</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}" {{ (old('event_id', $preselectedEventId) == $event->id) ? 'selected' : '' }}>
                                        {{ $event->name }} ({{ $event->category?->name ?: __('common.general') }}) – {{ $event->event_year }}
                                    </option>
                                @endforeach
                            </select>
                            @error('event_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Document Type --}}
                        <div class="mb-3">
                            <label for="document_type" class="form-label fw-bold">
                                {{ __('portal.documents.type') }} <span class="text-danger">*</span>
                            </label>
                            <select name="document_type" id="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                                <option value="">-- {{ __('common.select') }} --</option>
                                <option value="financial_report" {{ old('document_type') === 'financial_report' ? 'selected' : '' }}>
                                    {{ __('portal.document_types.financial_report') }}
                                </option>
                                <option value="audit_report" {{ old('document_type') === 'audit_report' ? 'selected' : '' }}>
                                    {{ __('portal.document_types.audit_report') }}
                                </option>
                                <option value="supporting_document" {{ old('document_type') === 'supporting_document' ? 'selected' : '' }}>
                                    {{ __('portal.document_types.supporting_document') }}
                                </option>
                                <option value="other" {{ old('document_type') === 'other' ? 'selected' : '' }}>
                                    {{ __('portal.document_types.other') }}
                                </option>
                            </select>
                            @error('document_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Titles (English & Bengali) --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="title_en" class="form-label fw-bold">
                                    {{ __('portal.documents.title_en') }} <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="title_en" id="title_en"
                                       class="form-control @error('title_en') is-invalid @enderror"
                                       value="{{ old('title_en') }}" required maxlength="255"
                                       placeholder="e.g. Durga Puja 2025 – Complete Income & Expense Financial Report">
                                @error('title_en')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="title_bn" class="form-label fw-bold font-bengali">
                                    {{ __('portal.documents.title_bn') }}
                                </label>
                                <input type="text" name="title_bn" id="title_bn"
                                       class="form-control font-bengali @error('title_bn') is-invalid @enderror"
                                       value="{{ old('title_bn') }}" maxlength="255"
                                       placeholder="যেমন: দুর্গাপূজা ২০২৫ – পূর্ণাঙ্গ আয়-ব্যয়ের হিসাব">
                                @error('title_bn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- File Input --}}
                        <div class="mb-3">
                            <label for="file" class="form-label fw-bold">
                                {{ __('portal.documents.file') }} <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="file" id="file"
                                   class="form-control @error('file') is-invalid @enderror"
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="form-text text-muted">
                                {{ __('common.allowed_types') }}: PDF, JPG, PNG (Max: 10MB)
                            </div>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Descriptions (English & Bengali) --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="description_en" class="form-label fw-bold">
                                    {{ __('portal.documents.description_en') }}
                                </label>
                                <textarea name="description_en" id="description_en" rows="3"
                                          class="form-control @error('description_en') is-invalid @enderror"
                                          maxlength="2000"
                                          placeholder="e.g. Total income, total expenses, category-wise expenditure breakdown, and closing balance">{{ old('description_en') }}</textarea>
                                @error('description_en')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="description_bn" class="form-label fw-bold font-bengali">
                                    {{ __('portal.documents.description_bn') }}
                                </label>
                                <textarea name="description_bn" id="description_bn" rows="3"
                                          class="form-control font-bengali @error('description_bn') is-invalid @enderror"
                                          maxlength="2000"
                                          placeholder="যেমন: মোট আয়, মোট ব্যয়, খাতভিত্তিক ব্যয় এবং সমাপনী স্থিতি">{{ old('description_bn') }}</textarea>
                                @error('description_bn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Publication Status --}}
                        <div class="mb-4">
                            <label for="publication_status" class="form-label fw-bold">
                                {{ __('portal.publication_status') }}
                            </label>
                            <select name="publication_status" id="publication_status" class="form-select @error('publication_status') is-invalid @enderror">
                                <option value="published" {{ old('publication_status', 'published') === 'published' ? 'selected' : '' }}>
                                    {{ __('portal.published') }} — ({{ __('portal.documents.publish_now') }})
                                </option>
                                <option value="unpublished" {{ old('publication_status') === 'unpublished' ? 'selected' : '' }}>
                                    {{ __('portal.unpublished') }} / {{ __('portal.draft') }}
                                </option>
                            </select>
                            <div class="form-text text-muted">
                                Published reports will appear on the Public Transparency Portal. Unpublished reports remain confidential.
                            </div>
                            @error('publication_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <a href="{{ route('portal.documents.index') }}" class="btn btn-outline-secondary">
                                {{ __('portal.actions.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary px-4" id="btnSubmit">
                                <i class="bi bi-cloud-arrow-up me-1"></i> {{ __('portal.actions.upload') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
