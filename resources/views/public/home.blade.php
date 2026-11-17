@extends('layouts.public')

@section('title', __('portal.public.home') . ' — ' . __('common.app_name'))

@section('content')
<div class="py-2">
    <!-- Hero Section -->
    <div class="public-hero p-4 p-md-5 mb-5 shadow-sm">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-warning bg-opacity-25 text-warning fw-semibold small mb-3 border border-warning border-opacity-50">
                    <span class="temple-emblem" style="width: 22px; height: 22px; font-size: 0.75rem; background: var(--temple-gold); color: #ffffff;">ॐ</span>
                    <span>{{ __('portal.public.welcome_badge') }}</span>
                </div>
                <h1 class="display-5 fw-bold text-white lh-sm mb-3">
                    {{ __('portal.public.hero_title') }}
                </h1>
                <p class="lead text-light text-opacity-90 mb-4 fs-5" style="max-width: 680px;">
                    {{ __('portal.public.hero_description') }}
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('public.events.index') }}" class="btn btn-warning btn-lg fw-bold px-4 py-2 text-dark shadow-sm">
                        <i class="bi bi-calendar3 me-2"></i> {{ __('portal.public.view_all_events') }}
                    </a>
                    <a href="#templePillars" class="btn btn-outline-light btn-lg fw-semibold px-4 py-2">
                        <i class="bi bi-info-circle me-1"></i> {{ __('common.details') }}
                    </a>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block text-center">
                <div class="p-4 rounded-4 bg-black bg-opacity-25 border border-white border-opacity-10 backdrop-blur">
                    <div class="temple-emblem mx-auto mb-3 shadow-lg" style="width: 72px; height: 72px; font-size: 2.2rem; background: var(--temple-gold); color: #ffffff;">ॐ</div>
                    <h5 class="text-white fw-bold mb-1">{{ __('common.app_name') }}</h5>
                    <small class="text-warning text-opacity-90 d-block mb-3">{{ __('common.temple_motto') }}</small>
                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-3 py-2 rounded-pill small">
                        <i class="bi bi-shield-check me-1"></i> {{ __('portal.public.transparency_badge') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Highlights / Value Pillars -->
    <div class="row g-4 mb-5" id="templePillars">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-4 bg-white border-start border-4 border-danger">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-circle p-2 bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-journal-check fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-0 text-dark">{{ __('portal.public.transparency_badge') }}</h5>
                </div>
                <p class="text-muted small mb-0">{{ __('portal.public.transparency_text') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-4 bg-white border-start border-4 border-warning">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-circle p-2 bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-heart-pulse fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-0 text-dark">{{ __('portal.public.devotee_service_badge') }}</h5>
                </div>
                <p class="text-muted small mb-0">{{ __('portal.public.devotee_service_text') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-3 p-4 bg-white border-start border-4 border-primary">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-circle p-2 bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-translate fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-0 text-dark">{{ __('portal.public.bilingual_badge') }}</h5>
                </div>
                <p class="text-muted small mb-0">{{ __('portal.public.bilingual_text') }}</p>
            </div>
        </div>
    </div>

    <!-- Section B: Running & Active Festivals -->
    <div class="mb-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-stars text-warning me-2"></i>{{ __('portal.public.running_events') }}
                </h3>
                <p class="text-muted small mb-0">{{ __('portal.public.status_active_note') }}</p>
            </div>
            <a href="{{ route('public.events.index', ['status' => 'active']) }}" class="btn btn-sm btn-outline-primary">
                {{ __('common.all') }} <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        @if($runningEvents->isNotEmpty())
            <div class="row g-4">
                @foreach($runningEvents as $event)
                    <div class="col-lg-4 col-md-6">
                        <div class="public-event-card shadow-sm">
                            <!-- Banner Image -->
                            @if($event->banner_image_path)
                                <img src="{{ asset('storage/' . $event->banner_image_path) }}" alt="{{ $event->name }}" class="public-event-banner">
                            @else
                                <div class="public-event-banner-placeholder">
                                    <span class="temple-emblem mb-2" style="width: 44px; height: 44px; font-size: 1.4rem;">ॐ</span>
                                    <span class="fw-bold">{{ $event->category?->name ?? __('events.event') }}</span>
                                    <small class="text-warning">{{ $event->event_year }}</small>
                                </div>
                            @endif

                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <span class="badge bg-success px-2 py-1 small">
                                        <i class="bi bi-circle-fill me-1 small"></i> {{ __('portal.public.active_badge') }}
                                    </span>
                                    <span class="badge bg-light text-secondary border">
                                        {{ $event->category?->name ?? __('events.event') }} — {{ $event->event_year }}
                                    </span>
                                </div>

                                <h5 class="card-title fw-bold text-dark mb-2">
                                    <a href="{{ route('public.events.show', $event->slug) }}" class="text-dark text-decoration-none hover-primary">
                                        {{ $event->name }}
                                    </a>
                                </h5>

                                <div class="small text-muted mb-3 d-flex flex-column gap-1">
                                    <div>
                                        <i class="bi bi-calendar-event text-primary me-1"></i>
                                        {{ $event->start_date?->format('d M Y') }} — {{ $event->end_date?->format('d M Y') }}
                                    </div>
                                    @if($event->venue)
                                        <div>
                                            <i class="bi bi-geo-alt text-danger me-1"></i> {{ $event->venue }}
                                        </div>
                                    @endif
                                </div>

                                <p class="card-text text-secondary small flex-grow-1 mb-4">
                                    {{ $event->short_description ?: \Illuminate\Support\Str::limit($event->description, 130) }}
                                </p>

                                <div class="mt-auto pt-2 border-top">
                                    <a href="{{ route('public.events.show', $event->slug) }}" class="btn temple-btn-maroon w-100 fw-semibold">
                                        {{ __('portal.public.view_event_details') }} <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card border-0 bg-white shadow-sm p-4 text-center rounded-3">
                <div class="text-muted mb-2">
                    <i class="bi bi-calendar-x fs-1 text-secondary opacity-50"></i>
                </div>
                <h6 class="text-dark fw-bold mb-1">{{ __('portal.public.no_running_events') }}</h6>
                <p class="text-muted small mb-0">{{ __('portal.public.status_upcoming_note') }}</p>
            </div>
        @endif
    </div>

    <!-- Section B (cont): Upcoming Events -->
    <div class="mb-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-calendar-check text-primary me-2"></i>{{ __('portal.public.upcoming_events') }}
                </h3>
                <p class="text-muted small mb-0">{{ __('portal.public.status_upcoming_note') }}</p>
            </div>
            <a href="{{ route('public.events.index', ['status' => 'upcoming']) }}" class="btn btn-sm btn-outline-primary">
                {{ __('common.all') }} <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        @if($upcomingEvents->isNotEmpty())
            <div class="row g-4">
                @foreach($upcomingEvents as $event)
                    <div class="col-lg-4 col-md-6">
                        <div class="public-event-card shadow-sm">
                            @if($event->banner_image_path)
                                <img src="{{ asset('storage/' . $event->banner_image_path) }}" alt="{{ $event->name }}" class="public-event-banner">
                            @else
                                <div class="public-event-banner-placeholder" style="background: linear-gradient(135deg, #1e3a8a, #0284c7);">
                                    <span class="temple-emblem mb-2" style="width: 44px; height: 44px; font-size: 1.4rem;">ॐ</span>
                                    <span class="fw-bold">{{ $event->category?->name ?? __('events.event') }}</span>
                                    <small class="text-info">{{ $event->event_year }}</small>
                                </div>
                            @endif

                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <span class="badge bg-primary px-2 py-1 small">
                                        <i class="bi bi-hourglass-split me-1 small"></i> {{ __('portal.public.upcoming_badge') }}
                                    </span>
                                    <span class="badge bg-light text-secondary border">
                                        {{ $event->category?->name ?? __('events.event') }} — {{ $event->event_year }}
                                    </span>
                                </div>

                                <h5 class="card-title fw-bold text-dark mb-2">
                                    <a href="{{ route('public.events.show', $event->slug) }}" class="text-dark text-decoration-none hover-primary">
                                        {{ $event->name }}
                                    </a>
                                </h5>

                                <div class="small text-muted mb-3 d-flex flex-column gap-1">
                                    <div>
                                        <i class="bi bi-calendar-event text-primary me-1"></i>
                                        {{ $event->start_date?->format('d M Y') }} — {{ $event->end_date?->format('d M Y') }}
                                    </div>
                                    @if($event->venue)
                                        <div>
                                            <i class="bi bi-geo-alt text-danger me-1"></i> {{ $event->venue }}
                                        </div>
                                    @endif
                                </div>

                                <p class="card-text text-secondary small flex-grow-1 mb-4">
                                    {{ $event->short_description ?: \Illuminate\Support\Str::limit($event->description, 130) }}
                                </p>

                                <div class="mt-auto pt-2 border-top">
                                    <a href="{{ route('public.events.show', $event->slug) }}" class="btn btn-outline-primary w-100 fw-semibold">
                                        {{ __('portal.public.view_event_details') }} <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card border-0 bg-white shadow-sm p-4 text-center rounded-3">
                <div class="text-muted mb-2">
                    <i class="bi bi-hourglass text-secondary opacity-50 fs-2"></i>
                </div>
                <h6 class="text-dark fw-bold mb-1">{{ __('portal.public.no_upcoming_events') }}</h6>
            </div>
        @endif
    </div>

    <!-- Section C: Completed Events Preview (if available) -->
    @if($completedEvents->isNotEmpty())
        <div class="mb-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">
                        <i class="bi bi-check2-circle text-secondary me-2"></i>{{ __('portal.public.completed_events') }}
                    </h4>
                    <p class="text-muted small mb-0">{{ __('portal.public.status_closed_note') }}</p>
                </div>
                <a href="{{ route('public.events.index', ['status' => 'closed']) }}" class="btn btn-sm btn-outline-secondary">
                    {{ __('common.all') }} <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="row g-3">
                @foreach($completedEvents as $event)
                    <div class="col-lg-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                            <div class="card-body p-3 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-secondary-subtle text-secondary border small">
                                        {{ __('portal.public.closed_badge') }}
                                    </span>
                                    <span class="small text-muted fw-bold">{{ $event->event_year }}</span>
                                </div>
                                <h6 class="fw-bold mb-1">
                                    <a href="{{ route('public.events.show', $event->slug) }}" class="text-dark text-decoration-none">
                                        {{ $event->name }}
                                    </a>
                                </h6>
                                <div class="small text-muted mb-3">
                                    {{ $event->category?->name ?? __('events.event') }}
                                </div>
                                <div class="mt-auto pt-2 border-top">
                                    <a href="{{ route('public.events.show', $event->slug) }}" class="btn btn-sm btn-light border w-100 text-secondary">
                                        {{ __('portal.public.view_event_details') }} <i class="bi bi-arrow-right small ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Section D: Call To Action -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 text-center bg-white border-top border-4 border-warning">
        <div class="temple-emblem mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.6rem; background: var(--temple-gold); color: #ffffff;">ॐ</div>
        <h3 class="fw-bold text-dark mb-2">{{ __('common.app_name') }}</h3>
        <p class="text-muted mx-auto mb-4" style="max-width: 620px;">
            {{ __('portal.public.hero_description') }}
        </p>
        <div>
            <a href="{{ route('public.events.index') }}" class="btn temple-btn-maroon btn-lg px-4 fw-bold">
                <i class="bi bi-calendar3 me-2"></i> {{ __('portal.public.view_all_events') }}
            </a>
        </div>
    </div>
</div>
@endsection
