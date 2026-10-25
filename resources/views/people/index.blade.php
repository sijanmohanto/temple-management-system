@extends('layouts.app')

@section('title', __('people.people_directory'))

@section('content')
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('people.people_directory') }}</h1>
            <p class="text-muted small mb-0">{{ __('people.people') }} &bull; {{ $people->total() }} {{ __('common.details') }}</p>
        </div>

        @if(Auth::user()->hasPermission('manage_people'))
            <div>
                <a href="{{ route('people.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                    <i class="bi bi-person-plus-fill me-1"></i> {{ __('people.add_person') }}
                </a>
            </div>
        @endif
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('people.index') }}" class="row g-3" id="peopleFilterForm">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('people.classifications') }}</label>
                    <select name="classification" id="filterClassification" class="form-select select2" data-placeholder="{{ __('people.filter_by_classification') }}">
                        <option value="">{{ __('people.filter_by_classification') }}</option>
                        @foreach($classifications as $classification)
                            <option value="{{ $classification->slug }}" {{ ($filters['classification'] ?? '') === $classification->slug ? 'selected' : '' }}>
                                {{ $classification->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('families.family') }}</label>
                    <select name="family_id" id="filterFamily" class="form-select select2" data-placeholder="{{ __('people.filter_by_family') }}">
                        <option value="">{{ __('people.filter_by_family') }}</option>
                        @foreach($families as $fam)
                            <option value="{{ $fam->id }}" {{ ($filters['family_id'] ?? '') == $fam->id ? 'selected' : '' }}>
                                {{ $fam->family_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-muted">{{ __('common.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select">
                        <option value="">{{ __('people.filter_by_status') }}</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>{{ __('common.status_active') }}</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>{{ __('common.status_inactive') }}</option>
                    </select>
                </div>

                <div class="col-md-12 col-lg-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">
                        <i class="bi bi-funnel me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('people.index') }}" class="btn btn-outline-secondary rounded-pill" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Devotees Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="peopleDataTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('people.person_code') }}</th>
                            <th>{{ __('people.name_en') }}</th>
                            <th>{{ __('people.phone') }}</th>
                            <th>{{ __('people.classifications') }}</th>
                            <th>{{ __('families.family') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($people as $person)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $person->person_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $person->name_en }}</div>
                                    @if($person->name_bn)
                                        <small class="text-muted">{{ $person->name_bn }}</small>
                                    @endif
                                    @if($person->gotra)
                                        <div><span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">{{ $person->gotra }}</span></div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $person->phone ?? '—' }}</div>
                                    @if($person->email)
                                        <small class="text-muted">{{ $person->email }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($person->classifications as $classification)
                                            @php
                                                $badgeColor = match($classification->slug) {
                                                    'resident' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                                    'donor' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                                                    'committee_member' => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                                                    'volunteer' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                                    default => 'bg-secondary bg-opacity-10 text-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeColor }}" style="font-size: 0.72rem;">
                                                {{ $classification->name }}
                                            </span>
                                        @empty
                                            <span class="text-muted small">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    @if($person->family)
                                        <a href="{{ route('families.show', $person->family) }}" class="text-decoration-none fw-semibold text-primary">
                                            <i class="bi bi-house me-1"></i> {{ $person->family->family_name }}
                                        </a>
                                        @if($person->family_relationship)
                                            <div>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">
                                                    {{ __('people.relationship_' . $person->family_relationship) }}
                                                </span>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted small">{{ __('people.no_family_assigned') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($person->isActive())
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i> {{ __('common.status_active') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">
                                            <i class="bi bi-slash-circle me-1"></i> {{ __('common.status_inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('people.show', $person) }}" class="btn btn-outline-primary" title="{{ __('common.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if(Auth::user()->hasPermission('manage_people'))
                                            <a href="{{ route('people.edit', $person) }}" class="btn btn-outline-secondary" title="{{ __('common.edit') }}">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <form method="POST" action="{{ route('people.toggle-status', $person) }}" class="d-inline"
                                                  onsubmit="return confirm('{{ $person->isActive() ? __('people.deactivate_confirm') : __('people.reactivate_confirm') }}')">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-{{ $person->isActive() ? 'warning' : 'success' }}" 
                                                        title="{{ $person->isActive() ? __('people.deactivate') : __('people.reactivate') }}">
                                                    <i class="bi bi-power"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="peopleNativePagination" class="mt-3">
                @if($people->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $people->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize server-side DataTables
        var peopleTable = TempleUI.initDataTable('#peopleDataTable', {
            serverSide: true,
            processing: true,
            deferLoading: {{ $people->total() }}, // Use existing initial HTML for fast first render!
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
            ajax: {
                url: "{{ route('people.index') }}",
                data: function(d) {
                    d.classification = $('#filterClassification').val();
                    d.family_id = $('#filterFamily').val();
                    d.status = $('#filterStatus').val();
                }
            },
            columns: [
                { data: 0, name: 'person_code' },
                { data: 1, name: 'name_en' },
                { data: 2, name: 'phone' },
                { data: 3, name: 'classifications', orderable: false },
                { data: 4, name: 'family', orderable: false },
                { data: 5, name: 'status' },
                { data: 6, name: 'actions', orderable: false, className: 'text-end pe-3' }
            ]
        });

        // Hide native Laravel pagination once DataTables handles it
        $('#peopleNativePagination').hide();

        $('#peopleFilterForm').on('submit', function(e) {
            e.preventDefault();
            peopleTable.draw();
        });

        $('#filterClassification, #filterFamily, #filterStatus').on('change', function() {
            peopleTable.draw();
        });
    });
</script>
@endpush
