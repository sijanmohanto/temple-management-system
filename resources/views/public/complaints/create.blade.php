@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', __('portal.public.submit_complaint') . ' — ' . __('common.app_name'))

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
                <a href="{{ route('public.complaints.index') }}" class="text-decoration-none text-muted">
                    {{ __('portal.public.my_complaints') }}
                </a>
            </li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                {{ __('portal.public.submit_complaint') }}
            </li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background: linear-gradient(135deg, #4a154b 0%, #611f69 50%, #0d6efd 100%);">
                <div class="card-body p-4 p-md-5 text-white">
                    <span class="badge bg-light text-dark px-3 py-1 font-monospace mb-2">{{ $donor->person_code }}</span>
                    <h1 class="h3 fw-bold mb-1">{{ __('portal.public.submit_complaint') }}</h1>
                    <p class="text-white-50 mb-0 fs-6">
                        {{ __('portal.public.submit_complaint_subtitle') }}
                    </p>
                </div>
            </div>

            <!-- Financial Disclaimer Alert -->
            <div class="alert alert-info border-0 shadow-sm rounded-4 d-flex align-items-start gap-3 p-3 mb-4">
                <i class="bi bi-info-circle-fill fs-4 text-info flex-shrink-0 mt-1"></i>
                <div class="small">
                    <strong>{{ __('portal.complaints_module.title') }}:</strong>
                    {{ __('portal.public.complaint_notice') }}
                </div>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Form Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-5">
                <div class="card-body p-4 p-md-5">
                    <form action="{{ route('public.complaints.store') }}" method="POST" enctype="multipart/form-data" id="complaintForm">
                        @csrf

                        <!-- Devotee Info Readonly Preview -->
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <div class="row g-2 small">
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">{{ __('portal.public.devotee_name') }}:</span>
                                    <strong class="text-dark">{{ app()->getLocale() === 'bn' && !empty($donor->name_bn) ? $donor->name_bn : $donor->name_en }}</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">{{ __('portal.public.devotee_phone') }}:</span>
                                    <span class="font-monospace text-dark">{{ $donor->phone }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 1. Complaint Category -->
                        <div class="mb-4">
                            <label for="complaint_type" class="form-label fw-semibold text-dark">
                                {{ __('portal.public.complaint_type') }} <span class="text-danger">*</span>
                            </label>
                            <select name="complaint_type" id="complaint_type" class="form-select @error('complaint_type') is-invalid @enderror" required>
                                <option value="" disabled {{ old('complaint_type') ? '' : 'selected' }}>-- {{ __('portal.public.complaint_issue_type') }} --</option>
                                <option value="payment_not_updated" {{ old('complaint_type') === 'payment_not_updated' ? 'selected' : '' }}>
                                    {{ __('portal.complaint_categories.payment_not_updated') }}
                                </option>
                                <option value="wrong_paid_amount" {{ old('complaint_type') === 'wrong_paid_amount' ? 'selected' : '' }}>
                                    {{ __('portal.complaint_categories.wrong_paid_amount') }}
                                </option>
                                <option value="wrong_due_amount" {{ old('complaint_type') === 'wrong_due_amount' ? 'selected' : '' }}>
                                    {{ __('portal.complaint_categories.wrong_due_amount') }}
                                </option>
                                <option value="other" {{ old('complaint_type') === 'other' ? 'selected' : '' }}>
                                    {{ __('portal.complaint_categories.other') }}
                                </option>
                            </select>
                            @error('complaint_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 2. Related Payment (Optional) -->
                        <div class="mb-4">
                            <label for="payment_code" class="form-label fw-semibold text-dark">
                                {{ __('portal.public.related_payment') }} <small class="text-muted fw-normal">({{ __('portal.documents.replace_file_help') ? __('common.optional') : 'Optional' }})</small>
                            </label>
                            <select name="payment_code" id="payment_code" class="form-select font-monospace small @error('payment_code') is-invalid @enderror">
                                <option value="">-- {{ __('portal.public.no_related_payment') }} --</option>
                                @foreach($donorPayments as $p)
                                    @php
                                        $selected = old('payment_code', $preselectedPayment?->payment_code) === $p->payment_code;
                                    @endphp
                                    <option value="{{ $p->payment_code }}" {{ $selected ? 'selected' : '' }}>
                                        {{ $p->payment_code }} — ৳{{ number_format($p->amount, 2) }} ({{ strtoupper($p->payment_method) }}) — {{ $p->payment_date ? $p->payment_date->format('d M Y') : '' }} [{{ strtoupper($p->status) }}]
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small text-muted">
                                {{ __('portal.public.select_related_payment') }}
                            </div>
                            @error('payment_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 3. Related Event (Optional) -->
                        <div class="mb-4">
                            <label for="event_id" class="form-label fw-semibold text-dark">
                                {{ __('portal.public.related_event') }} <small class="text-muted fw-normal">({{ __('common.optional') }})</small>
                            </label>
                            <select name="event_id" id="event_id" class="form-select @error('event_id') is-invalid @enderror">
                                <option value="">-- {{ __('portal.public.no_related_event') }} --</option>
                                @foreach($events as $ev)
                                    @php
                                        $evSelected = old('event_id', $preselectedPayment?->event_id) == $ev->id;
                                    @endphp
                                    <option value="{{ $ev->id }}" {{ $evSelected ? 'selected' : '' }}>
                                        {{ app()->getLocale() === 'bn' && !empty($ev->name_bn) ? $ev->name_bn : $ev->name_en }} ({{ $ev->event_year }})
                                    </option>
                                @endforeach
                            </select>
                            @error('event_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 4. Description -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="description" class="form-label fw-semibold text-dark mb-0">
                                    {{ __('portal.public.complaint_description') }} <span class="text-danger">*</span>
                                </label>
                                <span class="text-muted small font-monospace" id="charCounter">0 / 2000</span>
                            </div>
                            <textarea name="description" id="description" rows="5"
                                      class="form-control @error('description') is-invalid @enderror"
                                      minlength="5" maxlength="2000"
                                      placeholder="{{ __('portal.public.complaint_description_help') }}"
                                      required>{{ old('description') }}</textarea>
                            <div class="form-text small text-muted">
                                {{ __('portal.public.complaint_description_help') }}
                            </div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 5. Attachment (Optional) -->
                        <div class="mb-4">
                            <label for="attachment" class="form-label fw-semibold text-dark">
                                {{ __('portal.public.complaint_attachment') }}
                            </label>
                            <input type="file" name="attachment" id="attachment"
                                   class="form-control @error('attachment') is-invalid @enderror"
                                   accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text small text-muted">
                                {{ __('portal.public.payment_proof_help') }}
                            </div>
                            @error('attachment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 pt-3 border-top">
                            <a href="{{ route('public.complaints.index') }}" class="btn btn-outline-secondary w-100 w-sm-auto">
                                <i class="bi bi-arrow-left me-1"></i> {{ __('portal.public.back_to_complaints') }}
                            </a>
                            <button type="submit" class="btn btn-primary px-4 w-100 w-sm-auto" id="submitBtn">
                                <span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner" role="status" aria-hidden="true"></span>
                                <i class="bi bi-send me-1" id="submitIcon"></i>
                                <span id="submitText">{{ __('portal.public.complaint_submit_btn') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const descArea = document.getElementById('description');
    const charCounter = document.getElementById('charCounter');
    const form = document.getElementById('complaintForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitSpinner = document.getElementById('submitSpinner');
    const submitIcon = document.getElementById('submitIcon');
    const submitText = document.getElementById('submitText');

    function updateCount() {
        if (descArea && charCounter) {
            const len = descArea.value.length;
            charCounter.textContent = len + ' / 2000';
            if (len > 2000) {
                charCounter.classList.add('text-danger');
            } else {
                charCounter.classList.remove('text-danger');
            }
        }
    }

    if (descArea) {
        descArea.addEventListener('input', updateCount);
        updateCount();
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (form.checkValidity()) {
                submitBtn.disabled = true;
                submitSpinner.classList.remove('d-none');
                submitIcon.classList.add('d-none');
                submitText.textContent = "{{ __('portal.public.submitting') }}";
            }
        });
    }
});
</script>
@endpush
@endsection
