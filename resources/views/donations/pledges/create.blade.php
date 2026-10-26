@extends('layouts.app')

@section('title', __('donations.add_pledge'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('pledges.index') }}" class="text-decoration-none">{{ __('donations.pledges') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('donations.add_pledge') }}</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-0 text-dark">{{ __('donations.add_pledge') }}</h1>
        </div>

        <div>
            <a href="{{ route('pledges.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('pledges.store') }}" method="POST" id="createPledgeForm">
                @csrf

                <!-- Contributor / Payer Type Selector -->
                <div class="border-bottom pb-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-check me-2 text-primary"></i>{{ __('donations.payer') }}</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold required">{{ __('donations.select_payer_type') }}</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payer_type" id="typePerson" value="person" {{ old('family_id', $selectedFamily?->id) ? '' : 'checked' }}>
                                <label class="form-check-label fw-semibold" for="typePerson">
                                    <i class="bi bi-person me-1"></i> {{ __('donations.individual_devotee') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payer_type" id="typeFamily" value="family" {{ old('family_id', $selectedFamily?->id) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="typeFamily">
                                    <i class="bi bi-house-heart me-1"></i> {{ __('donations.family_unit') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Individual Devotee Selection -->
                    <div class="row g-3" id="personSelectContainer">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold required">{{ __('people.person') }}</label>
                            <select name="person_id" id="personSelect" class="form-select select2" data-ajax--url="{{ route('people.search') }}" data-placeholder="Search devotee by name, code or phone...">
                                @if(old('person_id') || $selectedPerson)
                                    @php
                                        $p = $selectedPerson ?? \App\Models\Person::find(old('person_id'));
                                    @endphp
                                    @if($p)
                                        <option value="{{ $p->id }}" selected>{{ $p->name_en }} ({{ $p->person_code }}) - {{ $p->phone }}</option>
                                    @endif
                                @endif
                            </select>
                            @error('person_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Family Unit Selection -->
                    <div class="row g-3" id="familySelectContainer" style="display: none;">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold required">{{ __('families.family') }}</label>
                            <select name="family_id" id="familySelect" class="form-select select2" data-ajax--url="{{ route('families.search') }}" data-placeholder="Search family by name or code...">
                                @if(old('family_id') || $selectedFamily)
                                    @php
                                        $f = $selectedFamily ?? \App\Models\Family::find(old('family_id'));
                                    @endphp
                                    @if($f)
                                        <option value="{{ $f->id }}" selected>{{ $f->family_name }} ({{ $f->family_code }})</option>
                                    @endif
                                @endif
                            </select>
                            @error('family_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Event & Obligation Details -->
                <div class="border-bottom pb-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-calendar-event me-2 text-primary"></i>{{ __('events.event') }} &amp; {{ __('donations.pledges') }}</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">{{ __('events.event') }}</label>
                            <select name="event_id" id="eventSelect" class="form-select select2 @error('event_id') is-invalid @enderror" required data-placeholder="Select Event">
                                <option value="">Select Event</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}" data-year="{{ $event->event_year }}" {{ old('event_id', $selectedEventId) == $event->id ? 'selected' : '' }}>
                                        {{ $event->name }} ({{ $event->event_year }})
                                    </option>
                                @endforeach
                            </select>
                            @error('event_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold required">{{ __('donations.payment_year') }}</label>
                            <input type="number" name="pledge_year" id="pledgeYear" class="form-control @error('pledge_year') is-invalid @enderror" value="{{ old('pledge_year', date('Y')) }}" required min="2000" max="2100">
                            @error('pledge_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold required">{{ __('donations.pledged_on') }}</label>
                            <input type="date" name="pledged_on" class="form-control @error('pledged_on') is-invalid @enderror" value="{{ old('pledged_on', date('Y-m-d')) }}" required>
                            @error('pledged_on')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">{{ __('donations.pledged_amount') }} (৳)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">৳</span>
                                <input type="number" step="0.01" min="1" name="pledged_amount" class="form-control @error('pledged_amount') is-invalid @enderror" value="{{ old('pledged_amount') }}" required placeholder="0.00">
                            </div>
                            @error('pledged_amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('donations.purpose') }}</label>
                            <input type="text" name="purpose" class="form-control @error('purpose') is-invalid @enderror" value="{{ old('purpose') }}" placeholder="e.g. Mahaprasad Bhog Seva, Cultural Program">
                            @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('donations.notes') }}</label>
                            <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional internal notes or remarks...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <a href="{{ route('pledges.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        {{ __('common.cancel') }}
                    </a>
                    <button type="submit" id="savePledgeBtn" class="btn btn-primary rounded-pill px-4" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
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
        function togglePayerContainers() {
            if ($('#typePerson').is(':checked')) {
                $('#personSelectContainer').show();
                $('#familySelectContainer').hide();
                $('#familySelect').val(null).trigger('change');
            } else {
                $('#personSelectContainer').hide();
                $('#familySelectContainer').show();
                $('#personSelect').val(null).trigger('change');
            }
        }

        $('input[name="payer_type"]').on('change', togglePayerContainers);
        togglePayerContainers();

        // Event change auto-sets pledge year
        $('#eventSelect').on('change', function() {
            var year = $(this).find(':selected').data('year');
            if (year) {
                $('#pledgeYear').val(year);
            }
        });

        $('#createPledgeForm').on('submit', function() {
            TempleUI.button.loading('#savePledgeBtn');
        });
    });
</script>
@endpush
