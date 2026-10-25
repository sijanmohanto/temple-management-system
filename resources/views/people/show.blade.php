@extends('layouts.app')

@section('title', $person->name . ' — ' . __('people.person_details'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden"
         style="background: linear-gradient(135deg, var(--temple-maroon), var(--temple-maroon-dark));">
        <div class="card-body p-4 position-relative">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-circle rounded-circle bg-white text-dark d-flex align-items-center justify-content-center fw-bold shadow-sm"
                         style="width: 64px; height: 64px; font-size: 1.5rem; background-color: var(--temple-saffron-light) !important; color: var(--temple-maroon) !important;">
                        {{ mb_substr($person->name_en, 0, 1) }}
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark font-monospace">{{ $person->person_code }}</span>
                            @if($person->isActive())
                                <span class="badge bg-success bg-opacity-75"><i class="bi bi-check-circle me-1"></i> {{ __('common.status_active') }}</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-75"><i class="bi bi-slash-circle me-1"></i> {{ __('common.status_inactive') }}</span>
                            @endif
                        </div>
                        <h2 class="h3 fw-bold mb-0 text-white mt-1">
                            {{ $person->name_en }}
                            @if($person->name_bn)
                                <span class="fs-5 fw-normal opacity-75">({{ $person->name_bn }})</span>
                            @endif
                        </h2>
                        @if($person->gotra)
                            <div class="text-warning small mt-1">
                                <i class="bi bi-flower1 me-1"></i> {{ __('people.gotra') }}: <strong>{{ $person->gotra }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @if(Auth::user()->hasPermission('manage_people'))
                        <a href="{{ route('people.edit', $person) }}" class="btn btn-light rounded-pill px-3 fw-semibold">
                            <i class="bi bi-pencil-square me-1"></i> {{ __('common.edit') }}
                        </a>

                        <form method="POST" action="{{ route('people.toggle-status', $person) }}" class="d-inline"
                              onsubmit="return confirm('{{ $person->isActive() ? __('people.deactivate_confirm') : __('people.reactivate_confirm') }}')">
                            @csrf
                            <button type="submit" class="btn btn-outline-light rounded-pill px-3">
                                <i class="bi bi-power me-1"></i> {{ $person->isActive() ? __('people.deactivate') : __('people.reactivate') }}
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('people.index') }}" class="btn btn-outline-light rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Potential Duplicate Profiles Warning Banner -->
    @if($duplicateCheck['has_duplicates'])
        <div class="alert alert-warning border-warning shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
                <div class="w-100">
                    <h6 class="alert-heading fw-bold mb-1">{{ __('people.duplicate_warning_title') }}</h6>
                    <p class="small text-muted mb-2">{{ __('people.duplicate_detected_warning') }}</p>
                    <div class="list-group">
                        @foreach($duplicateCheck['matches'] as $match)
                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3">
                                <div>
                                    <span class="badge bg-dark font-monospace me-2">{{ $match['person']->person_code }}</span>
                                    <strong class="text-dark">{{ $match['person']->name_en }}</strong>
                                    @if($match['person']->name_bn)
                                        <span class="text-muted">({{ $match['person']->name_bn }})</span>
                                    @endif
                                    <span class="text-muted ms-2">&bull; {{ $match['person']->phone ?? 'No Phone' }}</span>
                                    <div class="small text-danger mt-1">
                                        @foreach($match['reasons'] as $reason)
                                            <span><i class="bi bi-dot"></i> {{ $reason }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <a href="{{ route('people.show', $match['person']) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('people.duplicate_view_existing') }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Details Column -->
        <div class="col-lg-8">
            <!-- 1. Devotee Classifications -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-tags text-primary me-2"></i> {{ __('people.classifications') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($person->classifications as $classification)
                            @php
                                $badgeStyle = match($classification->slug) {
                                    'resident' => 'bg-info bg-opacity-10 text-info border-info',
                                    'donor' => 'bg-warning bg-opacity-10 text-warning-emphasis border-warning',
                                    'committee_member' => 'bg-primary bg-opacity-10 text-primary border-primary',
                                    'volunteer' => 'bg-success bg-opacity-10 text-success border-success',
                                    default => 'bg-secondary bg-opacity-10 text-secondary border-secondary',
                                };
                            @endphp
                            <div class="border rounded-3 p-3 {{ $badgeStyle }} d-flex align-items-center gap-2 flex-grow-1" style="min-width: 200px;">
                                <i class="bi bi-check-circle-fill fs-5"></i>
                                <div>
                                    <div class="fw-bold">{{ $classification->name }}</div>
                                    <small class="opacity-75 d-block" style="font-size: 0.75rem;">
                                        {{ $classification->description }}
                                    </small>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">{{ __('common.none') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- 2. Detailed Profile Info -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-person-lines-fill text-primary me-2"></i> Profile & Lineage Information
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">{{ __('people.phone') }}</span>
                            <span class="fw-semibold text-dark">{{ $person->phone ?? '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">{{ __('people.email') }}</span>
                            <span class="fw-semibold text-dark">{{ $person->email ?? '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">{{ __('people.date_of_birth') }}</span>
                            <span class="fw-semibold text-dark">
                                {{ $person->date_of_birth ? $person->date_of_birth->format('d M Y') : '—' }}
                                @if($person->date_of_birth)
                                    <small class="text-muted">({{ $person->date_of_birth->age }} years)</small>
                                @endif
                            </span>
                        </div>
                        <div class="col-sm-3">
                            <span class="text-muted small d-block">{{ __('people.gender') }}</span>
                            <span class="fw-semibold text-dark text-capitalize">{{ $person->gender ? __('people.gender_' . $person->gender) : '—' }}</span>
                        </div>
                        <div class="col-sm-3">
                            <span class="text-muted small d-block">{{ __('people.blood_group') }}</span>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                {{ $person->blood_group ?? '—' }}
                            </span>
                        </div>

                        <hr class="my-2 text-muted opacity-25">

                        <div class="col-sm-6">
                            <span class="text-muted small d-block">{{ __('people.father_name') }}</span>
                            <span class="fw-semibold text-dark">{{ $person->father_name ?? '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">{{ __('people.mother_name') }}</span>
                            <span class="fw-semibold text-dark">{{ $person->mother_name ?? '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">{{ __('people.spouse_name') }}</span>
                            <span class="fw-semibold text-dark">{{ $person->spouse_name ?? '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">{{ __('people.occupation') }}</span>
                            <span class="fw-semibold text-dark">{{ $person->occupation ?? '—' }}</span>
                        </div>

                        <hr class="my-2 text-muted opacity-25">

                        <div class="col-12">
                            <span class="text-muted small d-block">{{ __('people.present_address') }}</span>
                            <span class="text-dark">{{ $person->present_address ?? '—' }}</span>
                        </div>
                        <div class="col-12">
                            <span class="text-muted small d-block">{{ __('people.permanent_address') }}</span>
                            <span class="text-dark">{{ $person->permanent_address ?? '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">{{ __('people.emergency_contact_name') }}</span>
                            <span class="fw-semibold text-dark">{{ $person->emergency_contact_name ?? '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">{{ __('people.emergency_contact_phone') }}</span>
                            <span class="fw-semibold text-dark">{{ $person->emergency_contact_phone ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Committee Memberships & Historical Service -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-award text-primary me-2"></i> {{ __('people.committee_history') }}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="personCommitteesTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">{{ __('committees.committee') }}</th>
                                    <th>{{ __('committees.designation') }}</th>
                                    <th>{{ __('committees.start_date') }}</th>
                                    <th>{{ __('committees.end_date') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($person->committeeMemberships as $membership)
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('committees.show', $membership->committee) }}" class="fw-semibold text-decoration-none">
                                                {{ $membership->committee->name }}
                                            </a>
                                            <div class="small text-muted font-monospace">{{ $membership->committee->code }}</div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $membership->designation_en }}</span>
                                            @if($membership->designation_bn)
                                                <small class="text-muted d-block">({{ $membership->designation_bn }})</small>
                                            @endif
                                        </td>
                                        <td>{{ $membership->start_date ? $membership->start_date->format('d M Y') : '—' }}</td>
                                        <td>{{ $membership->end_date ? $membership->end_date->format('d M Y') : 'Ongoing' }}</td>
                                        <td>
                                            @if($membership->status === 'active')
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                    {{ __('committees.status_term_active') }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                                    {{ __('committees.status_' . $membership->status) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            {{ __('people.no_committee_history') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Family, Remarks & Security Notes -->
        <div class="col-lg-4">
            <!-- Family Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-house-heart text-danger me-2"></i> {{ __('families.family') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($person->family)
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-light text-dark font-monospace">{{ $person->family->family_code }}</span>
                            @if($person->family->primary_contact_person_id === $person->id)
                                <span class="badge bg-primary"><i class="bi bi-star-fill me-1"></i> {{ __('families.primary_contact') }}</span>
                            @endif
                        </div>
                        <h6 class="fw-bold text-dark mb-1">
                            <a href="{{ route('families.show', $person->family) }}" class="text-decoration-none">
                                {{ $person->family->family_name }}
                            </a>
                        </h6>
                        <p class="text-muted small mb-3">
                            {{ __('people.family_relationship') }}: 
                            <strong>{{ $person->family_relationship ? __('people.relationship_' . $person->family_relationship) : 'Member' }}</strong>
                        </p>

                        <div class="border-top pt-3">
                            <h6 class="small fw-bold text-muted mb-2">{{ __('families.members_list') }} ({{ $person->family->members->count() }}):</h6>
                            <ul class="list-unstyled mb-0 small">
                                @foreach($person->family->members as $member)
                                    <li class="py-1 d-flex justify-content-between align-items-center {{ $member->id === $person->id ? 'fw-bold text-primary' : '' }}">
                                        <span>
                                            {{ $member->name_en }}
                                            @if($member->id === $person->id) (Current) @endif
                                        </span>
                                        <span class="text-muted">{{ $member->family_relationship ? __('people.relationship_' . $member->family_relationship) : '' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-muted mb-0">{{ __('people.no_family_assigned') }}</p>
                    @endif
                </div>
            </div>

            <!-- Notes & Remarks -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-journal-text text-secondary me-2"></i> Remarks & Notes
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <span class="text-muted small d-block mb-1">{{ __('people.public_notes') }}</span>
                        <div class="p-3 bg-light rounded-3 text-dark small">
                            {{ $person->public_notes ?: 'No general remarks entered.' }}
                        </div>
                    </div>

                    @if($person->canViewPrivateNotes(Auth::user()))
                        <div>
                            <span class="text-danger small fw-bold d-block mb-1">
                                <i class="bi bi-lock-fill me-1"></i> {{ __('people.private_notes') }}
                            </span>
                            <div class="p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3 text-danger-emphasis small">
                                {{ $person->private_notes ?: 'No confidential notes recorded.' }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Metadata Card -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 text-muted small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span>{{ __('common.created_at') }}:</span>
                        <span>{{ $person->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @if($person->creator)
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span>Created By:</span>
                            <span>{{ $person->creator->name }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between py-1">
                        <span>{{ __('common.updated_at') }}:</span>
                        <span>{{ $person->updated_at->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($person->committeeMemberships->count() > 0)
<script>
    $(document).ready(function() {
        TempleUI.initDataTable('#personCommitteesTable', {
            pageLength: 5,
            lengthMenu: [[5, 10, 25], [5, 10, 25]]
        });
    });
</script>
@endif
@endpush
