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

        <!-- Module 4 & 5: Collections, Pledges & Finance -->
        <div class="sidebar-heading">{{ __('donations.collections_management') }}</div>
        @if(Auth::user()->hasPermission('view_collections'))
            <a href="{{ route('collections.dashboard') }}" class="sidebar-link {{ request()->routeIs('collections.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>{{ __('donations.dashboard') }}</span>
            </a>
            <a href="{{ route('collections.index') }}" class="sidebar-link {{ request()->routeIs('collections.index') || request()->routeIs('collections.show') || request()->routeIs('collections.create') ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i>
                <span>{{ __('donations.payments') }}</span>
            </a>
        @endif

        @if(Auth::user()->hasPermission('view_pledges'))
            <a href="{{ route('pledges.index') }}" class="sidebar-link {{ request()->routeIs('pledges.*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark"></i>
                <span>{{ __('donations.pledges') }}</span>
            </a>
        @endif

        @if(Auth::user()->hasPermission('view_committee_contributions'))
            <a href="{{ route('contributions.index') }}" class="sidebar-link {{ request()->routeIs('contributions.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check"></i>
                <span>{{ __('donations.committee_contributions') }}</span>
            </a>
        @endif

        @if(Auth::user()->hasPermission('view_receipts'))
            <a href="{{ route('receipts.index') }}" class="sidebar-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i>
                <span>{{ __('donations.receipts') }}</span>
            </a>
        @endif

        @if(Auth::user()->hasPermission('view_collection_reports'))
            <a href="{{ route('collections.reports.index') }}" class="sidebar-link {{ request()->routeIs('collections.reports.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>{{ __('donations.reports') }}</span>
            </a>
        @endif

        <!-- Module 6: Finance & Accounting (Phase 5) -->
        @if(Auth::user()->hasAnyPermission(['view_finance_dashboard', 'view_accounts', 'view_journals', 'view_expenses', 'view_income', 'view_transfers', 'view_financial_reports']))
            <div class="sidebar-heading">{{ __('finance.finance_and_accounting') }}</div>
            
            @if(Auth::user()->hasPermission('view_finance_dashboard'))
                <a href="{{ route('finance.dashboard') }}" class="sidebar-link {{ request()->routeIs('finance.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>{{ __('finance.finance_dashboard') }}</span>
                </a>
            @endif

            @if(Auth::user()->hasPermission('view_accounts'))
                <a href="{{ route('finance.accounts.index') }}" class="sidebar-link {{ request()->routeIs('finance.accounts.*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i>
                    <span>{{ __('finance.chart_of_accounts') }}</span>
                </a>
            @endif

            @if(Auth::user()->hasPermission('view_journals'))
                <a href="{{ route('finance.journals.index') }}" class="sidebar-link {{ request()->routeIs('finance.journals.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i>
                    <span>{{ __('finance.journal_entries') }}</span>
                </a>
            @endif

            @if(Auth::user()->hasPermission('view_expenses'))
                <a href="{{ route('finance.expenses.index') }}" class="sidebar-link {{ request()->routeIs('finance.expenses.*') ? 'active' : '' }}">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span>{{ __('finance.expenses') }}</span>
                </a>
            @endif

            @if(Auth::user()->hasPermission('view_transfers'))
                <a href="{{ route('finance.transfers.index') }}" class="sidebar-link {{ request()->routeIs('finance.transfers.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>{{ __('finance.fund_transfers') }}</span>
                </a>
            @endif

            @if(Auth::user()->hasPermission('view_income'))
                <a href="{{ route('finance.income.index') }}" class="sidebar-link {{ request()->routeIs('finance.income.*') ? 'active' : '' }}">
                    <i class="bi bi-cash-coin"></i>
                    <span>{{ __('finance.misc_income') }}</span>
                </a>
            @endif

            @if(Auth::user()->hasPermission('manage_opening_balances'))
                <a href="{{ route('finance.opening-balances.index') }}" class="sidebar-link {{ request()->routeIs('finance.opening-balances.*') ? 'active' : '' }}">
                    <i class="bi bi-safe"></i>
                    <span>{{ __('finance.opening_balances') }}</span>
                </a>
            @endif

            @if(Auth::user()->hasPermission('view_financial_reports'))
                <a href="{{ route('finance.reports.trial-balance') }}" class="sidebar-link {{ request()->routeIs('finance.reports.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    <span>{{ __('finance.financial_reports') }}</span>
                </a>
            @endif
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
            v1.5 (Phase 5 Finance & Accounts)
        </div>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
