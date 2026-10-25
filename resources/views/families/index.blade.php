@extends('layouts.app')

@section('title', __('families.family_units'))

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('families.family_units') }}</h1>
            <p class="text-muted small mb-0">{{ __('families.families') }} &bull; {{ $families->total() }} {{ __('common.details') }}</p>
        </div>

        @if(Auth::user()->hasPermission('manage_people'))
            <div>
                <a href="{{ route('families.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                    <i class="bi bi-plus-circle-fill me-1"></i> {{ __('families.add_family') }}
                </a>
            </div>
        @endif
    </div>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('families.index') }}" class="row g-3">
                <div class="col-md-8 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" 
                               placeholder="Search by family code, name, phone, area..." 
                               value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark rounded-pill px-4">
                        <i class="bi bi-funnel me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('families.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Families Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('families.family_code') }}</th>
                        <th>{{ __('families.family_name') }}</th>
                        <th>{{ __('families.primary_contact') }}</th>
                        <th>{{ __('families.phone') }}</th>
                        <th>{{ __('families.area') }}</th>
                        <th>{{ __('families.members_count') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end pe-4">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($families as $family)
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-light text-dark border font-monospace">{{ $family->family_code }}</span>
                            </td>
                            <td>
                                <a href="{{ route('families.show', $family) }}" class="fw-bold text-dark text-decoration-none">
                                    {{ $family->family_name }}
                                </a>
                            </td>
                            <td>
                                @if($family->primaryContact)
                                    <a href="{{ route('people.show', $family->primaryContact) }}" class="text-decoration-none fw-semibold">
                                        <i class="bi bi-person me-1"></i> {{ $family->primaryContact->name }}
                                    </a>
                                @else
                                    <span class="text-muted small">{{ __('families.no_primary_contact') }}</span>
                                @endif
                            </td>
                            <td>{{ $family->phone ?? '—' }}</td>
                            <td>{{ $family->area ?? '—' }}</td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                    <i class="bi bi-people me-1"></i> {{ $family->members_count }}
                                </span>
                            </td>
                            <td>
                                @if($family->isActive())
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        {{ __('common.status_active') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                        {{ __('common.status_inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('families.show', $family) }}" class="btn btn-outline-primary" title="{{ __('common.view') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if(Auth::user()->hasPermission('manage_people'))
                                        <a href="{{ route('families.edit', $family) }}" class="btn btn-outline-secondary" title="{{ __('common.edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        @if($family->canBeSafelyDeleted())
                                            <form method="POST" action="{{ route('families.destroy', $family) }}" class="d-inline"
                                                  onsubmit="return confirm('{{ __('families.delete_confirm') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="{{ __('common.delete') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-house-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                {{ __('families.no_families_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($families->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-center">
                    {{ $families->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
