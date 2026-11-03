@extends('layouts.app')

@section('title', __('portal.public_events') . ' - ' . $event->name)

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('portal.events.index') }}">{{ __('portal.public_events') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $event->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-globe text-primary me-2"></i>{{ $event->name }}
            </h1>
        </div>
        <div class="d-flex gap-2">
            @if(Auth::user()->hasPermission('manage_public_events'))
                <a href="{{ route('portal.events.edit', $event) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-gear me-1"></i> {{ __('portal.configure_public_info') }}
                </a>
            @endif
            @if(Auth::user()->hasPermission('publish_public_events'))
                @if($event->isPublished())
                    <form action="{{ route('portal.events.unpublish', $event) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning btn-sm" onclick="return confirm('{{ __('portal.unpublish_confirmation') }}')">
                            <i class="bi bi-eye-slash me-1"></i> {{ __('portal.unpublish') }}
                        </button>
                    </form>
                @else
                    <form action="{{ route('portal.events.publish', $event) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('{{ __('portal.publish_confirmation') }}')">
                            <i class="bi bi-globe me-1"></i> {{ __('portal.publish') }}
                        </button>
                    </form>
                @endif
            @endif
            <a href="{{ route('portal.events.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Banner Display -->
    @if($event->banner_image_path)
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div style="height: 240px; background: #000;">
                <img src="{{ asset('storage/' . $event->banner_image_path) }}" alt="{{ $event->name }}"
                     style="width: 100%; height: 100%; object-fit: cover; opacity: 0.95;">
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Details -->
        <div class="col-lg-8">
            <!-- Public Card Preview -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-eye me-1 text-primary"></i> {{ __('portal.public_preview') }}
                    </h6>
                    <span class="badge {{ $event->isPublished() ? 'bg-success' : 'bg-secondary' }}">
                        {{ __('portal.' . $event->publication_status) }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-light text-dark border">{{ $event->category?->name_en ?? 'Event' }}</span>
                        <span class="text-muted small">&bull;</span>
                        <span class="text-muted small">{{ $event->event_year }}</span>
                        <span class="text-muted small">&bull;</span>
                        <span class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $event->venue ?: 'Main Temple' }}</span>
                    </div>

                    <h4 class="fw-bold text-dark mb-2">{{ $event->name }}</h4>
                    <p class="text-muted font-monospace small mb-3">
                        <i class="bi bi-link-45deg me-1"></i>/events/<strong class="text-primary">{{ $event->slug ?: 'not-configured' }}</strong>
                    </p>

                    @if($event->short_description)
                        <div class="p-3 bg-light rounded-3 border-start border-4 border-primary mb-3">
                            <div class="small fw-bold text-uppercase text-muted mb-1">{{ __('portal.short_description') }}</div>
                            <p class="mb-0 text-dark">{{ $event->short_description }}</p>
                        </div>
                    @endif

                    @if($event->description)
                        <div class="mt-3">
                            <div class="small fw-bold text-uppercase text-muted mb-1">{{ __('portal.detailed_description') }}</div>
                            <div class="text-dark small lh-lg" style="white-space: pre-line;">{{ $event->description }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Metadata & Status Sidebar -->
        <div class="col-lg-4">
            <!-- Publication Info Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-broadcast me-1 text-primary"></i> {{ __('portal.publication_status') }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded mb-3 {{ $event->isPublished() ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }}">
                        <div class="fw-bold">
                            <i class="bi {{ $event->isPublished() ? 'bi-check-circle-fill' : 'bi-eye-slash-fill' }} me-2"></i>
                            {{ __('portal.' . $event->publication_status) }}
                        </div>
                        <span class="badge {{ $event->isPublished() ? 'bg-success' : 'bg-secondary' }}">
                            {{ strtoupper($event->publication_status) }}
                        </span>
                    </div>

                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('portal.slug') }}:</span>
                            <span class="font-monospace fw-bold text-primary">{{ $event->slug ?: '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('portal.published_at') }}:</span>
                            <span>{{ $event->published_at ? $event->published_at->format('d M Y, h:i A') : '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('portal.published_by') }}:</span>
                            <span>{{ $event->publisher?->name ?? '—' }}</span>
                        </li>
                        @if($event->unpublished_at)
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">{{ __('portal.unpublished_at') }}:</span>
                                <span>{{ $event->unpublished_at->format('d M Y, h:i A') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">{{ __('portal.unpublished_by') }}:</span>
                                <span>{{ $event->unpublisher?->name ?? '—' }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <!-- Liturgical & Collection Invariants Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-shield-check me-1 text-success"></i> {{ __('events.event_details') }}
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('events.event_code') }}:</span>
                            <span class="font-monospace fw-bold">{{ $event->event_code }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('portal.liturgical_status') }}:</span>
                            <span class="badge bg-light text-dark border">{{ $event->status }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('events.event_duration') }}:</span>
                            <span>{{ $event->start_date->format('d M Y') }} — {{ $event->end_date->format('d M Y') }}</span>
                        </li>
                        @if($event->hasCollectionPeriod())
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">{{ __('events.collection_period') }}:</span>
                                <span>{{ $event->collection_start_date->format('d M Y') }} — {{ $event->collection_end_date->format('d M Y') }}</span>
                            </li>
                        @endif
                    </ul>
                    <div class="mt-3 pt-3 border-top">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('events.view_details') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
