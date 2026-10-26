@extends('layouts.app')

@section('title', $committee->name . ' — ' . __('committees.committee_details'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden"
         style="background: linear-gradient(135deg, var(--temple-maroon), var(--temple-maroon-dark));">
        <div class="card-body p-4 position-relative">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center fw-bold shadow-sm"
                         style="width: 64px; height: 64px; font-size: 1.6rem; background-color: var(--temple-saffron-light) !important; color: var(--temple-maroon) !important;">
                        <i class="bi bi-award"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark font-monospace">{{ $committee->code }}</span>
                            @if($committee->status === 'active')
                                <span class="badge bg-success bg-opacity-75">{{ __('committees.status_active') }}</span>
                            @elseif($committee->status === 'inactive')
                                <span class="badge bg-secondary bg-opacity-75">{{ __('committees.status_inactive') }}</span>
                            @else
                                <span class="badge bg-dark bg-opacity-75">{{ __('committees.status_dissolved') }}</span>
                            @endif
                        </div>
                        <h2 class="h3 fw-bold mb-0 text-white mt-1">{{ $committee->name_en }}</h2>
                        @if($committee->name_bn)
                            <div class="text-light opacity-75 small">{{ $committee->name_bn }}</div>
                        @endif
                        <div class="text-warning small mt-1">
                            <i class="bi bi-calendar-range me-1"></i>
                            {{ $committee->term_start_date ? $committee->term_start_date->format('d M Y') : 'Open Start' }} &ndash;
                            {{ $committee->term_end_date ? $committee->term_end_date->format('d M Y') : 'Present / Open End' }}
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @if(Auth::user()->hasPermission('manage_committee'))
                        <a href="{{ route('committees.edit', $committee) }}" class="btn btn-light rounded-pill px-3 fw-semibold">
                            <i class="bi bi-pencil-square me-1"></i> {{ __('common.edit') }}
                        </a>
                    @endif

                    <a href="{{ route('committees.index') }}" class="btn btn-outline-light rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Committee Description Card if present -->
    @if($committee->description_en || $committee->description_bn)
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-1">Mandate & Charter:</h6>
                @if($committee->description_en)
                    <p class="text-muted small mb-1">{{ $committee->description_en }}</p>
                @endif
                @if($committee->description_bn)
                    <p class="text-muted small mb-0">{{ $committee->description_bn }}</p>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Members Column -->
        <div class="col-lg-8">
            <!-- 1. Active Serving Members -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-people-fill text-success me-2"></i> {{ __('committees.active_members') }} ({{ $activeMemberships->count() }})
                    </h5>

                    @if(Auth::user()->hasPermission('manage_committee'))
                        <button type="button" class="btn btn-sm btn-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                            <i class="bi bi-person-plus-fill me-1"></i> {{ __('committees.add_member') }}
                        </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="activeMembersTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __('people.person') }}</th>
                                <th>{{ __('committees.designation') }}</th>
                                <th>{{ __('committees.start_date') }}</th>
                                <th>{{ __('committees.end_date') }}</th>
                                <th class="text-end pe-4">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeMemberships as $membership)
                                <tr>
                                    <td class="ps-4">
                                        <a href="{{ route('people.show', $membership->person) }}" class="fw-bold text-dark text-decoration-none">
                                            {{ $membership->person->name_en }}
                                        </a>
                                        @if($membership->person->name_bn)
                                            <small class="text-muted d-block">{{ $membership->person->name_bn }}</small>
                                        @endif
                                        <span class="badge bg-light text-dark border font-monospace mt-1">{{ $membership->person->person_code }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary">{{ $membership->designation_en }}</span>
                                        @if($membership->designation_bn)
                                            <small class="text-muted d-block">({{ $membership->designation_bn }})</small>
                                        @endif
                                    </td>
                                    <td>{{ $membership->start_date ? $membership->start_date->format('d M Y') : '—' }}</td>
                                    <td>{{ $membership->end_date ? $membership->end_date->format('d M Y') : 'Active' }}</td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('people.show', $membership->person) }}" class="btn btn-outline-primary" title="{{ __('common.view') }}">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            @if(Auth::user()->hasPermission('manage_committee'))
                                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editMemberModal{{ $membership->id }}" title="{{ __('common.edit') }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#endMemberModal{{ $membership->id }}" title="{{ __('committees.end_membership') }}">
                                                    <i class="bi bi-box-arrow-right"></i>
                                                </button>
                                            @endif
                                        </div>

                                        <!-- Edit Member Modal -->
                                        @if(Auth::user()->hasPermission('manage_committee'))
                                            <div class="modal fade text-start" id="editMemberModal{{ $membership->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content rounded-4 border-0">
                                                        <form method="POST" action="{{ route('committees.members.update', [$committee, $membership]) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-header border-bottom py-3">
                                                                <h5 class="modal-title fw-bold text-dark">{{ __('committees.edit_member') }}: {{ $membership->person->name_en }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body p-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold small">{{ __('committees.designation_en') }} <span class="text-danger">*</span></label>
                                                                    <input type="text" name="designation_en" class="form-control" value="{{ $membership->designation_en }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold small">{{ __('committees.designation_bn') }}</label>
                                                                    <input type="text" name="designation_bn" class="form-control" value="{{ $membership->designation_bn }}">
                                                                </div>
                                                                <div class="row g-2 mb-3">
                                                                    <div class="col-6">
                                                                        <label class="form-label fw-semibold small">{{ __('committees.start_date') }} <span class="text-danger">*</span></label>
                                                                        <input type="date" name="start_date" class="form-control" value="{{ $membership->start_date?->toDateString() }}" required>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <label class="form-label fw-semibold small">{{ __('committees.end_date') }}</label>
                                                                        <input type="date" name="end_date" class="form-control" value="{{ $membership->end_date?->toDateString() }}">
                                                                    </div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold small">{{ __('committees.membership_status') }}</label>
                                                                    <select name="status" class="form-select">
                                                                        <option value="active" {{ $membership->status === 'active' ? 'selected' : '' }}>{{ __('committees.status_term_active') }}</option>
                                                                        <option value="ended" {{ $membership->status === 'ended' ? 'selected' : '' }}>{{ __('committees.status_ended') }}</option>
                                                                        <option value="resigned" {{ $membership->status === 'resigned' ? 'selected' : '' }}>{{ __('committees.status_resigned') }}</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-0">
                                                                    <label class="form-label fw-semibold small">{{ __('committees.notes') }}</label>
                                                                    <textarea name="notes" class="form-control" rows="2">{{ $membership->notes }}</textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-top py-2">
                                                                <button type="button" class="btn btn-secondary rounded-pill btn-sm px-3" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                                                                <button type="submit" class="btn btn-primary rounded-pill btn-sm px-3" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">{{ __('common.save_changes') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- End Membership Modal (Preserves Historical Record) -->
                                            <div class="modal fade text-start" id="endMemberModal{{ $membership->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content rounded-4 border-0">
                                                        <form method="POST" action="{{ route('committees.members.end', [$committee, $membership]) }}">
                                                            @csrf
                                                            <div class="modal-header border-bottom py-3">
                                                                <h5 class="modal-title fw-bold text-dark">{{ __('committees.end_membership_title') }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body p-4">
                                                                <p class="small text-muted mb-3">{{ __('committees.end_membership_notice') }}</p>
                                                                <div class="p-3 bg-light rounded-3 mb-3">
                                                                    <strong>{{ $membership->person->name_en }}</strong> &mdash; {{ $membership->designation_en }}
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold small">{{ __('committees.end_date') }} <span class="text-danger">*</span></label>
                                                                    <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold small">Conclusion Status <span class="text-danger">*</span></label>
                                                                    <select name="status" class="form-select" required>
                                                                        <option value="ended">{{ __('committees.status_ended') }}</option>
                                                                        <option value="resigned">{{ __('committees.status_resigned') }}</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-0">
                                                                    <label class="form-label fw-semibold small">Reason / Remarks</label>
                                                                    <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Tenure completed upon committee reform..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-top py-2">
                                                                <button type="button" class="btn btn-secondary rounded-pill btn-sm px-3" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                                                                <button type="submit" class="btn btn-warning rounded-pill btn-sm px-3 fw-bold">{{ __('committees.end_membership') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Historical / Past Memberships Preserved -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-clock-history text-secondary me-2"></i> {{ __('committees.historical_members') }} ({{ $historicalMemberships->count() }})
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="historicalMembersTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __('people.person') }}</th>
                                <th>{{ __('committees.designation') }}</th>
                                <th>{{ __('committees.start_date') }}</th>
                                <th>{{ __('committees.end_date') }}</th>
                                <th>Status</th>
                                <th class="text-end pe-4">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historicalMemberships as $past)
                                <tr>
                                    <td class="ps-4">
                                        <a href="{{ route('people.show', $past->person) }}" class="fw-semibold text-dark text-decoration-none">
                                            {{ $past->person->name_en }}
                                        </a>
                                        <span class="badge bg-light text-dark border font-monospace ms-2">{{ $past->person->person_code }}</span>
                                    </td>
                                    <td>{{ $past->designation_en }}</td>
                                    <td>{{ $past->start_date ? $past->start_date->format('d M Y') : '—' }}</td>
                                    <td>{{ $past->end_date ? $past->end_date->format('d M Y') : '—' }}</td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                            {{ __('committees.status_' . $past->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('people.show', $past->person) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Side: Committee Info & Rules -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle text-primary me-2"></i> Committee Rules & Scope
                    </h5>
                </div>
                <div class="card-body p-4">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-3 d-flex align-items-start gap-2">
                            <i class="bi bi-check2-circle text-success fs-5"></i>
                            <div>
                                <strong>Central Devotee Registry:</strong>
                                <p class="text-muted mb-0">Assigning committee members selects existing persons from the directory. No duplicate person profile is ever created.</p>
                            </div>
                        </li>
                        <li class="mb-3 d-flex align-items-start gap-2">
                            <i class="bi bi-check2-circle text-success fs-5"></i>
                            <div>
                                <strong>Historical Preservation:</strong>
                                <p class="text-muted mb-0">Term conclusions and re-elections preserve historical memberships rather than overwriting or deleting past service records.</p>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check2-circle text-success fs-5"></i>
                            <div>
                                <strong>Multi-Term Support:</strong>
                                <p class="text-muted mb-0">Devotees can serve across multiple terms and different committees concurrently or sequentially.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Assign Existing Person to Committee -->
@if(Auth::user()->hasPermission('manage_committee'))
    <div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="{{ route('committees.members.add', $committee) }}">
                    @csrf
                    <input type="hidden" name="committee_id" value="{{ $committee->id }}">
                    <input type="hidden" name="status" value="active">

                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark">
                            <i class="bi bi-person-plus text-primary me-1"></i> {{ __('committees.add_member') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <p class="small text-muted mb-3">{{ __('committees.select_person_help') }}</p>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">{{ __('families.select_person') }} <span class="text-danger">*</span></label>
                            <select name="person_id" class="form-select select2" required>
                                <option value="">-- Choose Existing Devotee --</option>
                                @foreach($availablePeople as $person)
                                    <option value="{{ $person->id }}">
                                        {{ $person->name_en }} @if($person->name_bn) ({{ $person->name_bn }}) @endif &mdash; {{ $person->person_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">{{ __('committees.designation_en') }} <span class="text-danger">*</span></label>
                                <input type="text" name="designation_en" class="form-control" placeholder="e.g. President, Treasurer" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">{{ __('committees.designation_bn') }}</label>
                                <input type="text" name="designation_bn" class="form-control" placeholder="যেমনঃ সভাপতি, কোষাধ্যক্ষ">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">{{ __('committees.start_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">{{ __('committees.end_date') }}</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $committee->term_end_date?->toDateString() }}">
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold small">{{ __('committees.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Specific portfolio or remarks..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer border-top py-2">
                        <button type="button" class="btn btn-secondary rounded-pill btn-sm px-3" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-pill btn-sm px-3" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                            <i class="bi bi-check-lg me-1"></i> {{ __('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        TempleUI.initDataTable('#activeMembersTable', {
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 4 }
            ]
        });

        TempleUI.initDataTable('#historicalMembersTable', {
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 5 }
            ]
        });
    });
</script>
@endpush
