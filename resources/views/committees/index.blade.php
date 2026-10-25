@extends('layouts.app')

@section('title', __('committees.committees'))

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('committees.committees') }}</h1>
            <p class="text-muted small mb-0">{{ __('committees.committee_management') }}</p>
        </div>

        @if(Auth::user()->hasPermission('manage_committee'))
            <div>
                <a href="{{ route('committees.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                    <i class="bi bi-plus-circle-fill me-1"></i> {{ __('committees.add_committee') }}
                </a>
            </div>
        @endif
    </div>

    <!-- Filter Buttons -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="{{ route('committees.index') }}" class="btn btn-sm {{ empty($status) ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill px-3">
            {{ __('common.all') }}
        </a>
        <a href="{{ route('committees.index', ['status' => 'active']) }}" class="btn btn-sm {{ $status === 'active' ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill px-3">
            {{ __('committees.status_active') }}
        </a>
        <a href="{{ route('committees.index', ['status' => 'inactive']) }}" class="btn btn-sm {{ $status === 'inactive' ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill px-3">
            {{ __('committees.status_inactive') }}
        </a>
        <a href="{{ route('committees.index', ['status' => 'dissolved']) }}" class="btn btn-sm {{ $status === 'dissolved' ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill px-3">
            {{ __('committees.status_dissolved') }}
        </a>
    </div>

    <!-- Committees Grid -->
    <div class="row g-4">
        @forelse($committees as $committee)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <span class="badge bg-light text-dark font-monospace border">{{ $committee->code }}</span>
                        @if($committee->status === 'active')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                {{ __('committees.status_active') }}
                            </span>
                        @elseif($committee->status === 'inactive')
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                {{ __('committees.status_inactive') }}
                            </span>
                        @else
                            <span class="badge bg-dark bg-opacity-10 text-dark border">
                                {{ __('committees.status_dissolved') }}
                            </span>
                        @endif
                    </div>
                    <div class="card-body p-4 d-flex flex-column">
                        <h5 class="fw-bold text-dark mb-1">
                            <a href="{{ route('committees.show', $committee) }}" class="text-decoration-none text-dark">
                                {{ $committee->name_en }}
                            </a>
                        </h5>
                        @if($committee->name_bn)
                            <p class="text-muted small mb-2">{{ $committee->name_bn }}</p>
                        @endif

                        <p class="text-secondary small mb-3 flex-grow-1" style="font-size: 0.85rem;">
                            {{ Str::limit($committee->description_en, 100) }}
                        </p>

                        <div class="bg-light rounded-3 p-3 mb-3 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Term:</span>
                                <span class="fw-semibold">
                                    {{ $committee->term_start_date ? $committee->term_start_date->format('M Y') : 'Open' }} &ndash;
                                    {{ $committee->term_end_date ? $committee->term_end_date->format('M Y') : 'Present' }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Active Members:</span>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                    {{ $committee->active_members_count }} Active
                                </span>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('committees.show', $committee) }}" class="btn btn-outline-primary w-100 rounded-pill btn-sm">
                                <i class="bi bi-eye me-1"></i> {{ __('common.view') }}
                            </a>
                            @if(Auth::user()->hasPermission('manage_committee'))
                                <a href="{{ route('committees.edit', $committee) }}" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-award fs-1 d-block mb-2 text-secondary opacity-50"></i>
                No committees found matching your filter.
            </div>
        @endforelse
    </div>

    @if($committees->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $committees->links() }}
        </div>
    @endif
</div>
@endsection
