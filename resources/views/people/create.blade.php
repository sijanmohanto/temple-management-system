@extends('layouts.app')

@section('title', __('people.add_person'))

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('people.add_person') }}</h1>
            <p class="text-muted small mb-0">{{ __('people.people_directory') }}</p>
        </div>
        <a href="{{ route('people.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
        </a>
    </div>

    <!-- Duplicate Detection Warning Alert (Server-side validation) -->
    @if(session('warning_duplicates'))
        <div class="alert alert-warning border-warning shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-2 text-warning"></i>
                <div class="w-100">
                    <h5 class="alert-heading fw-bold mb-2">{{ __('people.duplicate_warning_title') }}</h5>
                    <p class="mb-3">{{ __('people.duplicate_detected_warning') }}</p>

                    <div class="list-group mb-3">
                        @foreach(session('warning_duplicates') as $match)
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <span class="badge bg-dark font-monospace me-2">{{ $match['person']->person_code }}</span>
                                    <strong class="text-dark">{{ $match['person']->name_en }}</strong>
                                    @if($match['person']->name_bn)
                                        <span class="text-muted">({{ $match['person']->name_bn }})</span>
                                    @endif
                                    <div class="small text-danger mt-1">
                                        @foreach($match['reasons'] as $reason)
                                            <div><i class="bi bi-dot"></i> {{ $reason }}</div>
                                        @endforeach
                                    </div>
                                </div>
                                <a href="{{ route('people.show', $match['person']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('people.duplicate_view_existing') }}
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="confirm_duplicate" value="1" id="confirmDuplicateCheckbox" form="personCreateForm">
                        <label class="form-check-label fw-bold text-dark" for="confirmDuplicateCheckbox">
                            {{ __('people.confirm_duplicate_checkbox') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('people.store') }}" id="personCreateForm" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            <!-- Left Column: Core & Religious Details -->
            <div class="col-lg-8">
                <!-- 1. Basic Identity -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-person-badge text-primary me-2"></i> 1. Basic Identity & Contact
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.name_en') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name_en" id="name_en" class="form-control @error('name_en') is-invalid @enderror" 
                                       value="{{ old('name_en') }}" required placeholder="e.g. Subrata Majumder">
                                @error('name_en')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.name_bn') }}</label>
                                <input type="text" name="name_bn" id="name_bn" class="form-control @error('name_bn') is-invalid @enderror" 
                                       value="{{ old('name_bn') }}" placeholder="যেমনঃ সুব্রত মজুমদার">
                                @error('name_bn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.phone') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" 
                                           value="{{ old('phone') }}" required placeholder="+88017XXXXXXXX">
                                </div>
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.email') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" 
                                           value="{{ old('email') }}" placeholder="devotee@example.com">
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">{{ __('people.date_of_birth') }}</label>
                                <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" 
                                       value="{{ old('date_of_birth') }}">
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">{{ __('people.gender') }}</label>
                                <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                    <option value="">{{ __('common.none') }}</option>
                                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>{{ __('people.gender_male') }}</option>
                                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('people.gender_female') }}</option>
                                    <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>{{ __('people.gender_other') }}</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">{{ __('people.blood_group') }}</label>
                                <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                                    <option value="">{{ __('common.none') }}</option>
                                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                        <option value="{{ $bg }}" {{ old('blood_group') === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                    @endforeach
                                </select>
                                @error('blood_group')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Real-time Duplicate Alert Container (Client-side) -->
                        <div id="liveDuplicateAlert" class="alert alert-warning mt-3 d-none mb-0">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-info-circle-fill"></i>
                                <strong id="liveDuplicateText"></strong>
                            </div>
                            <div id="liveDuplicateMatches" class="small"></div>
                        </div>
                    </div>
                </div>

                <!-- 2. Family & Religious Lineage -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-flower1 text-warning me-2"></i> 2. Family & Religious Lineage
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.gotra') }}</label>
                                <input type="text" name="gotra" class="form-control @error('gotra') is-invalid @enderror" 
                                       value="{{ old('gotra') }}" placeholder="e.g. Shandilya, Kashyapa, Bharadwaja">
                                @error('gotra')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.father_name') }}</label>
                                <input type="text" name="father_name" class="form-control @error('father_name') is-invalid @enderror" 
                                       value="{{ old('father_name') }}">
                                @error('father_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.mother_name') }}</label>
                                <input type="text" name="mother_name" class="form-control @error('mother_name') is-invalid @enderror" 
                                       value="{{ old('mother_name') }}">
                                @error('mother_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.spouse_name') }}</label>
                                <input type="text" name="spouse_name" class="form-control @error('spouse_name') is-invalid @enderror" 
                                       value="{{ old('spouse_name') }}">
                                @error('spouse_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('families.family') }}</label>
                                <select name="family_id" class="form-select select2 @error('family_id') is-invalid @enderror" data-placeholder="{{ __('people.no_family_assigned') }}">
                                    <option value="">{{ __('people.no_family_assigned') }}</option>
                                    @foreach($families as $family)
                                        <option value="{{ $family->id }}" {{ old('family_id') == $family->id ? 'selected' : '' }}>
                                            {{ $family->family_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('family_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.family_relationship') }}</label>
                                <select name="family_relationship" class="form-select @error('family_relationship') is-invalid @enderror">
                                    <option value="">{{ __('common.none') }}</option>
                                    <option value="head" {{ old('family_relationship') === 'head' ? 'selected' : '' }}>{{ __('people.relationship_head') }}</option>
                                    <option value="spouse" {{ old('family_relationship') === 'spouse' ? 'selected' : '' }}>{{ __('people.relationship_spouse') }}</option>
                                    <option value="son" {{ old('family_relationship') === 'son' ? 'selected' : '' }}>{{ __('people.relationship_son') }}</option>
                                    <option value="daughter" {{ old('family_relationship') === 'daughter' ? 'selected' : '' }}>{{ __('people.relationship_daughter') }}</option>
                                    <option value="father" {{ old('family_relationship') === 'father' ? 'selected' : '' }}>{{ __('people.relationship_father') }}</option>
                                    <option value="mother" {{ old('family_relationship') === 'mother' ? 'selected' : '' }}>{{ __('people.relationship_mother') }}</option>
                                    <option value="brother" {{ old('family_relationship') === 'brother' ? 'selected' : '' }}>{{ __('people.relationship_brother') }}</option>
                                    <option value="sister" {{ old('family_relationship') === 'sister' ? 'selected' : '' }}>{{ __('people.relationship_sister') }}</option>
                                    <option value="other" {{ old('family_relationship') === 'other' ? 'selected' : '' }}>{{ __('people.relationship_other') }}</option>
                                </select>
                                @error('family_relationship')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Address & Occupation -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-geo-alt text-success me-2"></i> 3. Address & Emergency
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('people.occupation') }}</label>
                                <input type="text" name="occupation" class="form-control" value="{{ old('occupation') }}" placeholder="e.g. Teacher, Doctor, Business">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.present_address') }}</label>
                                <textarea name="present_address" class="form-control" rows="2">{{ old('present_address') }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.permanent_address') }}</label>
                                <textarea name="permanent_address" class="form-control" rows="2">{{ old('permanent_address') }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.emergency_contact_name') }}</label>
                                <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('people.emergency_contact_phone') }}</label>
                                <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Classifications, Status, Notes & Submission -->
            <div class="col-lg-4">
                <!-- Devotee Photo Upload Card (Spacious & Reactive) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-camera-fill text-primary me-2"></i> {{ __('people.photo') }}
                        </h5>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">{{ __('common.optional') ?? 'Optional' }}</span>
                    </div>
                    <div class="card-body p-4">
                        <input type="file" name="photo" id="profilePhotoInput" accept="image/jpeg,image/png,image/webp" class="d-none">

                        <!-- Spacious Dropzone / Upload Area -->
                        <div id="photoDropzone" class="border border-2 border-dashed rounded-4 p-4 text-center position-relative transition-all"
                             style="background-color: #f8fafc; border-color: #cbd5e1 !important; min-height: 220px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            
                            <!-- Placeholder Area (Visible when no photo selected) -->
                            <div id="photoPlaceholderArea" class="w-100 py-3">
                                <div class="avatar-placeholder rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm overflow-hidden border border-2 border-white"
                                     style="width: 82px; height: 82px; background-color: var(--temple-saffron-light);">
                                    <img id="placeholderGenderAvatar" src="{{ asset('assets/images/avatars/avatar-default.svg') }}" alt="Devotee Avatar" class="w-100 h-100 object-fit-cover">
                                </div>
                                <h6 class="fw-bold text-dark mb-1">{{ __('people.upload_photo') }}</h6>
                                <p class="text-muted small mb-3 px-2" style="font-size: 0.78rem;">
                                    {{ __('people.photo_hint') }}
                                </p>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none">
                                    <i class="bi bi-folder2-open me-1"></i> {{ __('people.upload_photo') }}
                                </button>
                            </div>

                            <!-- Preview Area (Dynamic, replaces previous selection seamlessly) -->
                            <div id="photoPreviewContainer" class="d-none w-100">
                                <div class="position-relative mx-auto mb-2" style="max-width: 200px;">
                                    <img id="photoPreviewImg" src="#" alt="Preview" class="img-fluid rounded-4 shadow-sm border"
                                         style="max-height: 210px; width: 100%; object-fit: cover;">
                                </div>
                                <div class="small fw-semibold text-truncate text-muted mb-2 px-2" id="photoFileName"></div>
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" id="btnChangePhoto" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="bi bi-arrow-repeat me-1"></i> {{ __('people.change_photo') }}
                                    </button>
                                    <button type="button" id="btnRemovePhoto" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                        <i class="bi bi-trash me-1"></i> {{ __('people.remove_photo') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        @error('photo')
                            <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Classifications Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-tags text-primary me-2"></i> {{ __('people.classifications') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="small text-muted mb-3">Devotees can belong to multiple classifications simultaneously.</p>
                        
                        @foreach($classifications as $classification)
                            <div class="form-check p-3 rounded-3 mb-2 bg-light border">
                                <input class="form-check-input" type="checkbox" name="classifications[]" 
                                       value="{{ $classification->id }}" id="class_{{ $classification->slug }}"
                                       {{ in_array($classification->id, old('classifications', [])) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-dark d-block" for="class_{{ $classification->slug }}">
                                    {{ $classification->name }}
                                </label>
                                @if($classification->description)
                                    <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">
                                        {{ $classification->description }}
                                    </small>
                                @endif
                            </div>
                        @endforeach
                        @error('classifications')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Status & Notes Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-shield-lock text-danger me-2"></i> Status & Administrative Notes
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('people.status') }} <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>{{ __('common.status_active') }}</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>{{ __('common.status_inactive') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('people.public_notes') }}</label>
                            <textarea name="public_notes" class="form-control" rows="2" placeholder="General service notes...">{{ old('public_notes') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-danger">
                                <i class="bi bi-lock-fill me-1"></i> {{ __('people.private_notes') }}
                            </label>
                            <textarea name="private_notes" class="form-control border-danger border-opacity-50" rows="2" placeholder="Confidential admin remarks...">{{ old('private_notes') }}</textarea>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                {{ __('people.private_notes_notice') }}
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Send Welcome Mail Toggle Card (Appears only when valid email is entered) -->
                <div id="emailCredentialsCard" class="card border-0 shadow-sm rounded-4 mb-4 d-none" style="border-left: 4px solid var(--temple-maroon) !important; background: #fffcf8;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="pe-2">
                                <label class="form-check-label fw-bold text-dark small d-block mb-1" for="sendCredentialsEmail" style="cursor: pointer;">
                                    <i class="bi bi-envelope-check-fill text-primary me-1"></i> {{ __('people.send_credentials_email') }}
                                </label>
                                <div class="text-muted small" style="font-size: 0.76rem; line-height: 1.35;">
                                    {{ __('people.send_credentials_email_hint') }}
                                </div>
                                <div class="small text-primary fw-semibold mt-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-send-fill me-1 text-success"></i> <span id="validEmailTargetDisplay"></span>
                                </div>
                            </div>
                            <div class="form-check form-switch ms-3 mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="sendCredentialsEmail" name="send_credentials_email" value="1" {{ old('send_credentials_email', '1') == '1' ? 'checked' : '' }} style="cursor: pointer; width: 2.6em; height: 1.4em;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submission Action Card -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold shadow-sm mb-2"
                                style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                            <i class="bi bi-check-circle me-1"></i> {{ __('common.save') }}
                        </button>
                        <a href="{{ route('people.index') }}" class="btn btn-outline-secondary w-100 rounded-pill py-2">
                            {{ __('common.cancel') }}
                        </a>
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
        let debounceTimer;

        function checkDuplicates() {
            const phone = $('#phone').val().trim();
            const email = $('#email').val().trim();
            const nameEn = $('#name_en').val().trim();

            if (!phone && !email && !nameEn) {
                $('#liveDuplicateAlert').addClass('d-none');
                return;
            }

            $.ajax({
                url: "{{ route('people.check-duplicates') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    phone: phone,
                    email: email,
                    name_en: nameEn
                },
                success: function(response) {
                    if (response.has_duplicates && response.matches.length > 0) {
                        $('#liveDuplicateAlert').removeClass('d-none');
                        $('#liveDuplicateText').text("Notice: " + response.matches.length + " matching record(s) found in registry.");
                        let html = '<ul class="mb-0 ps-3 mt-1">';
                        response.matches.forEach(function(item) {
                            html += '<li><strong>' + item.name_en + '</strong> (' + item.person_code + ') — ' + item.reasons.join(', ') + '</li>';
                        });
                        html += '</ul>';
                        $('#liveDuplicateMatches').html(html);
                    } else {
                        $('#liveDuplicateAlert').addClass('d-none');
                    }
                }
            });
        }

        $('#phone, #email, #name_en').on('keyup change', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(checkDuplicates, 500);
        });

        // Real-time Email JS Validation & Send Credentials Card Toggle
        function validateEmailRealtime() {
            const emailVal = $('#email').val().trim();
            // Standard RFC regex ensuring name@domain.tld with at least 2 char TLD
            const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;
            const isValid = emailRegex.test(emailVal);
            const card = $('#emailCredentialsCard');

            if (isValid) {
                $('#validEmailTargetDisplay').text(emailVal);
                if (card.hasClass('d-none')) {
                    card.removeClass('d-none').hide().slideDown(250);
                    $('#sendCredentialsEmail').prop('checked', true);
                }
            } else {
                if (!card.hasClass('d-none')) {
                    card.slideUp(200, function() {
                        $(this).addClass('d-none');
                    });
                    $('#sendCredentialsEmail').prop('checked', false);
                }
            }
        }

        $('#email').on('input propertychange keyup paste change', validateEmailRealtime);
        validateEmailRealtime();

        // Reactive Gender Avatar Selection (When no custom photo uploaded)
        $('select[name="gender"]').on('change', function() {
            const gender = $(this).val();
            let src = "{{ asset('assets/images/avatars/avatar-default.svg') }}";
            if (gender === 'male') {
                src = "{{ asset('assets/images/avatars/avatar-male.svg') }}";
            } else if (gender === 'female') {
                src = "{{ asset('assets/images/avatars/avatar-female.svg') }}";
            }
            $('#placeholderGenderAvatar').attr('src', src);
        });

        // Photo Upload & Dynamic Preview Handler
        const photoInput = document.getElementById('profilePhotoInput');
        const photoDropzone = document.getElementById('photoDropzone');
        const photoPlaceholderArea = document.getElementById('photoPlaceholderArea');
        const photoPreviewContainer = document.getElementById('photoPreviewContainer');
        const photoPreviewImg = document.getElementById('photoPreviewImg');
        const photoFileName = document.getElementById('photoFileName');
        let currentObjectURL = null;

        function handleFileSelection(file) {
            if (!file) return;

            if (!file.type.match('image.*')) {
                alert('Please select a valid image file (JPG, PNG, WebP).');
                return;
            }

            if (file.size > 3 * 1024 * 1024) {
                alert('The selected image exceeds the 3MB limit.');
                return;
            }

            // Revoke previous object URL if any
            if (currentObjectURL) {
                URL.revokeObjectURL(currentObjectURL);
            }

            currentObjectURL = URL.createObjectURL(file);
            photoPreviewImg.src = currentObjectURL;
            photoFileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';

            photoPlaceholderArea.classList.add('d-none');
            photoPreviewContainer.classList.remove('d-none');
        }

        function clearPhoto() {
            photoInput.value = '';
            if (currentObjectURL) {
                URL.revokeObjectURL(currentObjectURL);
                currentObjectURL = null;
            }
            photoPreviewImg.src = '#';
            photoFileName.textContent = '';
            photoPreviewContainer.classList.add('d-none');
            photoPlaceholderArea.classList.remove('d-none');
        }

        $('#photoDropzone').on('click', function(e) {
            if (e.target.closest('#btnRemovePhoto') || e.target.closest('#btnChangePhoto')) {
                return;
            }
            photoInput.click();
        });

        $('#btnChangePhoto').on('click', function(e) {
            e.stopPropagation();
            photoInput.click();
        });

        $('#btnRemovePhoto').on('click', function(e) {
            e.stopPropagation();
            clearPhoto();
        });

        $(photoInput).on('change', function() {
            if (this.files && this.files[0]) {
                handleFileSelection(this.files[0]);
            }
        });

        // Drag & drop support
        $(photoDropzone).on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('border-primary bg-primary bg-opacity-10');
        });

        $(photoDropzone).on('dragleave dragend drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('border-primary bg-primary bg-opacity-10');
        });

        $(photoDropzone).on('drop', function(e) {
            const dt = e.originalEvent.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                photoInput.files = dt.files;
                handleFileSelection(dt.files[0]);
            }
        });
    });
</script>
@endpush
