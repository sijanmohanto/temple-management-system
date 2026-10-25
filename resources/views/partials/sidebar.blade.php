<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <span class="temple-emblem">ॐ</span>
            <div>
                <div class="lh-1">{{ __('common.app_name') }}</div>
                <small class="text-warning fw-normal" style="font-size: 0.72rem;">{{ __('common.temple_motto') }}</small>
            </div>
        </a>
    </div>

    <div class="sidebar-menu">
        <!-- Main Section -->
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            <span>{{ __('nav.dashboard') }}</span>
        </a>

        <!-- Module 1: People & Devotees -->
        <div class="sidebar-heading">{{ __('nav.people_management') }}</div>
        <a href="#peopleModule" class="sidebar-link disabled-future" title="{{ __('common.under_construction') }}">
            <i class="bi bi-people"></i>
            <span>{{ __('nav.people_list') }}</span>
            <span class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">P2</span>
        </a>
        <a href="#committeeModule" class="sidebar-link disabled-future" title="{{ __('common.under_construction') }}">
            <i class="bi bi-award"></i>
            <span>{{ __('nav.committee') }}</span>
            <span class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">P2</span>
        </a>

        <!-- Module 2 & 3: Puja & Events -->
        <div class="sidebar-heading">{{ __('nav.events_puja') }}</div>
        <a href="#pujaModule" class="sidebar-link disabled-future" title="{{ __('common.under_construction') }}">
            <i class="bi bi-calendar-event"></i>
            <span>{{ __('nav.puja_editions') }}</span>
            <span class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">P2</span>
        </a>
        <a href="#pledgesModule" class="sidebar-link disabled-future" title="{{ __('common.under_construction') }}">
            <i class="bi bi-journal-bookmark"></i>
            <span>{{ __('nav.pledges') }}</span>
            <span class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">P2</span>
        </a>

        <!-- Module 4 & 5: Central Finance -->
        <div class="sidebar-heading">{{ __('nav.finance') }}</div>
        @if(Auth::user()->hasAnyRole(['super_admin', 'treasurer']))
            <a href="{{ route('finance.overview') }}" class="sidebar-link {{ request()->routeIs('finance.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i>
                <span>{{ __('nav.ledger') }}</span>
            </a>
        @else
            <a href="#ledgerPreview" class="sidebar-link disabled-future" title="{{ __('common.under_construction') }}">
                <i class="bi bi-cash-coin"></i>
                <span>{{ __('nav.donations') }}</span>
                <span class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">P2</span>
            </a>
        @endif

        <!-- Module 7: Venues & Hall Bookings -->
        <div class="sidebar-heading">{{ __('nav.venues') }}</div>
        <a href="#venueModule" class="sidebar-link disabled-future" title="{{ __('common.under_construction') }}">
            <i class="bi bi-building"></i>
            <span>{{ __('nav.venues_list') }}</span>
            <span class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">P2</span>
        </a>

        <!-- Module 8: Operations & Seva -->
        <div class="sidebar-heading">{{ __('nav.operations') }}</div>
        <a href="#opsModule" class="sidebar-link disabled-future" title="{{ __('common.under_construction') }}">
            <i class="bi bi-person-arms-up"></i>
            <span>{{ __('nav.volunteers') }}</span>
            <span class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">P2</span>
        </a>

        <!-- System Administration (Super Admin only) -->
        @if(Auth::user()->hasRole('super_admin'))
            <div class="sidebar-heading">{{ __('nav.administration') }}</div>
            <a href="{{ route('admin.audit-logs') }}" class="sidebar-link {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i>
                <span>{{ __('nav.audit_logs') }}</span>
            </a>
        @endif

        <div class="sidebar-heading">{{ __('nav.my_profile') }}</div>
        <a href="{{ route('profile.edit') }}" class="sidebar-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
            <i class="bi bi-person-circle"></i>
            <span>{{ __('nav.my_profile') }}</span>
        </a>
    </div>

    <div class="p-3 border-top border-secondary text-muted small">
        <div class="d-flex align-items-center justify-content-between">
            <span>{{ __('common.status') }}:</span>
            <span class="badge bg-success">{{ __('common.status_active') }}</span>
        </div>
        <div class="mt-1" style="font-size: 0.75rem;">
            v1.0 (Phase 1 Foundation)
        </div>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
