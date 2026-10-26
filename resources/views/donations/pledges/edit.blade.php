@extends('layouts.app')

@section('title', __('donations.edit_pledge'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('pledges.index') }}" class="text-decoration-none">{{ __('donations.pledges') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pledges.show', $pledge) }}" class="text-decoration-none">{{ $pledge->pledge_code }}</a></li>
                    <li class="breadcrumb-item active">{{ __('donations.edit_pledge') }}</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-0 text-dark">{{ __('donations.edit_pledge') }} — <span class="text-primary font-monospace">{{ $pledge->pledge_code }}</span></h1>
        </div>

        <div>
            <a href="{{ route('pledges.show', $pledge) }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
            <!-- Payer & Event Readonly Summary -->
            <div class="p-3 bg-light rounded-3 mb-4 d-flex flex-wrap gap-4 align-items-center">
                <div>
                    <div class="small text-muted">{{ __('donations.payer') }}:</div>
                    <div class="fw-bold text-dark">{{ $pledge->owner_name }}</div>
                </div>
                <div>
                    <div class="small text-muted">{{ __('events.event') }}:</div>
                    <div class="fw-bold text-dark">{{ $pledge->event?->name }} ({{ $pledge->pledge_year }})</div>
                </div>
                <div>
                    <div class="small text-muted">{{ __('donations.paid_amount') }}:</div>
                    <div class="fw-bold text-success">৳{{ number_format($pledge->verified_paid_amount, 2) }}</div>
                </div>
            </div>

            <form action="{{ route('pledges.update', $pledge) }}" method="POST" id="editPledgeForm">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold required">{{ __('donations.pledged_amount') }} (৳)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">৳</span>
                            <input type="number" step="0.01" min="{{ max(1, $pledge->verified_paid_amount) }}" name="pledged_amount" class="form-control @error('pledged_amount') is-invalid @enderror" value="{{ old('pledged_amount', $pledge->pledged_amount) }}" required>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Minimum: ৳{{ number_format($pledge->verified_paid_amount, 2) }} (already verified collected)</small>
                        @error('pledged_amount')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold required">{{ __('donations.pledged_on') }}</label>
                        <input type="date" name="pledged_on" class="form-control @error('pledged_on') is-invalid @enderror" value="{{ old('pledged_on', $pledge->pledged_on->format('Y-m-d')) }}" required>
                        @error('pledged_on')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('donations.purpose') }}</label>
                        <input type="text" name="purpose" class="form-control @error('purpose') is-invalid @enderror" value="{{ old('purpose', $pledge->purpose) }}">
                        @error('purpose')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">{{ __('donations.notes') }}</label>
                        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $pledge->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="{{ route('pledges.show', $pledge) }}" class="btn btn-outline-secondary rounded-pill px-4">
                        {{ __('common.cancel') }}
                    </a>
                    <button type="submit" id="updatePledgeBtn" class="btn btn-primary rounded-pill px-4" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('common.save_changes') }}
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
        $('#editPledgeForm').on('submit', function() {
            TempleUI.button.loading('#updatePledgeBtn');
        });
    });
</script>
@endpush
