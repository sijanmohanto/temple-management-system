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

                        {{-- Event Selection: Existing vs New Historical Event --}}
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label class="form-label fw-bold mb-2">{{ __('portal.documents.event_source_selection') }} <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="event_mode" id="event_mode_existing" value="existing" {{ old('event_mode', 'existing') === 'existing' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="event_mode_existing">
                                        <i class="bi bi-calendar3 me-1 text-primary"></i> {{ __('portal.documents.select_existing_event') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="event_mode" id="event_mode_new" value="new" {{ old('event_mode') === 'new' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="event_mode_new">
                                        <i class="bi bi-plus-circle me-1 text-success"></i> {{ __('portal.documents.create_new_historical_event') }}
                                    </label>
                                </div>
                            </div>

                            {{-- Container for Existing Event Selection --}}
                            <div id="existing_event_container" class="{{ old('event_mode', 'existing') === 'new' ? 'd-none' : '' }}">
                                <label for="event_id" class="form-label small fw-bold">
                                    {{ __('portal.documents.event') }} <span class="text-danger">*</span>
                                </label>
                                <select name="event_id" id="event_id" class="form-select select2-enable @error('event_id') is-invalid @enderror" {{ old('event_mode', 'existing') === 'existing' ? 'required' : '' }}>
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

                            {{-- Container for New Historical Event Entry (Not in Database) --}}
                            <div id="new_event_container" class="{{ old('event_mode', 'existing') === 'new' ? '' : 'd-none' }}">
                                <div class="alert alert-info py-2 px-3 small border-0 mb-3">
                                    <i class="bi bi-info-circle me-1"></i> {{ __('portal.documents.new_historical_event_help') }}
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="new_event_name_en" class="form-label small fw-bold">
                                            {{ __('portal.documents.historical_event_name_en') }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="new_event_name_en" id="new_event_name_en" class="form-control form-control-sm @error('new_event_name_en') is-invalid @enderror" value="{{ old('new_event_name_en') }}" placeholder="e.g. Durga Puja 2018">
                                        @error('new_event_name_en')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="new_event_year" class="form-label small fw-bold">
                                            {{ __('portal.documents.event_year') }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="new_event_year" id="new_event_year" class="form-control form-control-sm @error('new_event_year') is-invalid @enderror" value="{{ old('new_event_year', date('Y') - 1) }}" min="1900" max="2100">
                                        @error('new_event_year')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="new_event_name_bn" class="form-label small font-bengali">
                                            {{ __('portal.documents.historical_event_name_bn') }}
                                        </label>
                                        <input type="text" name="new_event_name_bn" id="new_event_name_bn" class="form-control form-control-sm font-bengali @error('new_event_name_bn') is-invalid @enderror" value="{{ old('new_event_name_bn') }}" placeholder="যেমন: শারদীয় দুর্গোৎসব ২০১৮">
                                        @error('new_event_name_bn')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="new_event_category_id" class="form-label small fw-bold">
                                            {{ __('portal.documents.event_category') }} <span class="text-danger">*</span>
                                        </label>
                                        <select name="new_event_category_id" id="new_event_category_id" class="form-select form-select-sm @error('new_event_category_id') is-invalid @enderror">
                                            <option value="">-- {{ __('common.select') }} --</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" {{ old('new_event_category_id') == $cat->id ? 'selected' : '' }}>
                                                    {{ $cat->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('new_event_category_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="new_event_venue" class="form-label small text-muted">
                                            {{ __('portal.public.venue') }} ({{ __('common.optional') }})
                                        </label>
                                        <input type="text" name="new_event_venue" id="new_event_venue" class="form-control form-control-sm" value="{{ old('new_event_venue') }}" placeholder="e.g. Main Temple Complex">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="new_event_description_en" class="form-label small text-muted">
                                            {{ __('common.description') }} ({{ __('common.optional') }})
                                        </label>
                                        <input type="text" name="new_event_description_en" id="new_event_description_en" class="form-control form-control-sm" value="{{ old('new_event_description_en') }}" placeholder="e.g. Historical celebration archive">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Document Type --}}
                        <div class="mb-3">
                            <label for="document_type" class="form-label fw-bold">
                                {{ __('portal.documents.type') }} <span class="text-danger">*</span>
                            </label>
                            <select name="document_type" id="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                                <option value="">-- {{ __('common.select') }} --</option>
                                <option value="financial_report" {{ old('document_type', 'financial_report') === 'financial_report' ? 'selected' : '' }}>
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

                        {{-- Report Category & Financial Year --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="report_category" class="form-label fw-bold">
                                    {{ __('portal.documents.report_category') }}
                                </label>
                                <select name="report_category" id="report_category" class="form-select @error('report_category') is-invalid @enderror">
                                    <option value="">-- {{ __('common.select') }} --</option>
                                    @foreach(\App\Models\EventDocument::REPORT_CATEGORIES as $cat)
                                        <option value="{{ $cat }}" {{ old('report_category') === $cat ? 'selected' : '' }}>
                                            {{ __('portal.report_categories.' . $cat) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('report_category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="financial_year" class="form-label fw-bold">
                                    {{ __('portal.documents.financial_year') }}
                                </label>
                                <input type="number" name="financial_year" id="financial_year"
                                       class="form-control @error('financial_year') is-invalid @enderror"
                                       value="{{ old('financial_year') }}" min="2000" max="2100"
                                       placeholder="{{ date('Y') }}">
                                @error('financial_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
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
                                   accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.csv" required>
                            <div class="form-text text-muted">
                                {{ __('common.allowed_types') }}: PDF, JPG, PNG, Excel (XLSX, XLS), CSV (Max: 10MB)
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="event_mode"]');
    const existingContainer = document.getElementById('existing_event_container');
    const newContainer = document.getElementById('new_event_container');
    const eventSelect = document.getElementById('event_id');

    radios.forEach(radio => {
        radio.addEventListener('change', function () {
            const isNew = this.value === 'new';
            if (existingContainer) existingContainer.classList.toggle('d-none', isNew);
            if (newContainer) newContainer.classList.toggle('d-none', !isNew);

            if (eventSelect) {
                if (isNew) {
                    eventSelect.removeAttribute('required');
                } else {
                    eventSelect.setAttribute('required', 'required');
                }
            }
        });
    });
});
</script>
@endpush
