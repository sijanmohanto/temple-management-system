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
                    <img src="{{ $person->avatar_url }}" alt="{{ $person->name_en }}" class="rounded-circle shadow-sm object-fit-cover border border-2 border-white bg-white" style="width: 68px; height: 68px; min-width: 68px;">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
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
                <div class="card-body p-3">
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
            <!-- Devotee Portal Account & Credentials Card -->
            @php
                $linkedUser = $person->resolveUser();
            @endphp
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-shield-lock text-danger me-2"></i> {{ __('devotee.portal_account_card_title') }}
                    </h5>
                    @if($linkedUser)
                        <span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle me-1"></i> {{ __('devotee.account_active') }}</span>
                    @else
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ __('devotee.no_account') }}</span>
                    @endif
                </div>
                <div class="card-body p-4">
                    @if($linkedUser)
                        <div class="mb-3">
                            <span class="text-muted small d-block">{{ __('devotee.login_identifier') }}</span>
                            <strong class="text-dark font-monospace fs-6">{{ $linkedUser->phone ?: $linkedUser->email }}</strong>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small d-block">{{ __('devotee.account_status') }}</span>
                            @if($linkedUser->isActive())
                                <span class="badge bg-success">{{ __('common.status_active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('common.status_inactive') }}</span>
                            @endif
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small d-block">{{ __('devotee.assigned_roles') }}</span>
                            @foreach($linkedUser->roles as $role)
                                <span class="badge bg-primary bg-opacity-10 text-primary me-1">{{ $role->name_en }}</span>
                            @endforeach
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-column gap-2 mt-3 pt-3 border-top">
                            @if(Auth::user()->hasPermission('view_devotee_passwords'))
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#revealPasswordModal">
                                    <i class="bi bi-eye me-1"></i> {{ __('devotee.reveal_password_btn') }}
                                </button>
                            @endif

                            @if(Auth::user()->hasPermission('update_devotee_passwords'))
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                                    <i class="bi bi-key me-1"></i> {{ __('devotee.reset_password_btn') }}
                                </button>
                            @endif
                        </div>
                    @else
                        <p class="text-muted small mb-3">
                            {{ __('devotee.no_account_notice') }}
                        </p>
                        @if(Auth::user()->hasPermission('manage_people'))
                            @if(! empty($person->phone) || ! empty($person->email))
                                <form method="POST" action="{{ route('people.credentials.provision', $person) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm temple-btn-maroon rounded-pill w-100">
                                        <i class="bi bi-person-plus me-1"></i> {{ __('devotee.provision_account_btn') }}
                                    </button>
                                </form>
                            @else
                                <div class="alert alert-warning py-2 px-3 small mb-0">
                                    <i class="bi bi-info-circle me-1"></i> {{ __('devotee.phone_or_email_required_to_provision') }}
                                </div>
                            @endif
                        @endif
                    @endif
                </div>
            </div>

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

@if($linkedUser && Auth::user()->hasPermission('view_devotee_passwords'))
<!-- Reveal Password Modal -->
<div class="modal fade" id="revealPasswordModal" tabindex="-1" aria-labelledby="revealPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark" id="revealPasswordModalLabel">
                    <i class="bi bi-shield-lock text-danger me-2"></i> {{ __('devotee.reveal_password_modal_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    {{ __('devotee.reveal_password_audit_notice') }}
                </p>
                <div class="mb-3">
                    <label for="revealReason" class="form-label small fw-semibold text-secondary">{{ __('devotee.inspection_reason') }}</label>
                    <input type="text" id="revealReason" class="form-control" placeholder="e.g. Assisting devotee with initial login">
                </div>
                <div id="revealedPasswordContainer" class="d-none">
                    <div class="p-3 bg-light rounded-3 text-center border">
                        <small class="text-muted d-block mb-1">{{ __('devotee.current_password') }}</small>
                        <h3 class="fw-bold text-danger font-monospace mb-0" id="revealedPasswordText"></h3>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn temple-btn-maroon rounded-pill px-3" id="confirmRevealBtn">
                    <i class="bi bi-eye me-1"></i> {{ __('devotee.reveal_btn') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($linkedUser && Auth::user()->hasPermission('update_devotee_passwords'))
<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="resetPasswordForm" method="POST" action="{{ route('people.credentials.reset', $person) }}">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="resetPasswordModalLabel">
                        <i class="bi bi-key text-primary me-2"></i> {{ __('devotee.reset_password_modal_title') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        {{ __('devotee.reset_password_modal_hint') }}
                    </p>
                    <div class="mb-3">
                        <label for="newDevoteePassword" class="form-label small fw-semibold text-secondary">{{ __('devotee.custom_password_optional') }}</label>
                        <input type="text" id="newDevoteePassword" name="new_password" class="form-control font-monospace" placeholder="Leave blank to auto-generate secure password">
                    </div>
                    <div class="mb-3">
                        <label for="resetReason" class="form-label small fw-semibold text-secondary">{{ __('devotee.reset_reason') }}</label>
                        <input type="text" id="resetReason" name="reason" class="form-control" placeholder="e.g. Devotee forgot login password" required>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-3">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('devotee.reset_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if(session('devotee_credential_modal'))
    @php
        $modalCreds = session('devotee_credential_modal');
    @endphp
    <!-- Devotee Login Credentials Created Modal (Auto-pops up on success) -->
    <div class="modal fade" id="newDevoteeCredentialModal" tabindex="-1" aria-labelledby="newDevoteeCredentialModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <!-- Modal Header -->
                <div class="modal-header text-white border-0 py-3" style="background: linear-gradient(135deg, var(--temple-maroon), var(--temple-maroon-dark));">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; font-size: 1.3rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="newDevoteeCredentialModalLabel">
                                {{ __('people.credential_modal_title') }}
                            </h5>
                            <small class="text-white-50">{{ __('people.credential_modal_subtitle') }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Status Banner -->
                    @if($modalCreds['email_sent'])
                        <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 rounded-3 mb-3 border-success border-opacity-25 bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <div class="small fw-semibold">
                                {{ __('people.welcome_email_sent', ['email' => $modalCreds['sent_to_email'] ?? $modalCreds['email']]) }}
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light border d-flex align-items-center gap-2 py-2 px-3 rounded-3 mb-3 text-secondary">
                            <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                            <div class="small">
                                {{ __('people.welcome_email_not_sent') }}
                            </div>
                        </div>
                    @endif

                    <!-- Credential Card -->
                    <div class="card border rounded-3 bg-light p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                            <span class="text-muted small fw-semibold">{{ __('people.person') }}</span>
                            <span class="fw-bold text-dark">{{ $modalCreds['name'] }} @if(!empty($modalCreds['name_bn'])) ({{ $modalCreds['name_bn'] }}) @endif</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                            <span class="text-muted small fw-semibold">{{ __('people.person_code') }}</span>
                            <span class="badge bg-secondary font-monospace">{{ $modalCreds['person_code'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                            <span class="text-muted small fw-semibold">{{ __('people.login_identifier') }}</span>
                            <span class="fw-bold font-monospace text-primary fs-6">{{ $modalCreds['login_id'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                            <span class="text-muted small fw-semibold">{{ __('people.temporary_password') }}</span>
                            <div class="d-flex align-items-center gap-2">
                                <span id="modalPasswordDisplay" class="font-monospace fw-bold px-2 py-1 bg-white border rounded text-danger" style="letter-spacing: 1px;">
                                    {{ $modalCreds['plain_password'] }}
                                </span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">{{ __('people.portal_login_url') }}</span>
                            <a href="{{ $modalCreds['login_url'] }}" target="_blank" class="small text-truncate text-decoration-none fw-semibold" style="max-width: 220px;">
                                {{ $modalCreds['login_url'] }}
                            </a>
                        </div>
                    </div>

                    <!-- Hidden text for copying -->
                    <textarea id="credentialCopyText" class="d-none">🏛️ {{ config('app.name') }} - Devotee Portal
👤 Devotee: {{ $modalCreds['name'] }}
🆔 Code: {{ $modalCreds['person_code'] }}
📱 Login ID: {{ $modalCreds['login_id'] }}
🔑 Password: {{ $modalCreds['plain_password'] }}
🌐 Portal URL: {{ $modalCreds['login_url'] }}</textarea>

                    <p class="text-muted small text-center mb-0">
                        <i class="bi bi-shield-exclamation text-warning me-1"></i>
                        {{ __('people.welcome_mail_change_pass_advice') }}
                    </p>
                </div>

                <!-- Modal Footer with prominent copy button -->
                <div class="modal-footer border-top bg-light p-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">
                        {{ __('people.close_modal') }}
                    </button>
                    <button type="button" id="btnCopyDevoteeCredentials" class="btn btn-primary rounded-pill px-4 shadow-sm"
                            style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                        <i class="bi bi-clipboard-check me-1"></i> <span id="copyBtnLabel">{{ __('people.copy_credentials') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
@if(session('devotee_credential_modal'))
<script>
    $(document).ready(function() {
        const modalEl = document.getElementById('newDevoteeCredentialModal');
        if (modalEl) {
            const credModal = new bootstrap.Modal(modalEl);
            credModal.show();
        }

        $('#btnCopyDevoteeCredentials').on('click', function() {
            const textToCopy = $('#credentialCopyText').val();
            const btn = $(this);
            const originalHtml = btn.html();

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(showCopied, fallbackCopy);
            } else {
                fallbackCopy();
            }

            function fallbackCopy() {
                const textArea = document.createElement('textarea');
                textArea.value = textToCopy;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    showCopied();
                } catch (err) {
                    alert('Copy failed, please copy manually.');
                }
                document.body.removeChild(textArea);
            }

            function showCopied() {
                btn.removeClass('btn-primary').addClass('btn-success').html('<i class="bi bi-check2-all me-1"></i> {{ __("people.copied_to_clipboard") }}');
                setTimeout(function() {
                    btn.removeClass('btn-success').addClass('btn-primary').html(originalHtml);
                }, 2500);
            }
        });
    });
</script>
@endif

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

@if($linkedUser && Auth::user()->hasPermission('view_devotee_passwords'))
<script>
    $(document).ready(function() {
        $('#confirmRevealBtn').on('click', function() {
            var btn = $(this);
            var reason = $('#revealReason').val();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Decrypting...');

            $.ajax({
                url: "{{ route('people.credentials.reveal', $person) }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    reason: reason
                },
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="bi bi-eye me-1"></i> Reveal');
                    if (res.success) {
                        $('#revealedPasswordText').text(res.password);
                        $('#revealedPasswordContainer').removeClass('d-none');
                        toastr.success('Password decrypted successfully.');
                    } else {
                        toastr.error(res.message);
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="bi bi-eye me-1"></i> Reveal');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Decryption failed.';
                    toastr.error(msg);
                }
            });
        });
    });
</script>
@endif
@endpush
