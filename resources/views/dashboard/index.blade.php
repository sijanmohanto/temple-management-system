@extends('layouts.app')

@section('title', __('dashboard.title'))

@section('content')
<div class="container-fluid px-0">
    <!-- Welcome Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden"
         style="background: linear-gradient(135deg, var(--temple-maroon), var(--temple-maroon-dark));">
        <div class="card-body p-4 position-relative">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="temple-emblem" style="width: 56px; height: 56px; font-size: 1.8rem;">ॐ</span>
                    <div>
                        <h2 class="h4 fw-bold mb-1">
                            {{ __('dashboard.greeting', ['name' => $user->name]) }}
                        </h2>
                        <p class="text-light opacity-75 mb-0 small">
                            {{ __('dashboard.subtitle') }}
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-role-{{ $user->roles->first()?->slug ?? 'devotee' }} fs-6 px-3 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-shield-check me-1"></i> {{ $user->primary_role_title }}
                    </span>
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-light text-dark fw-semibold rounded-pill px-3">
                        <i class="bi bi-pencil-square me-1"></i> {{ __('common.edit') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Community & People Stats Row (Phase 2) -->
    @if(Auth::user()->hasPermission('view_people'))
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('people.index') }}" class="text-decoration-none">
                    <div class="stat-card border-start border-4 border-primary">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold mb-1">{{ __('dashboard.total_people') }}</div>
                                <div class="fs-3 fw-bold text-dark">{{ $stats['total_people'] }}</div>
                            </div>
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('families.index') }}" class="text-decoration-none">
                    <div class="stat-card border-start border-4 border-info">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold mb-1">{{ __('dashboard.total_families') }}</div>
                                <div class="fs-3 fw-bold text-dark">{{ $stats['total_families'] }}</div>
                            </div>
                            <div class="stat-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-house-heart"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('committees.index') }}" class="text-decoration-none">
                    <div class="stat-card border-start border-4 border-warning">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold mb-1">{{ __('dashboard.active_committees') }}</div>
                                <div class="fs-3 fw-bold text-dark">{{ $stats['active_committees'] }}</div>
                            </div>
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-award"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card border-start border-4 border-success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold mb-1">Active Devotees</div>
                            <div class="fs-3 fw-bold text-success">{{ $stats['active_people'] }}</div>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-person-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold mb-1">{{ __('dashboard.total_users') }}</div>
                        <div class="fs-3 fw-bold text-dark">{{ $stats['total_users'] }}</div>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold mb-1">{{ __('dashboard.active_users') }}</div>
                        <div class="fs-3 fw-bold text-success">{{ $stats['active_users'] }}</div>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold mb-1">{{ __('dashboard.system_roles') }}</div>
                        <div class="fs-3 fw-bold text-warning">{{ $stats['total_roles'] }}</div>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold mb-1">{{ __('dashboard.audit_events') }}</div>
                        <div class="fs-3 fw-bold text-danger">{{ $stats['total_audit_logs'] }}</div>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-journal-text"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Long-Term Product Scope & Phase 2 Architecture Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="p-2 rounded-3 bg-success bg-opacity-10 text-success fs-4">
                            <i class="bi bi-cash-stack"></i>
                        </span>
                        <span class="badge bg-secondary">Phase 2</span>
                    </div>
                    <h5 class="fw-bold mb-2">{{ __('dashboard.financial_summary_title') }}</h5>
                    <p class="text-muted small flex-grow-1">
                        {{ __('dashboard.financial_summary_desc') }}
                    </p>
                    <div class="pt-2 border-top">
                        <span class="text-muted small"><i class="bi bi-lock me-1"></i> Double-Entry Ledger Planned</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary fs-4">
                            <i class="bi bi-people-fill"></i>
                        </span>
                        <span class="badge bg-success">Phase 2 Active</span>
                    </div>
                    <h5 class="fw-bold mb-2">{{ __('dashboard.people_summary_title') }}</h5>
                    <p class="text-muted small flex-grow-1">
                        {{ __('dashboard.people_summary_desc') }}
                    </p>
                    <div class="pt-2 border-top">
                        @if(Auth::user()->hasPermission('view_people'))
                            <a href="{{ route('people.index') }}" class="btn btn-sm btn-outline-primary rounded-pill w-100">
                                <i class="bi bi-arrow-right me-1"></i> Browse Directory
                            </a>
                        @else
                            <span class="text-muted small"><i class="bi bi-person-lines-fill me-1"></i> Decoupled Person Registry</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning fs-4">
                            <i class="bi bi-calendar3"></i>
                        </span>
                        <span class="badge bg-secondary">Phase 2</span>
                    </div>
                    <h5 class="fw-bold mb-2">{{ __('dashboard.events_summary_title') }}</h5>
                    <p class="text-muted small flex-grow-1">
                        {{ __('dashboard.events_summary_desc') }}
                    </p>
                    <div class="pt-2 border-top">
                        <span class="text-muted small"><i class="bi bi-journal-check me-1"></i> Pledges & Puja Editions</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="p-2 rounded-3 bg-info bg-opacity-10 text-info fs-4">
                            <i class="bi bi-building"></i>
                        </span>
                        <span class="badge bg-secondary">Phase 2</span>
                    </div>
                    <h5 class="fw-bold mb-2">{{ __('dashboard.venues_summary_title') }}</h5>
                    <p class="text-muted small flex-grow-1">
                        {{ __('dashboard.venues_summary_desc') }}
                    </p>
                    <div class="pt-2 border-top">
                        <span class="text-muted small"><i class="bi bi-clock-history me-1"></i> Hall Rental & Refund Flow</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity / Audit Trail Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="bi bi-activity text-danger me-2"></i> {{ __('dashboard.recent_activity') }}
            </h5>
            @if($user->hasRole('super_admin'))
                <a href="{{ route('admin.audit-logs') }}" class="btn btn-sm btn-outline-danger">
                    {{ __('dashboard.view_audit') }} <i class="bi bi-arrow-right ms-1"></i>
                </a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">{{ __('dashboard.activity_time') }}</th>
                            <th>{{ __('dashboard.activity_user') }}</th>
                            <th>{{ __('dashboard.activity_action') }}</th>
                            <th>{{ __('dashboard.activity_desc') }}</th>
                            <th class="pe-4">{{ __('dashboard.activity_ip') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAuditLogs as $log)
                            <tr>
                                <td class="ps-4 text-nowrap text-muted small">
                                    {{ $log->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                </td>
                                <td class="fw-semibold">
                                    @if($log->user)
                                        <span class="badge badge-role-{{ $log->user->roles->first()?->slug ?? 'devotee' }} me-1">
                                            {{ $log->user->primary_role_title }}
                                        </span>
                                        {{ $log->user->name }}
                                    @else
                                        <span class="text-muted fst-italic">System</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td>{{ $log->description }}</td>
                                <td class="pe-4 text-muted small text-nowrap">
                                    {{ $log->ip_address ?? '127.0.0.1' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    {{ __('common.no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
