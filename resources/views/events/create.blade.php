@extends('layouts.app')

@section('title', __('events.add_event'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('events.index') }}" class="text-decoration-none">{{ __('events.events') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('events.add_event') }}</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-0 text-dark">{{ __('events.add_event') }}</h1>
        </div>

        <div>
            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('events.store') }}" method="POST" id="createEventForm">
                @csrf

                <!-- Basic Information -->
                <div class="border-bottom pb-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>{{ __('common.details') }}</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">{{ __('events.name_en') }}</label>
                            <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror" value="{{ old('name_en') }}" required placeholder="e.g. Sharadiya Durga Puja 2026">
                            @error('name_en')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('events.name_bn') }}</label>
                            <input type="text" name="name_bn" class="form-control @error('name_bn') is-invalid @enderror" value="{{ old('name_bn') }}" placeholder="যেমন: শারদীয় দুর্গোৎসব ১৪৩৩">
                            @error('name_bn')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">{{ __('events.category') }}</label>
                            <select name="event_category_id" class="form-select select2 @error('event_category_id') is-invalid @enderror" required data-placeholder="{{ __('events.select_category') }}">
                                <option value="">{{ __('events.select_category') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('event_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('event_category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold required">{{ __('events.event_year') }}</label>
                            <input type="number" name="event_year" class="form-control @error('event_year') is-invalid @enderror" value="{{ old('event_year', $currentYear) }}" min="2000" max="2100" required>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ __('events.event_year_help') }}</small>
                            @error('event_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">{{ __('events.status') }}</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>{{ __('events.status_draft') }}</option>
                                <option value="upcoming" {{ old('status') === 'upcoming' ? 'selected' : '' }}>{{ __('events.status_upcoming') }}</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>{{ __('events.status_active') }}</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('events.venue') }}</label>
                            <input type="text" name="venue" class="form-control @error('venue') is-invalid @enderror" value="{{ old('venue') }}" placeholder="e.g. Temple Natmandir & Courtyard">
                            @error('venue')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Schedule & Dates -->
                <div class="border-bottom pb-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-calendar-range me-2 text-primary"></i>{{ __('events.event_dates') }}</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">{{ __('events.start_date') }}</label>
                            <input type="date" name="start_date" id="eventStartDate" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">{{ __('events.end_date') }}</label>
                            <input type="date" name="end_date" id="eventEndDate" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Financial / Collection Window -->
                <div class="border-bottom pb-4 mb-4">
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-cash-stack me-2 text-primary"></i>{{ __('events.collection_period') }}</h5>
                    </div>
                    <p class="text-muted small mb-3">{{ __('events.collection_dates_help') }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('events.collection_start_date') }}</label>
                            <input type="date" name="collection_start_date" id="collStartDate" class="form-control @error('collection_start_date') is-invalid @enderror" value="{{ old('collection_start_date') }}">
                            @error('collection_start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('events.collection_end_date') }}</label>
                            <input type="date" name="collection_end_date" id="collEndDate" class="form-control @error('collection_end_date') is-invalid @enderror" value="{{ old('collection_end_date') }}">
                            @error('collection_end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Descriptions -->
                <div class="mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-card-text me-2 text-primary"></i>{{ __('events.description') }}</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('events.description_en') }}</label>
                            <textarea name="description_en" rows="4" class="form-control @error('description_en') is-invalid @enderror" placeholder="Rituals schedule, major attractions, deity details...">{{ old('description_en') }}</textarea>
                            @error('description_en')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('events.description_bn') }}</label>
                            <textarea name="description_bn" rows="4" class="form-control @error('description_bn') is-invalid @enderror" placeholder="পূজার নির্ঘণ্ট, অঞ্জলি সময়সূচি ও ভোগ বিবরণী...">{{ old('description_bn') }}</textarea>
                            @error('description_bn')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('events.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        {{ __('common.cancel') }}
                    </a>
                    <button type="submit" id="saveEventBtn" class="btn btn-primary rounded-pill px-4" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#createEventForm').on('submit', function() {
            TempleUI.button.loading('#saveEventBtn');
        });
    });
</script>
@endpush
