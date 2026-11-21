@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', $event->name . ' (' . $event->event_year . ') — ' . __('common.app_name'))

@section('content')
<div class="py-2">
    <!-- Breadcrumb & Back Link -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small bg-white p-2 px-3 rounded-3 shadow-sm border">
                <li class="breadcrumb-item">
                    <a href="{{ auth()->check() ? route('devotee.dashboard') : route('home') }}" class="text-decoration-none text-muted">
                        <i class="bi bi-house-door me-1"></i> {{ auth()->check() ? __('devotee.my_dashboard') : __('portal.public.home') }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('public.events.index') }}" class="text-decoration-none text-muted">
                        {{ __('portal.public.events') }}
                    </a>
                </li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                    {{ $event->name }}
                </li>
            </ol>
        </nav>

        <a href="{{ route('public.events.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> {{ __('portal.public.back_to_events') }}
        </a>
    </div>

    <!-- Main Event Showcase Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
        <!-- Banner Image (Uploaded or Themed Deity Image) -->
        <div class="position-relative" style="max-height: 400px; overflow: hidden; background: #1e1b18;">
            <img src="{{ $event->featured_image_url }}" alt="{{ $event->name }}" class="w-100" style="max-height: 400px; object-fit: cover; object-position: center;">
            <div class="position-absolute top-0 end-0 m-3">
                <span class="badge bg-dark bg-opacity-75 text-warning border border-warning border-opacity-50 px-3 py-2 fs-6 shadow-sm">
                    {{ $event->event_year }}
                </span>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            <!-- Header Badges & Title -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                @if($event->status === 'active' || $event->status === 'closing')
                    <span class="badge bg-success px-3 py-2 fs-6">
                        <i class="bi bi-circle-fill me-1 small"></i> {{ __('portal.public.active_badge') }}
                    </span>
                @elseif($event->status === 'upcoming')
                    <span class="badge bg-primary px-3 py-2 fs-6">
                        <i class="bi bi-hourglass-split me-1 small"></i> {{ __('portal.public.upcoming_badge') }}
                    </span>
                @else
                    <span class="badge bg-secondary px-3 py-2 fs-6">
                        <i class="bi bi-check-circle me-1 small"></i> {{ __('portal.public.closed_badge') }}
                    </span>
                @endif

                <span class="badge bg-light text-secondary border px-3 py-2 fs-6">
                    <i class="bi bi-tag me-1"></i> {{ $event->category?->name ?? __('events.event') }}
                </span>

                <span class="badge bg-light text-secondary border px-3 py-2 fs-6">
                    <i class="bi bi-calendar-check me-1"></i> {{ $event->event_year }}
                </span>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <h1 class="display-6 fw-bold text-dark mb-0">
                    {{ $event->name }}
                </h1>
                @if($event->status !== 'closed')
                    <a href="{{ route('public.events.pay', $event->slug) }}" class="btn temple-btn-maroon shadow-sm px-4 py-2 fw-semibold align-self-start align-self-md-auto">
                        <i class="bi bi-wallet2 me-1"></i> {{ __('portal.public.make_payment_btn') }}
                    </a>
                @endif
            </div>

            @if($event->short_description)
                <p class="lead text-secondary mb-4 fs-5">
                    {{ $event->short_description }}
                </p>
            @endif

            <!-- Collection & Contribution Summary Section -->
            <div class="card border-0 rounded-4 bg-light p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">
                            <i class="bi bi-wallet2 text-warning me-2"></i>{{ __('portal.public.collection_summary_title') }}
                        </h4>
                        <p class="text-muted small mb-0">{{ __('portal.public.verified_notice') }}</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($summary['is_over_collected'])
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 fs-6">
                                <i class="bi bi-check2-all me-1"></i> {{ __('portal.public.over_collected_badge') }} (+৳{{ number_format($summary['over_collected_amount'], 2) }})
                            </span>
                        @endif
                        @if($event->status !== 'closed')
                            <a href="{{ route('public.events.pay', $event->slug) }}" class="btn btn-outline-danger shadow-sm btn-sm px-3 py-2 fw-semibold">
                                <i class="bi bi-wallet2 me-1"></i> {{ __('portal.public.make_payment_btn') }}
                            </a>
                        @endif
                    </div>
                </div>

                <!-- 4 Summary Cards Grid -->
                <div class="row g-3 mb-4">
                    <!-- Card 1: Total Pledged (মোট প্রতিশ্রুত চাঁদা) -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 bg-white rounded-3 shadow-sm border h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-muted fw-semibold">{{ __('portal.public.total_pledged') }}</span>
                                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-2 lh-1">
                                    <i class="bi bi-bookmark-check fs-5"></i>
                                </div>
                            </div>
                            <div class="fs-4 fw-bold text-dark font-monospace">৳{{ number_format($summary['total_pledged'], 2) }}</div>
                            <div class="small text-muted mt-1">{{ __('portal.public.pledged_target') }}</div>
                        </div>
                    </div>

                    <!-- Card 2: Total Verified Collection (মোট যাচাইকৃত আদায়) -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 bg-white rounded-3 shadow-sm border h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-muted fw-semibold">{{ __('portal.public.total_verified_collection') }}</span>
                                <div class="bg-success bg-opacity-10 text-success p-2 rounded-2 lh-1">
                                    <i class="bi bi-check2-circle fs-5"></i>
                                </div>
                            </div>
                            <div class="fs-4 fw-bold text-success font-monospace">৳{{ number_format($summary['total_verified_collections'], 2) }}</div>
                            <div class="small text-success mt-1">
                                <i class="bi bi-shield-check me-1"></i>{{ __('portal.public.verified_collection_badge') }}
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Total Due (মোট বকেয়া) -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 bg-white rounded-3 shadow-sm border h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-muted fw-semibold">{{ __('portal.public.total_due') }}</span>
                                <div class="bg-danger bg-opacity-10 text-danger p-2 rounded-2 lh-1">
                                    <i class="bi bi-clock-history fs-5"></i>
                                </div>
                            </div>
                            <div class="fs-4 fw-bold {{ $summary['total_due'] > 0 ? 'text-danger' : 'text-muted' }} font-monospace">
                                ৳{{ number_format($summary['total_due'], 2) }}
                            </div>
                            <div class="small text-muted mt-1">{{ __('portal.public.remaining_due_badge') }}</div>
                        </div>
                    </div>

                    <!-- Card 4: Collection Progress (চাঁদা সংগ্রহের অগ্রগতি) -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 bg-white rounded-3 shadow-sm border h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-muted fw-semibold">{{ __('portal.public.collection_progress') }}</span>
                                <div class="bg-info bg-opacity-10 text-info p-2 rounded-2 lh-1">
                                    <i class="bi bi-graph-up-arrow fs-5"></i>
                                </div>
                            </div>
                            <div class="fs-4 fw-bold text-primary font-monospace">
                                @if($summary['has_pledges'])
                                    {{ rtrim(rtrim(number_format($summary['progress'], 1), '0'), '.') }}%
                                @else
                                    {{ __('portal.public.not_applicable') }}
                                @endif
                            </div>
                            <div class="small text-muted mt-1">{{ __('portal.public.progress_rate_badge') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Large Collection Progress Section -->
                <div class="bg-white p-3 p-md-4 rounded-3 shadow-sm border">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold text-dark">
                            <i class="bi bi-speedometer2 text-primary me-2"></i>{{ __('portal.public.collection_progress_label') }}
                        </span>
                        <span class="fw-bold font-monospace text-dark fs-6">
                            @if($summary['has_pledges'])
                                {{ rtrim(rtrim(number_format($summary['progress'], 1), '0'), '.') }}%
                                <span class="text-muted fw-normal small">(৳{{ number_format($summary['verified_pledge_collections'], 2) }} / ৳{{ number_format($summary['total_pledged'], 2) }})</span>
                            @else
                                <span class="badge bg-secondary">{{ __('portal.public.not_applicable') }}</span>
                            @endif
                        </span>
                    </div>

                    <div class="progress" style="height: 24px; border-radius: 12px;" role="progressbar" aria-label="{{ __('portal.public.collection_progress') }}" aria-valuenow="{{ $summary['progress_bar_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" style="width: {{ $summary['progress_bar_percentage'] }}%;">
                            @if($summary['has_pledges'] && $summary['progress_bar_percentage'] >= 10)
                                {{ rtrim(rtrim(number_format($summary['progress'], 1), '0'), '.') }}%
                            @endif
                        </div>
                    </div>

                    @if(! $summary['has_pledges'])
                        <div class="mt-2 text-muted small">
                            <i class="bi bi-info-circle me-1"></i> {{ __('portal.public.zero_pledge_notice') }}
                        </div>
                    @elseif($summary['is_over_collected'])
                        <div class="mt-2 text-success small fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> {{ __('portal.public.extra_contribution_credited') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Key Information Cards Grid -->
            <div class="row g-3 my-4">
                <!-- Festival Dates -->
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-light border h-100">
                        <div class="text-muted small mb-1">
                            <i class="bi bi-calendar3 text-primary me-1"></i> {{ __('portal.public.event_dates') }}
                        </div>
                        <div class="fw-bold text-dark fs-6">
                            {{ $event->start_date?->format('d M Y') }} — {{ $event->end_date?->format('d M Y') }}
                        </div>
                    </div>
                </div>

                <!-- Venue / Location -->
                @if($event->venue)
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 bg-light border h-100">
                            <div class="text-muted small mb-1">
                                <i class="bi bi-geo-alt text-danger me-1"></i> {{ __('portal.public.venue') }}
                            </div>
                            <div class="fw-bold text-dark fs-6">
                                {{ $event->venue }}
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Collection Period (if available) -->
                @if($event->hasCollectionPeriod())
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 bg-light border h-100">
                            <div class="text-muted small mb-1">
                                <i class="bi bi-clock-history text-warning me-1"></i> {{ __('portal.public.collection_period') }}
                            </div>
                            <div class="fw-bold text-dark fs-6">
                                {{ $event->collection_start_date?->format('d M Y') }} — {{ $event->collection_end_date?->format('d M Y') }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Detailed Description Section -->
            @if($event->description)
                <div class="mt-4 pt-3 border-top">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="bi bi-file-text text-secondary me-2"></i>{{ __('portal.public.event_description') }}
                    </h5>
                    <div class="text-secondary lh-lg fs-6" style="white-space: pre-line;">
                        {{ $event->description }}
                    </div>
                </div>
            @endif

            <!-- Donor-wise Running Collection Report Section -->
            <div class="card border-0 rounded-4 bg-white shadow-sm border p-4 my-4" id="donor-collection-report">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">
                            <i class="bi bi-people text-primary me-2"></i>{{ __('portal.public.donor_report_title') }}
                        </h4>
                        <p class="text-muted small mb-0">{{ __('portal.public.donor_report_subtitle') }}</p>
                    </div>

                    <!-- Search and Filter Controls -->
                    <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center flex-grow-1 justify-content-lg-end" style="max-width: 650px;">
                        <div class="flex-grow-1 donor-search-select2-wrap" style="min-width: 280px;">
                            <select id="donorSearchInput" class="form-select" style="width: 100%;" data-placeholder="{{ __('portal.public.filter_search_placeholder') }}">
                                <option value=""></option>
                            </select>
                        </div>

                        <select id="donorStatusFilter" class="form-select flex-shrink-0" style="width: auto; min-width: 140px;">
                            <option value="all">{{ __('portal.public.status_all') }}</option>
                            <option value="paid">{{ __('portal.public.status_paid') }}</option>
                            <option value="partial">{{ __('portal.public.status_partial') }}</option>
                            <option value="due">{{ __('portal.public.status_due') }}</option>
                            <option value="voluntary">{{ __('portal.public.status_voluntary') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Donor Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="donorReportTable">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th scope="col" class="py-3 text-start" style="width: 20%;">{{ __('portal.public.table_donor_name') }}</th>
                                <th scope="col" class="py-3 text-start" style="width: 20%;">{{ __('portal.public.table_pledged') }}</th>
                                <th scope="col" class="py-3 text-start" style="width: 20%;">{{ __('portal.public.table_paid') }}</th>
                                <th scope="col" class="py-3 text-start" style="width: 20%;">{{ __('portal.public.table_status') }}</th>
                                <th scope="col" class="py-3 text-center" style="width: 20%;">{{ __('portal.public.table_action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($donorReport as $row)
                                <tr class="donor-row"
                                    data-status="{{ $row['status'] }}"
                                    data-name="{{ mb_strtolower($row['display_name']) }}"
                                    data-phone="{{ mb_strtolower($row['phone'] ?? '') }}"
                                    data-code="{{ mb_strtolower($row['code'] ?? '') }}"
                                    data-name-en="{{ mb_strtolower($row['raw_name_en'] ?? '') }}"
                                    data-name-bn="{{ mb_strtolower($row['raw_name_bn'] ?? '') }}">
                                    <!-- Name & Phone with Devotee Avatar -->
                                    <td class="py-3 text-start">
                                        <div class="d-flex align-items-center" style="gap: 16px;">
                                            <div class="donor-avatar rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 {{ $row['is_anonymous'] || $row['is_private'] ? 'bg-secondary bg-opacity-10 text-secondary' : 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20' }}" style="width: 40px; height: 40px;">
                                                @if($row['is_anonymous'])
                                                    <i class="bi bi-incognito fs-5"></i>
                                                @elseif($row['is_private'])
                                                    <i class="bi bi-shield-lock fs-5"></i>
                                                @else
                                                    <i class="bi bi-person-circle fs-5"></i>
                                                @endif
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="fw-bold text-dark text-truncate lh-sm" style="font-size: 0.95rem;">
                                                    @if($row['is_anonymous'] || $row['is_private'])
                                                        <span class="text-muted fst-italic">
                                                            {{ $row['display_name'] }}
                                                        </span>
                                                    @else
                                                        <span>{{ $row['display_name'] }}</span>
                                                    @endif
                                                </div>
                                                <div class="small mt-1 text-muted text-truncate">
                                                    @if(!empty($row['phone']))
                                                        <span class="d-inline-flex align-items-center text-secondary font-monospace">
                                                            <i class="bi bi-telephone text-primary me-1"></i>{{ $row['phone'] }}
                                                        </span>
                                                    @elseif($row['is_anonymous'] || $row['is_private'])
                                                        <span class="badge bg-light text-muted border border-secondary border-opacity-25 py-0.5 px-1.5" style="font-size: 0.72rem;">
                                                            <i class="bi bi-shield-lock me-1"></i>{{ __('portal.public.hidden_for_privacy') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted font-monospace">—</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Amount / Pledged -->
                                    <td class="py-3 text-start font-monospace">
                                        @if($row['pledged_amount'] > 0)
                                            <div class="fw-bold text-dark fs-6">
                                                ৳{{ number_format($row['pledged_amount'], 2) }}
                                            </div>
                                            <small class="text-muted d-block" style="font-size: 0.72rem; letter-spacing: 0.02em;">{{ __('portal.public.table_pledged') }}</small>
                                        @else
                                            <span class="badge bg-light text-secondary border border-secondary border-opacity-25 py-1 px-2">
                                                <i class="bi bi-heart me-1 text-danger"></i>{{ __('portal.public.status_voluntary') }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Paid -->
                                    <td class="py-3 text-start font-monospace">
                                        <div class="fw-bold text-success fs-6">
                                            ৳{{ number_format($row['verified_paid'], 2) }}
                                        </div>
                                        @if(!empty($row['is_excess']))
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 mt-1" style="font-size: 0.72rem;">
                                                <i class="bi bi-arrow-up-circle me-1"></i>{{ __('donations.excess') ?? 'Excess' }}
                                            </span>
                                        @else
                                            <small class="text-success d-inline-flex align-items-center mt-0.5" style="font-size: 0.72rem; font-weight: 500;">
                                                <i class="bi bi-check2-circle me-1"></i>{{ __('portal.public.verified_collection_badge') }}
                                            </small>
                                        @endif
                                    </td>

                                    <!-- Status (Progress & Badges) -->
                                    <td class="py-3 text-start">
                                        <div class="d-flex flex-column gap-1">
                                            <div class="d-flex align-items-center gap-2">
                                                @if($row['progress'] !== null)
                                                    <div class="progress flex-grow-1" style="height: 7px; min-width: 65px; max-width: 95px; border-radius: 999px; background-color: #f1f5f9; box-shadow: inset 0 1px 2px rgba(0,0,0,0.06);" role="progressbar" aria-valuenow="{{ $row['progress_bar_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                                                        <div class="progress-bar {{ $row['status_color'] }} rounded-pill" style="width: {{ $row['progress_bar_percentage'] }}%;"></div>
                                                    </div>
                                                    <span class="small font-monospace fw-bold {{ $row['status_color'] === 'bg-success' ? 'text-success' : 'text-danger' }}">
                                                        {{ rtrim(rtrim(number_format($row['progress'], 1), '0'), '.') }}%
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border py-1 px-2 font-monospace">
                                                        {{ __('portal.public.not_applicable') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                @if($row['status'] === 'paid')
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2 rounded-pill fw-semibold" style="font-size: 0.74rem;">
                                                        <i class="bi bi-check-circle-fill me-1"></i>{{ __('portal.public.status_paid') }}
                                                    </span>
                                                @elseif($row['status'] === 'partial')
                                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 py-1 px-2 rounded-pill fw-semibold" style="font-size: 0.74rem;">
                                                        <i class="bi bi-hourglass-split me-1 text-warning"></i>{{ __('portal.public.status_partial') }}
                                                    </span>
                                                    @if($row['due_amount'] > 0)
                                                        <small class="text-danger font-monospace fw-semibold" style="font-size: 0.73rem;">
                                                            (৳{{ number_format($row['due_amount'], 2) }})
                                                        </small>
                                                    @endif
                                                @elseif($row['status'] === 'due')
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2 rounded-pill fw-semibold" style="font-size: 0.74rem;">
                                                        <i class="bi bi-exclamation-circle-fill me-1"></i>{{ __('portal.public.status_due') }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-info bg-opacity-10 text-primary border border-info border-opacity-25 py-1 px-2 rounded-pill fw-semibold" style="font-size: 0.74rem;">
                                                        <i class="bi bi-heart-fill me-1 text-danger"></i>{{ __('portal.public.status_voluntary') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Action (Center-Aligned) -->
                                    <td class="py-3 text-center">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <a href="{{ auth()->check() ? route('devotee.payments.index') : route('login') }}" 
                                               class="btn btn-sm btn-outline-primary rounded-3 btn-donor-action d-inline-flex align-items-center justify-content-center shadow-xs" 
                                               style="width: 38px; height: 38px; transition: all 0.2s ease-in-out;"
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top"
                                               title="{{ __('portal.public.login') }}"
                                               data-bs-title="{{ __('portal.public.login') }}"
                                               aria-label="{{ __('portal.public.login') }}">
                                                <i class="bi bi-box-arrow-in-right fs-5"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                        {{ __('portal.public.no_donors_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Festival Status Note -->
            <div class="alert {{ $event->status === 'active' || $event->status === 'closing' ? 'alert-success' : ($event->status === 'upcoming' ? 'alert-primary' : 'alert-secondary') }} mt-4 mb-0">
                <i class="bi bi-info-circle me-2"></i>
                @if($event->status === 'active' || $event->status === 'closing')
                    {{ __('portal.public.status_active_note') }}
                @elseif($event->status === 'upcoming')
                    {{ __('portal.public.status_upcoming_note') }}
                @else
                    {{ __('portal.public.status_closed_note') }}
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Donor Details Offcanvas (Right Side) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="donorDetailsOffcanvas" aria-labelledby="donorDetailsOffcanvasLabel" style="width: 440px;">
    <div class="offcanvas-header border-bottom bg-light">
        <h5 class="offcanvas-title fw-bold text-dark" id="donorDetailsOffcanvasLabel">
            <i class="bi bi-person-check text-primary me-2"></i>{{ __('portal.public.donor_details_title') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4" id="donorDetailsContent">
        <div class="text-center py-5" id="donorDetailsSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<!-- Donor Complaint / Inquiry Modal -->
<div class="modal fade" id="donorComplaintModal" tabindex="-1" aria-labelledby="donorComplaintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="donorComplaintModalLabel">
                    <i class="bi bi-flag text-warning me-2"></i>{{ __('portal.public.complaint_modal_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="donorComplaintForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="pledge_code" id="complaintPledgeCode" value="">
                
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> {{ __('portal.public.complaint_notice') }}
                    </div>

                    <div id="complaintModalTargetNameWrap" class="p-2 mb-3 bg-light rounded-3 border d-none">
                        <span class="small text-muted d-block">{{ __('portal.public.table_donor_name') }}:</span>
                        <strong class="text-dark" id="complaintModalTargetName"></strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">{{ __('portal.public.complainant_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="complainant_name" class="form-control" required maxlength="255" value="{{ auth()->user()?->name }}">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">{{ __('portal.public.complainant_phone') }} <span class="text-danger">*</span></label>
                            <input type="text" name="complainant_phone" class="form-control" required maxlength="30" value="{{ auth()->user()?->phone }}">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">{{ __('portal.public.complainant_email') }}</label>
                            <input type="email" name="complainant_email" class="form-control" maxlength="255" value="{{ auth()->user()?->email }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">{{ __('portal.public.complaint_issue_type') }} <span class="text-danger">*</span></label>
                        <select name="issue_type" class="form-select" required>
                            <option value="payment_not_updated">{{ __('portal.public.complaint_type_not_updated') }}</option>
                            <option value="wrong_paid_amount">{{ __('portal.public.complaint_type_wrong_paid') }}</option>
                            <option value="wrong_due_amount">{{ __('portal.public.complaint_type_wrong_due') }}</option>
                            <option value="other">{{ __('portal.public.complaint_type_other') }}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">{{ __('portal.public.complaint_description') }} <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="3" required maxlength="2000" placeholder="{{ __('portal.public.complaint_description') }}"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark">{{ __('portal.public.complaint_attachment') }}</label>
                        <input type="file" name="attachment" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf">
                        <div class="form-text small">{{ __('portal.documents.file') }}</div>
                    </div>

                    <div id="complaintFormError" class="alert alert-danger py-2 px-3 small d-none mt-3"></div>
                </div>

                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('portal.public.close') }}</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-semibold" id="complaintSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true" id="complaintSubmitSpinner"></span>
                        <i class="bi bi-send me-1" id="complaintSubmitIcon"></i> {{ __('portal.public.complaint_submit_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. DataTables Integration on #donorReportTable
    const $donorTableEl = $('#donorReportTable');
    let donorTable = null;

    if ($.fn.DataTable && $donorTableEl.length) {
        // If single empty colspan row exists, clear it so DataTables calculates column count correctly
        var $tbodyRows = $donorTableEl.find('tbody tr');
        if ($tbodyRows.length === 1 && $tbodyRows.first().find('td[colspan]').length) {
            $donorTableEl.find('tbody').empty();
        }

        donorTable = $donorTableEl.DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('common.all') }}"]],
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            order: [], // Preserve server-provided order
            language: (window.TempleConfig && window.TempleConfig.datatablesLanguage) 
                ? window.TempleConfig.datatablesLanguage 
                : {},
            columnDefs: [
                { targets: [0, 1, 2, 3], className: 'text-start' },
                { targets: [4], className: 'text-center', orderable: false, searchable: false }
            ]
        });

        // Custom DataTables filter for Status dropdown
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (!donorTable || settings.nTable.id !== 'donorReportTable') {
                return true;
            }
            var filterStatus = $('#donorStatusFilter').val();
            if (!filterStatus || filterStatus === 'all') {
                return true;
            }
            var rowNode = donorTable.row(dataIndex).node();
            var rowStatus = $(rowNode).attr('data-status');
            return rowStatus === filterStatus;
        });

        $('#donorStatusFilter').on('change', function() {
            donorTable.draw();
        });
    }

    // 2. Select2 Remote Search & Status Filter
    const $donorSearch = $('#donorSearchInput');
    const statusFilter = document.getElementById('donorStatusFilter');
    const rows = document.querySelectorAll('.donor-row');
    let selectedDonorCode = '';
    let liveSearchQuery = '';

    function filterTableDomFallback() {
        const query = (liveSearchQuery || '').toLowerCase().trim();
        const selectedCode = (selectedDonorCode || '').toLowerCase().trim();
        const status = statusFilter ? statusFilter.value : 'all';

        rows.forEach(function (row) {
            const rowName = (row.getAttribute('data-name') || '').toLowerCase();
            const rowCode = (row.getAttribute('data-code') || '').toLowerCase();
            const rowPhone = (row.getAttribute('data-phone') || '').toLowerCase();
            const rowNameEn = (row.getAttribute('data-name-en') || '').toLowerCase();
            const rowNameBn = (row.getAttribute('data-name-bn') || '').toLowerCase();
            const rowStatus = row.getAttribute('data-status') || '';

            const matchesSelection = !selectedCode || rowCode === selectedCode || rowName.includes(selectedCode);
            const matchesQuery = !query || rowName.includes(query) || rowCode.includes(query) || rowPhone.includes(query) || rowNameEn.includes(query) || rowNameBn.includes(query);
            const matchesSearch = matchesSelection && matchesQuery;
            const matchesStatus = status === 'all' || rowStatus === status;

            const isVisible = matchesSearch && matchesStatus;
            row.style.display = isVisible ? '' : 'none';

            if (selectedCode && matchesSelection && isVisible) {
                row.classList.add('table-warning');
            } else {
                row.classList.remove('table-warning');
            }
        });
    }

    if ($donorSearch.length && $.fn.select2) {
        $donorSearch.select2({
            theme: 'bootstrap-5',
            placeholder: "{{ __('portal.public.filter_search_placeholder') }}",
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('public.events.donors.search', $event->slug) }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || ''
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            },
            minimumInputLength: 1,
            templateResult: function (item) {
                if (!item.id) return item.text;

                var $wrapper = $('<div class="py-1 px-1"></div>');
                var displayName = item.name || item.text || '';
                var $name = $('<div class="fw-semibold text-dark text-truncate"></div>').text(displayName);
                $wrapper.append($name);

                if (item.phone) {
                    var $phone = $('<div class="text-muted small mt-1"><i class="bi bi-telephone me-1 text-primary"></i></div>');
                    $phone.append(document.createTextNode(item.phone));
                    $wrapper.append($phone);
                }

                return $wrapper;
            },
            templateSelection: function (item) {
                if (!item.id) return item.text || "{{ __('portal.public.filter_search_placeholder') }}";
                var displayName = item.name || item.text || '';
                if (item.phone) {
                    return displayName + ' (' + item.phone + ')';
                }
                return displayName;
            }
        });

        $donorSearch.on('select2:select', function (e) {
            const data = e.params.data;
            const searchVal = data.name || data.text || '';
            if (donorTable) {
                donorTable.search(searchVal).draw();
            } else {
                selectedDonorCode = data.code || data.id || '';
                liveSearchQuery = '';
                filterTableDomFallback();
            }
        });

        $donorSearch.on('select2:clear', function () {
            if (donorTable) {
                donorTable.search('').draw();
            } else {
                selectedDonorCode = '';
                liveSearchQuery = '';
                filterTableDomFallback();
            }
        });

        $(document).on('input', '.select2-container--open .select2-search__field', function () {
            const val = $(this).val();
            if (donorTable) {
                donorTable.search(val).draw();
            } else {
                liveSearchQuery = val;
                filterTableDomFallback();
            }
        });
    }

    if (statusFilter && !donorTable) {
        statusFilter.addEventListener('change', filterTableDomFallback);
    }

    // Initialize Tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // 2. Offcanvas View Details AJAX
    const detailsContent = document.getElementById('donorDetailsContent');
    const detailsUrlBase = "{{ url('/events/' . $event->slug . '/donor-details') }}";

    document.querySelectorAll('.btn-donor-details').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const code = this.getAttribute('data-code');
            if (!code) return;

            detailsContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>`;

            fetch(`${detailsUrlBase}/${encodeURIComponent(code)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (res.status === 403) {
                    throw new Error("{{ __('portal.public.unauthorized_cross_donor') }}");
                }
                return res.json();
            })
            .then(data => {
                if (!data.authorized) {
                    detailsContent.innerHTML = `
                        <div class="card border-warning border-opacity-50 bg-warning bg-opacity-10 p-3 rounded-3 mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2 text-warning">
                                <i class="bi bi-shield-lock-fill fs-4 text-warning"></i>
                                <h6 class="fw-bold mb-0 text-dark">{{ __('portal.public.donor_details_title') }}</h6>
                            </div>
                            <p class="small text-muted mb-0">
                                ${data.security_notice || data.message || "{{ __('portal.public.unauthorized_donor_notice') }}"}
                            </p>
                        </div>
                        <div class="text-center pt-3">
                            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('portal.public.admin_login') }}
                            </a>
                        </div>`;
                    return;
                }

                // Render Authorized Donor Details
                let allocationsHtml = '';
                if (data.allocations && data.allocations.length > 0) {
                    allocationsHtml = `
                        <div class="table-responsive mt-3">
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('portal.public.history_payment_date') }}</th>
                                        <th>{{ __('portal.public.history_receipt_number') }}</th>
                                        <th>{{ __('portal.public.history_method') }}</th>
                                        <th class="text-end">{{ __('portal.public.history_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.allocations.map(a => `
                                        <tr>
                                            <td>${a.payment_date}</td>
                                            <td class="font-monospace">${a.receipt_number}</td>
                                            <td>${a.payment_method}</td>
                                            <td class="text-end font-monospace fw-semibold text-success">৳${Number(a.amount).toFixed(2)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>`;
                } else {
                    allocationsHtml = `<p class="small text-muted mt-3 mb-0">{{ __('portal.public.history_no_payments') }}</p>`;
                }

                detailsContent.innerHTML = `
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="text-muted small">{{ __('portal.public.table_donor_name') }}</div>
                        <div class="fw-bold text-dark fs-5">${data.donor_name}</div>
                        <div class="text-muted small mt-1 font-monospace">{{ __('portal.public.donor_code') }}: ${data.code}</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="p-2 rounded-2 bg-light border text-center">
                                <div class="text-muted" style="font-size: 0.75rem;">{{ __('portal.public.table_pledged') }}</div>
                                <div class="fw-bold font-monospace small">৳${Number(data.pledged_amount).toFixed(2)}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-2 bg-success bg-opacity-10 border border-success border-opacity-25 text-center">
                                <div class="text-success" style="font-size: 0.75rem;">{{ __('portal.public.table_paid') }}</div>
                                <div class="fw-bold font-monospace small text-success">৳${Number(data.verified_paid).toFixed(2)}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-2 bg-light border text-center">
                                <div class="text-muted" style="font-size: 0.75rem;">{{ __('portal.public.total_due') }}</div>
                                <div class="fw-bold font-monospace small ${data.due_amount > 0 ? 'text-danger' : 'text-muted'}">৳${Number(data.due_amount).toFixed(2)}</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mt-4">
                        <i class="bi bi-clock-history me-1"></i> {{ __('portal.public.verified_collection_badge') }}
                    </h6>
                    ${allocationsHtml}`;
            })
            .catch(err => {
                detailsContent.innerHTML = `
                    <div class="alert alert-danger py-2 px-3 small">
                        ${err.message || 'Error loading details'}
                    </div>`;
            });
        });
    });

    // 3. Complaint Modal Setup & AJAX Submission
    const complaintModal = document.getElementById('donorComplaintModal');
    const complaintForm = document.getElementById('donorComplaintForm');
    const complaintPledgeCodeInput = document.getElementById('complaintPledgeCode');
    const complaintTargetWrap = document.getElementById('complaintModalTargetNameWrap');
    const complaintTargetName = document.getElementById('complaintModalTargetName');
    const complaintError = document.getElementById('complaintFormError');
    const complaintSubmitBtn = document.getElementById('complaintSubmitBtn');
    const complaintSpinner = document.getElementById('complaintSubmitSpinner');
    const complaintIcon = document.getElementById('complaintSubmitIcon');
    const complaintUrl = "{{ route('public.events.complaint.store', $event->slug) }}";

    document.querySelectorAll('.btn-donor-complain').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const code = this.getAttribute('data-code') || '';
            const name = this.getAttribute('data-name') || '';

            if (complaintPledgeCodeInput) complaintPledgeCodeInput.value = code;
            if (name && complaintTargetWrap && complaintTargetName) {
                complaintTargetName.textContent = name + (code ? ` (${code})` : '');
                complaintTargetWrap.classList.remove('d-none');
            } else if (complaintTargetWrap) {
                complaintTargetWrap.classList.add('d-none');
            }
            if (complaintError) complaintError.classList.add('d-none');
        });
    });

    if (complaintForm) {
        complaintForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (complaintError) complaintError.classList.add('d-none');
            if (complaintSubmitBtn) complaintSubmitBtn.disabled = true;
            if (complaintSpinner) complaintSpinner.classList.remove('d-none');
            if (complaintIcon) complaintIcon.classList.add('d-none');

            const formData = new FormData(complaintForm);

            fetch(complaintUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, body: data })))
            .then(response => {
                if (complaintSubmitBtn) complaintSubmitBtn.disabled = false;
                if (complaintSpinner) complaintSpinner.classList.add('d-none');
                if (complaintIcon) complaintIcon.classList.remove('d-none');

                if (!response.ok) {
                    let errorMsg = response.body.message || 'Submission failed. Please check your inputs.';
                    if (response.body.errors) {
                        const firstErr = Object.values(response.body.errors)[0];
                        if (Array.isArray(firstErr)) errorMsg = firstErr[0];
                    }
                    if (complaintError) {
                        complaintError.textContent = errorMsg;
                        complaintError.classList.remove('d-none');
                    }
                    return;
                }

                // Success
                const bsModal = bootstrap.Modal.getInstance(complaintModal) || new bootstrap.Modal(complaintModal);
                bsModal.hide();
                complaintForm.reset();

                alert(response.body.message);
            })
            .catch(err => {
                if (complaintSubmitBtn) complaintSubmitBtn.disabled = false;
                if (complaintSpinner) complaintSpinner.classList.add('d-none');
                if (complaintIcon) complaintIcon.classList.remove('d-none');
                if (complaintError) {
                    complaintError.textContent = err.message || 'Network error occurred.';
                    complaintError.classList.remove('d-none');
                }
            });
        });
    }
});
</script>
@endpush
@endsection
