@extends('layouts.app')

@section('title', $family->family_name . ' — ' . __('families.family_details'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden"
         style="background: linear-gradient(135deg, var(--temple-maroon), var(--temple-maroon-dark));">
        <div class="card-body p-4 position-relative">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center fw-bold shadow-sm"
                         style="width: 64px; height: 64px; font-size: 1.6rem; background-color: var(--temple-saffron-light) !important; color: var(--temple-maroon) !important;">
                        <i class="bi bi-house-heart"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark font-monospace">{{ $family->family_code }}</span>
                            @if($family->isActive())
                                <span class="badge bg-success bg-opacity-75">{{ __('common.status_active') }}</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-75">{{ __('common.status_inactive') }}</span>
                            @endif
                        </div>
                        <h2 class="h3 fw-bold mb-0 text-white mt-1">{{ $family->family_name }}</h2>
                        @if($family->area)
                            <div class="text-warning small mt-1">
                                <i class="bi bi-geo-alt me-1"></i> {{ $family->area }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @if(Auth::user()->hasPermission('manage_people'))
                        <a href="{{ route('families.edit', $family) }}" class="btn btn-light rounded-pill px-3 fw-semibold">
                            <i class="bi bi-pencil-square me-1"></i> {{ __('common.edit') }}
                        </a>

                        @if($family->canBeSafelyDeleted())
                            <form method="POST" action="{{ route('families.destroy', $family) }}" class="d-inline"
                                  onsubmit="return confirm('{{ __('families.delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger text-light border-light rounded-pill px-3">
                                    <i class="bi bi-trash me-1"></i> {{ __('common.delete') }}
                                </button>
                            </form>
                        @endif
                    @endif

                    <a href="{{ route('families.index') }}" class="btn btn-outline-light rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Members Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-people-fill text-primary me-2"></i> {{ __('families.members_list') }} ({{ $family->members->count() }})
                    </h5>

                    @if(!$family->canBeSafelyDeleted())
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border small">
                            <i class="bi bi-shield-lock me-1"></i> Protected from deletion
                        </span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="familyMembersTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __('people.person_code') }}</th>
                                <th>{{ __('people.name_en') }}</th>
                                <th>{{ __('families.relationship_in_family') }}</th>
                                <th>{{ __('people.classifications') }}</th>
                                <th class="text-end pe-4">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($family->members as $member)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-light text-dark border font-monospace">{{ $member->person_code }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('people.show', $member) }}" class="fw-bold text-dark text-decoration-none">
                                            {{ $member->name_en }}
                                        </a>
                                        @if($member->name_bn)
                                            <small class="text-muted d-block">{{ $member->name_bn }}</small>
                                        @endif
                                        @if($family->primary_contact_person_id === $member->id)
                                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 mt-1">
                                                <i class="bi bi-star-fill me-1"></i> {{ __('families.primary_contact') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border">
                                            {{ $member->family_relationship ? __('people.relationship_' . $member->family_relationship) : 'Member' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($member->classifications as $c)
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.7rem;">
                                                    {{ $c->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('people.show', $member) }}" class="btn btn-outline-primary" title="{{ __('common.view') }}">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            @if(Auth::user()->hasPermission('manage_people'))
                                                <form method="POST" action="{{ route('families.members.remove', [$family, $member]) }}" class="d-inline"
                                                      onsubmit="return confirm('{{ __('families.remove_member_confirm') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="Disassociate">
                                                        <i class="bi bi-person-x"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                        {{ __('families.no_members') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Existing Person to Family Card -->
            @if(Auth::user()->hasPermission('manage_people'))
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-person-plus text-success me-2"></i> {{ __('families.add_member_modal') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @if($unassignedPeople->count() > 0)
                            <form method="POST" action="{{ route('families.members.add', $family) }}" class="row g-3 align-items-end">
                                @csrf

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">{{ __('families.select_person') }}</label>
                                    <select name="person_id" class="form-select select2" data-placeholder="-- Choose Devotee --" required>
                                        <option value="">-- Choose Devotee --</option>
                                        @foreach($unassignedPeople as $person)
                                            <option value="{{ $person->id }}">
                                                {{ $person->name_en }} ({{ $person->person_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small">{{ __('families.relationship_in_family') }}</label>
                                    <select name="family_relationship" class="form-select">
                                        <option value="head">{{ __('people.relationship_head') }}</option>
                                        <option value="spouse">{{ __('people.relationship_spouse') }}</option>
                                        <option value="son">{{ __('people.relationship_son') }}</option>
                                        <option value="daughter">{{ __('people.relationship_daughter') }}</option>
                                        <option value="father">{{ __('people.relationship_father') }}</option>
                                        <option value="mother">{{ __('people.relationship_mother') }}</option>
                                        <option value="brother">{{ __('people.relationship_brother') }}</option>
                                        <option value="sister">{{ __('people.relationship_sister') }}</option>
                                        <option value="other">{{ __('people.relationship_other') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-dark w-100 rounded-pill">
                                        <i class="bi bi-plus-lg me-1"></i> Add
                                    </button>
                                </div>
                            </form>
                        @else
                            <p class="text-muted small mb-0">
                                All active devotees currently belong to a family unit. To add a new member, you can
                                <a href="{{ route('people.create') }}">register a new devotee profile</a> with this family selected.
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Family Info Column -->
        <div class="col-lg-4">
            <!-- Contact Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-telephone text-primary me-2"></i> Household Contacts
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <span class="text-muted small d-block">{{ __('families.primary_contact') }}</span>
                        @if($family->primaryContact)
                            <a href="{{ route('people.show', $family->primaryContact) }}" class="fw-bold text-decoration-none">
                                <i class="bi bi-person-fill me-1"></i> {{ $family->primaryContact->name_en }}
                            </a>
                        @else
                            <span class="text-muted">{{ __('families.no_primary_contact') }}</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">{{ __('families.phone') }}</span>
                        <span class="fw-semibold text-dark">{{ $family->phone ?? '—' }}</span>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">{{ __('families.area') }}</span>
                        <span class="fw-semibold text-dark">{{ $family->area ?? '—' }}</span>
                    </div>

                    <div class="mb-0">
                        <span class="text-muted small d-block">{{ __('families.address') }}</span>
                        <span class="text-dark">{{ $family->address ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Remarks Card -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-journal-text text-secondary me-2"></i> {{ __('families.notes') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-0">
                        {{ $family->notes ?: 'No specific notes recorded for this household.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($family->members->count() > 0)
<script>
    $(document).ready(function() {
        TempleUI.initDataTable('#familyMembersTable', {
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 4 }
            ]
        });
    });
</script>
@endif
@endpush
