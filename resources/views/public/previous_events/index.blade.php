@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', __('portal.public.archive_title') . ' — ' . __('common.app_name'))

@section('content')
<div class="py-2">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 small bg-white p-2 px-3 rounded-3 shadow-sm border">
            <li class="breadcrumb-item">
                <a href="{{ auth()->check() ? route('devotee.dashboard') : route('home') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> {{ auth()->check() ? __('devotee.my_dashboard') : __('portal.public.home') }}
                </a>
            </li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                {{ __('portal.public.archive_title') }}
            </li>
        </ol>
    </nav>

    <!-- Page Header & Filter Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        <i class="bi bi-journal-text text-warning me-2"></i>{{ __('portal.public.archive_title') }}
                    </h2>
                    <p class="text-muted small mb-0">{{ __('portal.public.archive_subtitle') }}</p>
                </div>
                <div class="text-md-end">
                    <span class="badge bg-light text-dark border px-3 py-2 fs-6">
                        <i class="bi bi-calendar-check me-1 text-primary"></i>
                        {{ $events->total() }} {{ __('events.events') }}
                    </span>
                </div>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('public.previous-events.index') }}" method="GET" class="row g-2 align-items-end pt-2 border-top">
                <!-- Search Filter -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small text-muted fw-semibold mb-1">{{ __('common.search') }}</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('portal.public.search_events') }}" value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="col-md-2 col-sm-6">
                    <label class="form-label small text-muted fw-semibold mb-1">{{ __('portal.public.filter_by_category') }}</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">-- {{ __('portal.public.all_categories') }} --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Year Filter -->
                <div class="col-md-2 col-sm-6">
                    <label class="form-label small text-muted fw-semibold mb-1">{{ __('portal.public.financial_year') }}</label>
                    <select name="year" class="form-select form-select-sm">
                        <option value="">-- {{ __('portal.public.all_years') }} --</option>
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Report Category Filter -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small text-muted fw-semibold mb-1">{{ __('portal.public.report_category') }}</label>
                    <select name="report_category" class="form-select form-select-sm">
                        <option value="">-- {{ __('portal.public.all_report_categories') }} --</option>
                        @foreach($reportCategories as $key => $label)
                            <option value="{{ $key }}" {{ request('report_category') === $key ? 'selected' : '' }}>
                                {{ __('portal.report_categories.' . $key) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Actions -->
                <div class="col-md-2 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-sm temple-btn-maroon flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> {{ __('portal.public.filter_button') }}
                    </button>
                    @if(request()->hasAny(['search', 'category_id', 'year', 'report_category']))
                        <a href="{{ route('public.previous-events.index') }}" class="btn btn-sm btn-outline-secondary" title="{{ __('portal.public.reset_filter') }}">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Events Grid -->
    @if($events->isNotEmpty())
        <div class="row g-4 mb-4">
            @foreach($events as $event)
                <div class="col-lg-4 col-md-6 d-flex">
                    <div class="public-event-card shadow-sm w-100">
                        <!-- Banner Image (Uploaded or Deity Specific) -->
                        <div class="public-event-banner-wrapper">
                            <img src="{{ $event->featured_image_url }}" alt="{{ $event->name }}" class="public-event-banner" loading="lazy">
                            <div class="public-event-banner-badge">
                                <span class="badge bg-dark bg-opacity-75 text-warning border border-warning border-opacity-50 px-2 py-1 shadow-sm">
                                    {{ $event->event_year }}
                                </span>
                            </div>
                        </div>

                        <div class="public-event-card-body">
                            <!-- Badges -->
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2 flex-wrap">
                                <div>
                                    @if($event->status === 'closed')
                                        <span class="badge bg-secondary px-2 py-1 small">
                                            <i class="bi bi-check-circle me-1 small"></i> {{ __('portal.public.closed_badge') }}
                                        </span>
                                    @elseif($event->status === 'closing')
                                        <span class="badge bg-warning text-dark px-2 py-1 small">
                                            <i class="bi bi-clock-history me-1 small"></i> {{ __('events.statuses.closing') }}
                                        </span>
                                    @else
                                        <span class="badge bg-info text-dark px-2 py-1 small">
                                            <i class="bi bi-calendar-event me-1 small"></i> {{ $event->status_label }}
                                        </span>
                                    @endif

                                    @if($event->settlement && $event->settlement->isCompleted())
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small ms-1" title="{{ __('portal.public.settlement_notice') }}">
                                            <i class="bi bi-shield-check me-1 small"></i> {{ __('portal.public.settled_badge') }}
                                        </span>
                                    @endif
                                </div>

                                <span class="badge bg-light text-secondary border text-truncate" style="max-width: 50%;" title="{{ $event->category?->name ?? __('events.event') }}">
                                    {{ $event->category?->name ?? __('events.event') }}
                                </span>
                            </div>

                            <!-- Event Title -->
                            <h5 class="public-event-card-title text-dark">
                                <a href="{{ route('public.previous-events.show', $event->slug) }}" class="text-dark text-decoration-none hover-primary" title="{{ $event->name }}">
                                    {{ $event->name }}
                                </a>
                            </h5>

                            <!-- Dates & Venue -->
                            <div class="public-event-meta">
                                <div class="public-event-meta-item" title="{{ $event->start_date?->format('d M Y') }} — {{ $event->end_date?->format('d M Y') }}">
                                    <i class="bi bi-calendar-event text-primary me-1"></i>
                                    {{ $event->start_date?->format('d M Y') }} — {{ $event->end_date?->format('d M Y') }}
                                </div>
                                <div class="public-event-meta-item" title="{{ $event->venue ?: __('events.temple_premises') }}">
                                    <i class="bi bi-geo-alt text-danger me-1"></i>
                                    {{ $event->venue ?: __('events.temple_premises') }}
                                </div>
                            </div>

                            <!-- Short Summary / Details (Clamped) -->
                            <p class="public-event-desc" title="{{ $event->short_description ?: $event->description }}">
                                {{ $event->short_description ?: ($event->description ? \Illuminate\Support\Str::limit($event->description, 130) : __('events.no_description')) }}
                            </p>

                            <!-- Document Count Badge -->
                            <div class="mb-3">
                                @if($event->published_documents_count > 0)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                                        <i class="bi bi-file-earmark-pdf me-1"></i>
                                        {{ trans_choice('portal.public.published_documents_count', $event->published_documents_count, ['count' => $event->published_documents_count]) }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1 small">
                                        <i class="bi bi-journal-text me-1"></i> {{ __('portal.public.system_financial_summary') }}
                                    </span>
                                @endif
                            </div>

                            <!-- View Financial Reports Button (Pinned to Bottom) -->
                            <div class="public-event-card-footer">
                                <a href="{{ route('public.previous-events.show', $event->slug) }}" class="btn temple-btn-maroon w-100 fw-semibold">
                                    <i class="bi bi-journal-check me-1"></i> {{ __('portal.public.view_financial_reports') }} <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center my-4">
            {{ $events->links('pagination::bootstrap-5') }}
        </div>
    @else
        <!-- Empty State -->
        <div class="card border-0 bg-white shadow-sm p-5 text-center rounded-3 my-4">
            <div class="text-muted mb-3">
                <i class="bi bi-journal-x fs-1 text-secondary opacity-50"></i>
            </div>
            <h4 class="text-dark fw-bold mb-2">{{ __('portal.public.no_archive_events_found') }}</h4>
            <p class="text-muted small mb-4">
                {{ __('portal.public.no_events_found') }}
            </p>
            @if(request()->hasAny(['search', 'category_id', 'year', 'report_category']))
                <div>
                    <a href="{{ route('public.previous-events.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('portal.public.reset_filter') }}
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
