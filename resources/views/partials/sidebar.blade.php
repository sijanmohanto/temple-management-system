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

    <div class="sidebar-menu" id="appSidebarMenu">
        <!-- Main Dashboard -->
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            <span>{{ __('nav.dashboard') }}</span>
        </a>

        <!-- Module 1: People & Devotees -->
        @if(Auth::user()->hasPermission('view_people'))
            <div class="sidebar-heading">{{ __('nav.people_management') }}</div>
            @php
                $isPeopleActive = request()->routeIs('people.*') || request()->routeIs('families.*') || request()->routeIs('committees.*');
            @endphp
            <a class="sidebar-link sidebar-parent {{ $isPeopleActive ? 'active-parent' : '' }}"
               data-bs-toggle="collapse"
               href="#menuPeople"
               role="button"
               aria-expanded="{{ $isPeopleActive ? 'true' : 'false' }}"
               aria-controls="menuPeople">
                <i class="bi bi-people"></i>
                <span>{{ __('nav.directory') }}</span>
                <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
            </a>
            <div class="collapse {{ $isPeopleActive ? 'show' : '' }}" id="menuPeople">
                <div class="sidebar-submenu">
                    <a href="{{ route('people.index') }}" class="sidebar-sublink {{ request()->routeIs('people.*') ? 'active' : '' }}">
                        <i class="bi bi-person-lines-fill"></i>
                        <span>{{ __('nav.people_list') }}</span>
                    </a>
                    <a href="{{ route('families.index') }}" class="sidebar-sublink {{ request()->routeIs('families.*') ? 'active' : '' }}">
                        <i class="bi bi-house-heart"></i>
                        <span>{{ __('nav.families') }}</span>
                    </a>
                    <a href="{{ route('committees.index') }}" class="sidebar-sublink {{ request()->routeIs('committees.*') ? 'active' : '' }}">
                        <i class="bi bi-award"></i>
                        <span>{{ __('nav.committee') }}</span>
                    </a>
                </div>
            </div>
        @endif

        <!-- Module 2: Puja & Events -->
        @if(Auth::user()->hasPermission('view_events'))
            <div class="sidebar-heading">{{ __('nav.events_puja') }}</div>
            <a href="{{ route('events.dashboard') }}" class="sidebar-link {{ request()->routeIs('events.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer"></i>
                <span>{{ __('events.dashboard') }}</span>
            </a>
            @php
                $isEventsActive = request()->routeIs('events.index') || request()->routeIs('events.show') || request()->routeIs('events.create') || request()->routeIs('events.edit') || request()->routeIs('events.categories.*');
            @endphp
            <a class="sidebar-link sidebar-parent {{ $isEventsActive ? 'active-parent' : '' }}"
               data-bs-toggle="collapse"
               href="#menuEvents"
               role="button"
               aria-expanded="{{ $isEventsActive ? 'true' : 'false' }}"
               aria-controls="menuEvents">
                <i class="bi bi-calendar-event"></i>
                <span>{{ __('nav.events_management') }}</span>
                <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
            </a>
            <div class="collapse {{ $isEventsActive ? 'show' : '' }}" id="menuEvents">
                <div class="sidebar-submenu">
                    <a href="{{ route('events.index') }}" class="sidebar-sublink {{ request()->routeIs('events.index') || request()->routeIs('events.show') || request()->routeIs('events.edit') ? 'active' : '' }}">
                        <i class="bi bi-calendar4-week"></i>
                        <span>{{ __('nav.all_events') }}</span>
                    </a>
                    @if(Auth::user()->hasPermission('manage_events'))
                        <a href="{{ route('events.create') }}" class="sidebar-sublink {{ request()->routeIs('events.create') ? 'active' : '' }}">
                            <i class="bi bi-plus-circle"></i>
                            <span>{{ __('nav.create_event') }}</span>
                        </a>
                    @endif
                    <a href="{{ route('events.categories.index') }}" class="sidebar-sublink {{ request()->routeIs('events.categories.*') ? 'active' : '' }}">
                        <i class="bi bi-tags"></i>
                        <span>{{ __('nav.event_categories') }}</span>
                    </a>
                </div>
            </div>
        @endif

        <!-- Module 3: Collections & Pledges -->
        @if(Auth::user()->hasAnyPermission(['view_collections', 'view_pledges', 'view_committee_contributions', 'view_receipts', 'view_collection_reports']))
            <div class="sidebar-heading">{{ __('donations.collections_management') }}</div>
            @if(Auth::user()->hasPermission('view_collections'))
                <a href="{{ route('collections.dashboard') }}" class="sidebar-link {{ request()->routeIs('collections.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>{{ __('donations.dashboard') }}</span>
                </a>

                @php
                    $isPaymentsActive = request()->routeIs('collections.index') || request()->routeIs('collections.show') || request()->routeIs('collections.create');
                @endphp
                <a class="sidebar-link sidebar-parent {{ $isPaymentsActive ? 'active-parent' : '' }}"
                   data-bs-toggle="collapse"
                   href="#menuCollections"
                   role="button"
                   aria-expanded="{{ $isPaymentsActive ? 'true' : 'false' }}"
                   aria-controls="menuCollections">
                    <i class="bi bi-cash-coin"></i>
                    <span>{{ __('nav.payments_collections') }}</span>
                    <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
                </a>
                <div class="collapse {{ $isPaymentsActive ? 'show' : '' }}" id="menuCollections">
                    <div class="sidebar-submenu">
                        <a href="{{ route('collections.index') }}" class="sidebar-sublink {{ request()->routeIs('collections.index') || request()->routeIs('collections.show') ? 'active' : '' }}">
                            <i class="bi bi-list-check"></i>
                            <span>{{ __('nav.payment_register') }}</span>
                        </a>
                        @if(Auth::user()->hasPermission('record_collections'))
                            <a href="{{ route('collections.create') }}" class="sidebar-sublink {{ request()->routeIs('collections.create') ? 'active' : '' }}">
                                <i class="bi bi-plus-circle"></i>
                                <span>{{ __('nav.record_payment') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if(Auth::user()->hasPermission('view_pledges'))
                @php
                    $isPledgesActive = request()->routeIs('pledges.*');
                @endphp
                <a class="sidebar-link sidebar-parent {{ $isPledgesActive ? 'active-parent' : '' }}"
                   data-bs-toggle="collapse"
                   href="#menuPledges"
                   role="button"
                   aria-expanded="{{ $isPledgesActive ? 'true' : 'false' }}"
                   aria-controls="menuPledges">
                    <i class="bi bi-journal-bookmark"></i>
                    <span>{{ __('nav.pledges_management') }}</span>
                    <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
                </a>
                <div class="collapse {{ $isPledgesActive ? 'show' : '' }}" id="menuPledges">
                    <div class="sidebar-submenu">
                        <a href="{{ route('pledges.index') }}" class="sidebar-sublink {{ request()->routeIs('pledges.index') || request()->routeIs('pledges.show') ? 'active' : '' }}">
                            <i class="bi bi-journal-text"></i>
                            <span>{{ __('nav.pledge_register') }}</span>
                        </a>
                        @if(Auth::user()->hasPermission('manage_pledges'))
                            <a href="{{ route('pledges.create') }}" class="sidebar-sublink {{ request()->routeIs('pledges.create') ? 'active' : '' }}">
                                <i class="bi bi-plus-circle"></i>
                                <span>{{ __('nav.create_pledge') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
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
        @endif

        <!-- Module 4: Finance & Accounting -->
        @if(Auth::user()->hasAnyPermission(['view_finance_dashboard', 'view_accounts', 'view_journals', 'view_expenses', 'view_income', 'view_transfers', 'view_financial_reports', 'manage_opening_balances']))
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

            {{-- Income & Expense Reports + Misc Income --}}
            @if(Auth::user()->hasAnyPermission(['view_financial_reports', 'view_income']))
                @php
                    $isIncomeExpenseActive = request()->routeIs('finance.reports.income*') || request()->routeIs('finance.reports.expenses*') || request()->routeIs('finance.income.*');
                @endphp
                <a class="sidebar-link sidebar-parent {{ $isIncomeExpenseActive ? 'active-parent' : '' }}"
                   data-bs-toggle="collapse"
                   href="#menuFinanceIncomeExpense"
                   role="button"
                   aria-expanded="{{ $isIncomeExpenseActive ? 'true' : 'false' }}"
                   aria-controls="menuFinanceIncomeExpense">
                    <i class="bi bi-wallet2"></i>
                    <span>{{ __('nav.income_and_expense') }}</span>
                    <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
                </a>
                <div class="collapse {{ $isIncomeExpenseActive ? 'show' : '' }}" id="menuFinanceIncomeExpense">
                    <div class="sidebar-submenu">
                        @if(Auth::user()->hasPermission('view_financial_reports'))
                            <a href="{{ route('finance.reports.income') }}" class="sidebar-sublink {{ request()->routeIs('finance.reports.income*') ? 'active' : '' }}">
                                <i class="bi bi-graph-up-arrow"></i>
                                <span>{{ __('finance.income_report') }}</span>
                            </a>
                            <a href="{{ route('finance.reports.expenses') }}" class="sidebar-sublink {{ request()->routeIs('finance.reports.expenses*') ? 'active' : '' }}">
                                <i class="bi bi-graph-down-arrow"></i>
                                <span>{{ __('finance.expense_report') }}</span>
                            </a>
                        @endif
                        @if(Auth::user()->hasPermission('view_income'))
                            <a href="{{ route('finance.income.index') }}" class="sidebar-sublink {{ request()->routeIs('finance.income.*') ? 'active' : '' }}">
                                <i class="bi bi-cash-coin"></i>
                                <span>{{ __('finance.misc_income') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Expenses: List & Create --}}
            @if(Auth::user()->hasAnyPermission(['view_expenses', 'record_expenses']))
                @php
                    $isExpensesActive = request()->routeIs('finance.expenses.*');
                @endphp
                <a class="sidebar-link sidebar-parent {{ $isExpensesActive ? 'active-parent' : '' }}"
                   data-bs-toggle="collapse"
                   href="#menuFinanceExpenses"
                   role="button"
                   aria-expanded="{{ $isExpensesActive ? 'true' : 'false' }}"
                   aria-controls="menuFinanceExpenses">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span>{{ __('nav.expenses_management') }}</span>
                    <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
                </a>
                <div class="collapse {{ $isExpensesActive ? 'show' : '' }}" id="menuFinanceExpenses">
                    <div class="sidebar-submenu">
                        @if(Auth::user()->hasPermission('view_expenses'))
                            <a href="{{ route('finance.expenses.index') }}" class="sidebar-sublink {{ request()->routeIs('finance.expenses.index') || request()->routeIs('finance.expenses.show') || request()->routeIs('finance.expenses.edit') ? 'active' : '' }}">
                                <i class="bi bi-list-ul"></i>
                                <span>{{ __('nav.expense_list') }}</span>
                            </a>
                        @endif
                        @if(Auth::user()->hasPermission('record_expenses'))
                            <a href="{{ route('finance.expenses.create') }}" class="sidebar-sublink {{ request()->routeIs('finance.expenses.create') ? 'active' : '' }}">
                                <i class="bi bi-plus-circle"></i>
                                <span>{{ __('nav.record_expense') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Transfers: List & Create --}}
            @if(Auth::user()->hasAnyPermission(['view_transfers', 'manage_transfers']))
                @php
                    $isTransfersActive = request()->routeIs('finance.transfers.*');
                @endphp
                <a class="sidebar-link sidebar-parent {{ $isTransfersActive ? 'active-parent' : '' }}"
                   data-bs-toggle="collapse"
                   href="#menuFinanceTransfers"
                   role="button"
                   aria-expanded="{{ $isTransfersActive ? 'true' : 'false' }}"
                   aria-controls="menuFinanceTransfers">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>{{ __('nav.transfers_management') }}</span>
                    <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
                </a>
                <div class="collapse {{ $isTransfersActive ? 'show' : '' }}" id="menuFinanceTransfers">
                    <div class="sidebar-submenu">
                        @if(Auth::user()->hasPermission('view_transfers'))
                            <a href="{{ route('finance.transfers.index') }}" class="sidebar-sublink {{ request()->routeIs('finance.transfers.index') ? 'active' : '' }}">
                                <i class="bi bi-list-ul"></i>
                                <span>{{ __('nav.transfer_list') }}</span>
                            </a>
                        @endif
                        @if(Auth::user()->hasPermission('manage_transfers'))
                            <a href="{{ route('finance.transfers.create') }}" class="sidebar-sublink {{ request()->routeIs('finance.transfers.create') ? 'active' : '' }}">
                                <i class="bi bi-plus-circle"></i>
                                <span>{{ __('nav.new_transfer') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Journals & Ledgers --}}
            @if(Auth::user()->hasAnyPermission(['view_journals', 'manage_opening_balances']))
                @php
                    $isJournalsActive = request()->routeIs('finance.journals.*') || request()->routeIs('finance.opening-balances.*');
                @endphp
                <a class="sidebar-link sidebar-parent {{ $isJournalsActive ? 'active-parent' : '' }}"
                   data-bs-toggle="collapse"
                   href="#menuFinanceJournals"
                   role="button"
                   aria-expanded="{{ $isJournalsActive ? 'true' : 'false' }}"
                   aria-controls="menuFinanceJournals">
                    <i class="bi bi-journal-text"></i>
                    <span>{{ __('nav.journals_ledgers') }}</span>
                    <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
                </a>
                <div class="collapse {{ $isJournalsActive ? 'show' : '' }}" id="menuFinanceJournals">
                    <div class="sidebar-submenu">
                        @if(Auth::user()->hasPermission('view_journals'))
                            <a href="{{ route('finance.journals.index') }}" class="sidebar-sublink {{ request()->routeIs('finance.journals.*') ? 'active' : '' }}">
                                <i class="bi bi-journal-check"></i>
                                <span>{{ __('nav.journal_entries') }}</span>
                            </a>
                        @endif
                        @if(Auth::user()->hasPermission('manage_opening_balances'))
                            <a href="{{ route('finance.opening-balances.index') }}" class="sidebar-sublink {{ request()->routeIs('finance.opening-balances.*') ? 'active' : '' }}">
                                <i class="bi bi-safe"></i>
                                <span>{{ __('nav.opening_balances') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Financial Statements / Reports --}}
            @if(Auth::user()->hasPermission('view_financial_reports'))
                @php
                    $isFinancialReportsActive = request()->routeIs('finance.reports.*') && !request()->routeIs('finance.reports.income*') && !request()->routeIs('finance.reports.expenses*');
                @endphp
                <a class="sidebar-link sidebar-parent {{ $isFinancialReportsActive ? 'active-parent' : '' }}"
                   data-bs-toggle="collapse"
                   href="#menuFinancialReports"
                   role="button"
                   aria-expanded="{{ $isFinancialReportsActive ? 'true' : 'false' }}"
                   aria-controls="menuFinancialReports">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    <span>{{ __('nav.financial_statements') }}</span>
                    <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
                </a>
                <div class="collapse {{ $isFinancialReportsActive ? 'show' : '' }}" id="menuFinancialReports">
                    <div class="sidebar-submenu">
                        <a href="{{ route('finance.reports.trial-balance') }}" class="sidebar-sublink {{ request()->routeIs('finance.reports.trial-balance') ? 'active' : '' }}">
                            <i class="bi bi-bar-chart-steps"></i>
                            <span>{{ __('nav.trial_balance') }}</span>
                        </a>
                        <a href="{{ route('finance.reports.general-ledger') }}" class="sidebar-sublink {{ request()->routeIs('finance.reports.general-ledger') ? 'active' : '' }}">
                            <i class="bi bi-book"></i>
                            <span>{{ __('nav.general_ledger') }}</span>
                        </a>
                        <a href="{{ route('finance.reports.cash-book') }}" class="sidebar-sublink {{ request()->routeIs('finance.reports.cash-book') ? 'active' : '' }}">
                            <i class="bi bi-cash-stack"></i>
                            <span>{{ __('nav.cash_book') }}</span>
                        </a>
                        <a href="{{ route('finance.reports.income-expense') }}" class="sidebar-sublink {{ request()->routeIs('finance.reports.income-expense') ? 'active' : '' }}">
                            <i class="bi bi-pie-chart"></i>
                            <span>{{ __('nav.income_expenditure') }}</span>
                        </a>
                    </div>
                </div>
            @endif
        @endif

        <!-- Module 5: Budget & Procurement (Phase 6) -->
        @if(Auth::user()->hasRole('super_admin') || Auth::user()->hasAnyPermission(['view_budgets', 'manage_budgets', 'view_vendors', 'manage_vendors', 'view_purchase_requests', 'manage_purchase_requests', 'view_purchase_orders', 'manage_purchase_orders', 'view_vendor_bills', 'manage_vendor_bills', 'view_procurement_reports']))
            <div class="sidebar-heading">{{ __('procurement.budget_and_procurement') }}</div>

            {{-- Budgets --}}
            @php
                $isBudgetsActive = request()->routeIs('procurement.budgets.*') || request()->routeIs('procurement.reports.budget-vs-actual');
            @endphp
            <a class="sidebar-link sidebar-parent {{ $isBudgetsActive ? 'active-parent' : '' }}"
               data-bs-toggle="collapse"
               href="#menuProcurementBudgets"
               role="button"
               aria-expanded="{{ $isBudgetsActive ? 'true' : 'false' }}"
               aria-controls="menuProcurementBudgets">
                <i class="bi bi-calculator"></i>
                <span>{{ __('procurement.budgets') }}</span>
                <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
            </a>
            <div class="collapse {{ $isBudgetsActive ? 'show' : '' }}" id="menuProcurementBudgets">
                <div class="sidebar-submenu">
                    <a href="{{ Route::has('procurement.budgets.index') ? route('procurement.budgets.index') : '#budgetsList' }}" class="sidebar-sublink {{ request()->routeIs('procurement.budgets.index') ? 'active' : '' }}">
                        <i class="bi bi-list-columns-reverse"></i>
                        <span>{{ __('procurement.budget_list') }}</span>
                    </a>
                    <a href="{{ Route::has('procurement.budgets.create') ? route('procurement.budgets.create') : '#budgetsCreate' }}" class="sidebar-sublink {{ request()->routeIs('procurement.budgets.create') ? 'active' : '' }}">
                        <i class="bi bi-plus-circle"></i>
                        <span>{{ __('procurement.create_budget') }}</span>
                    </a>
                    <a href="{{ Route::has('procurement.reports.budget-vs-actual') ? route('procurement.reports.budget-vs-actual') : '#budgetVsActual' }}" class="sidebar-sublink {{ request()->routeIs('procurement.reports.budget-vs-actual') ? 'active' : '' }}">
                        <i class="bi bi-graph-up"></i>
                        <span>{{ __('procurement.budget_vs_actual') }}</span>
                    </a>
                </div>
            </div>

            {{-- Vendors --}}
            @if(Auth::user()->hasRole('super_admin') || Auth::user()->hasAnyPermission(['view_vendors', 'manage_vendors']))
            @php
                $isVendorsActive = request()->routeIs('procurement.vendors.*');
            @endphp
            <a class="sidebar-link sidebar-parent {{ $isVendorsActive ? 'active-parent' : '' }}"
               data-bs-toggle="collapse"
               href="#menuProcurementVendors"
               role="button"
               aria-expanded="{{ $isVendorsActive ? 'true' : 'false' }}"
               aria-controls="menuProcurementVendors">
                <i class="bi bi-shop"></i>
                <span>{{ __('procurement.vendors') }}</span>
                <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
            </a>
            <div class="collapse {{ $isVendorsActive ? 'show' : '' }}" id="menuProcurementVendors">
                <div class="sidebar-submenu">
                    <a href="{{ Route::has('procurement.vendors.index') ? route('procurement.vendors.index') : '#vendorsList' }}" class="sidebar-sublink {{ request()->routeIs('procurement.vendors.index') ? 'active' : '' }}">
                        <i class="bi bi-person-badge"></i>
                        <span>{{ __('procurement.vendor_list') }}</span>
                    </a>
                    @if(Auth::user()->hasRole('super_admin') || Auth::user()->hasPermission('manage_vendors'))
                    <a href="{{ Route::has('procurement.vendors.create') ? route('procurement.vendors.create') : '#vendorsCreate' }}" class="sidebar-sublink {{ request()->routeIs('procurement.vendors.create') ? 'active' : '' }}">
                        <i class="bi bi-plus-circle"></i>
                        <span>{{ __('procurement.add_vendor') }}</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Purchase Requests --}}
            @if(Auth::user()->hasRole('super_admin') || Auth::user()->hasAnyPermission(['view_purchase_requests', 'manage_purchase_requests']))
            @php
                $isRequestsActive = request()->routeIs('procurement.requests.*');
            @endphp
            <a class="sidebar-link sidebar-parent {{ $isRequestsActive ? 'active-parent' : '' }}"
               data-bs-toggle="collapse"
               href="#menuProcurementRequests"
               role="button"
               aria-expanded="{{ $isRequestsActive ? 'true' : 'false' }}"
               aria-controls="menuProcurementRequests">
                <i class="bi bi-clipboard-check"></i>
                <span>{{ __('procurement.purchase_requests') }}</span>
                <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
            </a>
            <div class="collapse {{ $isRequestsActive ? 'show' : '' }}" id="menuProcurementRequests">
                <div class="sidebar-submenu">
                    <a href="{{ Route::has('procurement.requests.index') ? route('procurement.requests.index') : '#requestsList' }}" class="sidebar-sublink {{ request()->routeIs('procurement.requests.index') ? 'active' : '' }}">
                        <i class="bi bi-card-checklist"></i>
                        <span>{{ __('procurement.request_list') }}</span>
                    </a>
                    @if(Auth::user()->hasRole('super_admin') || Auth::user()->hasPermission('manage_purchase_requests'))
                    <a href="{{ Route::has('procurement.requests.create') ? route('procurement.requests.create') : '#requestsCreate' }}" class="sidebar-sublink {{ request()->routeIs('procurement.requests.create') ? 'active' : '' }}">
                        <i class="bi bi-plus-circle"></i>
                        <span>{{ __('procurement.create_request') }}</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Purchase Orders --}}
            @if(Auth::user()->hasRole('super_admin') || Auth::user()->hasAnyPermission(['view_purchase_orders', 'manage_purchase_orders']))
            @php
                $isOrdersActive = request()->routeIs('procurement.orders.*');
            @endphp
            <a class="sidebar-link sidebar-parent {{ $isOrdersActive ? 'active-parent' : '' }}"
               data-bs-toggle="collapse"
               href="#menuProcurementOrders"
               role="button"
               aria-expanded="{{ $isOrdersActive ? 'true' : 'false' }}"
               aria-controls="menuProcurementOrders">
                <i class="bi bi-box-seam"></i>
                <span>{{ __('procurement.purchase_orders') }}</span>
                <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
            </a>
            <div class="collapse {{ $isOrdersActive ? 'show' : '' }}" id="menuProcurementOrders">
                <div class="sidebar-submenu">
                    <a href="{{ Route::has('procurement.orders.index') ? route('procurement.orders.index') : '#ordersList' }}" class="sidebar-sublink {{ request()->routeIs('procurement.orders.index') ? 'active' : '' }}">
                        <i class="bi bi-boxes"></i>
                        <span>{{ __('procurement.order_list') }}</span>
                    </a>
                </div>
            </div>
            @endif

            {{-- Vendor Bills & Payments --}}
            @php
                $isBillsActive = request()->routeIs('procurement.bills.*');
            @endphp
            <a class="sidebar-link sidebar-parent {{ $isBillsActive ? 'active-parent' : '' }}"
               data-bs-toggle="collapse"
               href="#menuProcurementBills"
               role="button"
               aria-expanded="{{ $isBillsActive ? 'true' : 'false' }}"
               aria-controls="menuProcurementBills">
                <i class="bi bi-receipt"></i>
                <span>{{ __('procurement.vendor_bills') }}</span>
                <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
            </a>
            <div class="collapse {{ $isBillsActive ? 'show' : '' }}" id="menuProcurementBills">
                <div class="sidebar-submenu">
                    <a href="{{ Route::has('procurement.bills.index') ? route('procurement.bills.index') : '#billsList' }}" class="sidebar-sublink {{ request()->routeIs('procurement.bills.index') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-ruled"></i>
                        <span>{{ __('procurement.bill_list') }}</span>
                    </a>
                </div>
            </div>

            {{-- Procurement Reports --}}
            @php
                $isProcurementReportsActive = request()->routeIs('procurement.reports.*');
            @endphp
            <a class="sidebar-link sidebar-parent {{ $isProcurementReportsActive ? 'active-parent' : '' }}"
               data-bs-toggle="collapse"
               href="#menuProcurementReports"
               role="button"
               aria-expanded="{{ $isProcurementReportsActive ? 'true' : 'false' }}"
               aria-controls="menuProcurementReports">
                <i class="bi bi-bar-chart-line"></i>
                <span>{{ __('procurement.procurement_reports') }}</span>
                <i class="bi bi-chevron-down ms-auto sidebar-chevron"></i>
            </a>
            <div class="collapse {{ $isProcurementReportsActive ? 'show' : '' }}" id="menuProcurementReports">
                <div class="sidebar-submenu">
                    <a href="{{ Route::has('procurement.reports.budget-vs-actual') ? route('procurement.reports.budget-vs-actual') : '#repBudgetVsActual' }}" class="sidebar-sublink {{ request()->routeIs('procurement.reports.budget-vs-actual') ? 'active' : '' }}">
                        <i class="bi bi-graph-up"></i>
                        <span>{{ __('procurement.budget_vs_actual') }}</span>
                    </a>
                    <a href="{{ Route::has('procurement.reports.requests') ? route('procurement.reports.requests') : '#repRequests' }}" class="sidebar-sublink {{ request()->routeIs('procurement.reports.requests') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>{{ __('procurement.purchase_request_report') }}</span>
                    </a>
                    <a href="{{ Route::has('procurement.reports.orders') ? route('procurement.reports.orders') : '#repOrders' }}" class="sidebar-sublink {{ request()->routeIs('procurement.reports.orders') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-check"></i>
                        <span>{{ __('procurement.purchase_order_report') }}</span>
                    </a>
                    <a href="{{ Route::has('procurement.reports.outstanding-bills') ? route('procurement.reports.outstanding-bills') : '#repOutstandingBills' }}" class="sidebar-sublink {{ request()->routeIs('procurement.reports.outstanding-bills') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-x"></i>
                        <span>{{ __('procurement.vendor_outstanding_report') }}</span>
                    </a>
                </div>
            </div>
        @endif

        <!-- Module 6: Venues & Hall Bookings (P2) -->
        <div class="sidebar-heading">{{ __('nav.venues') }}</div>
        <a href="#venueModule" class="sidebar-link disabled-future" title="{{ __('common.under_construction') }}">
            <i class="bi bi-building"></i>
            <span>{{ __('nav.venues_list') }}</span>
            <span class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">P2</span>
        </a>

        <!-- Module 7: Operations & Seva (P2) -->
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

        <!-- User Profile -->
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
            v1.6 (Phase 6 Budget & Procurement)
        </div>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
