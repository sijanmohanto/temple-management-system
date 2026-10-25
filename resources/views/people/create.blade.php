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

    <form method="POST" action="{{ route('people.store') }}" id="personCreateForm">
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
                                <label class="form-label fw-semibold">{{ __('people.phone') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" 
                                           value="{{ old('phone') }}" placeholder="+88017XXXXXXXX">
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
                                            {{ $family->family_name }} ({{ $family->family_code }})
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
    });
</script>
@endpush
