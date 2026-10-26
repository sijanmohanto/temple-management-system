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
        @if(Auth::user()->hasPermission('view_people'))
            <div class="sidebar-heading">{{ __('nav.people_management') }}</div>
            <a href="{{ route('people.index') }}" class="sidebar-link {{ request()->routeIs('people.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>{{ __('nav.people_list') }}</span>
            </a>
            <a href="{{ route('families.index') }}" class="sidebar-link {{ request()->routeIs('families.*') ? 'active' : '' }}">
                <i class="bi bi-house-heart"></i>
                <span>{{ __('nav.families') }}</span>
            </a>
            <a href="{{ route('committees.index') }}" class="sidebar-link {{ request()->routeIs('committees.*') ? 'active' : '' }}">
                <i class="bi bi-award"></i>
                <span>{{ __('nav.committee') }}</span>
            </a>
        @endif

        <!-- Module 2 & 3: Puja & Events -->
        @if(Auth::user()->hasPermission('view_events'))
            <div class="sidebar-heading">{{ __('nav.events_puja') }}</div>
            <a href="{{ route('events.dashboard') }}" class="sidebar-link {{ request()->routeIs('events.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer"></i>
                <span>{{ __('events.dashboard') }}</span>
            </a>
            <a href="{{ route('events.index') }}" class="sidebar-link {{ request()->routeIs('events.index') || request()->routeIs('events.show') || request()->routeIs('events.create') || request()->routeIs('events.edit') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i>
                <span>{{ __('events.events') }}</span>
            </a>
            <a href="{{ route('events.categories.index') }}" class="sidebar-link {{ request()->routeIs('events.categories.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i>
                <span>{{ __('events.categories') }}</span>
            </a>
        @endif

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
            v1.1 (Phase 2 People & Committees)
        </div>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
