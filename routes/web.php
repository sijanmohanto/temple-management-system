<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CollectionDashboardController;
use App\Http\Controllers\CollectionPaymentController;
use App\Http\Controllers\CollectionReportController;
use App\Http\Controllers\CommitteeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventDashboardController;
use App\Http\Controllers\EventSettlementController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\FinancialAccountController;
use App\Http\Controllers\Finance\FinancialDashboardController;
use App\Http\Controllers\Finance\FinancialReportController;
use App\Http\Controllers\Finance\FundTransferController;
use App\Http\Controllers\Finance\JournalEntryController;
use App\Http\Controllers\Finance\ManualIncomeController;
use App\Http\Controllers\Finance\OpeningBalanceController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MonthlyContributionController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PledgeController;
use App\Http\Controllers\Procurement\BudgetController;
use App\Http\Controllers\Procurement\BudgetReportController;
use App\Http\Controllers\Procurement\PurchaseOrderController;
use App\Http\Controllers\Procurement\PurchaseRequestController;
use App\Http\Controllers\Procurement\VendorBillController;
use App\Http\Controllers\Procurement\VendorController;
use App\Http\Controllers\Procurement\VendorPaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Language switcher (available to guests and authenticated users)
Route::get('/lang/{locale}', [LanguageController::class, 'switchLocale'])->name('lang.switch');

/*
|--------------------------------------------------------------------------
| Authentication Routes (Guest Only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    /*
    |--------------------------------------------------------------------------
    | Phase 2: People, Family & Committee Management Routes
    |--------------------------------------------------------------------------
    */
    // People Management
    Route::get('/people/search', [PersonController::class, 'search'])->name('people.search');
    Route::get('/people', [PersonController::class, 'index'])->name('people.index');
    Route::get('/people/create', [PersonController::class, 'create'])->name('people.create');
    Route::post('/people', [PersonController::class, 'store'])->name('people.store');
    Route::post('/people/check-duplicates', [PersonController::class, 'checkDuplicates'])->name('people.check-duplicates');
    Route::get('/people/{person}', [PersonController::class, 'show'])->name('people.show');
    Route::get('/people/{person}/edit', [PersonController::class, 'edit'])->name('people.edit');
    Route::put('/people/{person}', [PersonController::class, 'update'])->name('people.update');
    Route::post('/people/{person}/toggle-status', [PersonController::class, 'toggleStatus'])->name('people.toggle-status');

    // Family Management
    Route::get('/families/search', [FamilyController::class, 'search'])->name('families.search');
    Route::get('/families', [FamilyController::class, 'index'])->name('families.index');
    Route::get('/families/create', [FamilyController::class, 'create'])->name('families.create');
    Route::post('/families', [FamilyController::class, 'store'])->name('families.store');
    Route::get('/families/{family}', [FamilyController::class, 'show'])->name('families.show');
    Route::get('/families/{family}/edit', [FamilyController::class, 'edit'])->name('families.edit');
    Route::put('/families/{family}', [FamilyController::class, 'update'])->name('families.update');
    Route::delete('/families/{family}', [FamilyController::class, 'destroy'])->name('families.destroy');
    Route::post('/families/{family}/members', [FamilyController::class, 'addMember'])->name('families.members.add');
    Route::delete('/families/{family}/members/{person}', [FamilyController::class, 'removeMember'])->name('families.members.remove');

    // Committee Management
    Route::get('/committees', [CommitteeController::class, 'index'])->name('committees.index');
    Route::get('/committees/create', [CommitteeController::class, 'create'])->name('committees.create');
    Route::post('/committees', [CommitteeController::class, 'store'])->name('committees.store');
    Route::get('/committees/{committee}', [CommitteeController::class, 'show'])->name('committees.show');
    Route::get('/committees/{committee}/edit', [CommitteeController::class, 'edit'])->name('committees.edit');
    Route::put('/committees/{committee}', [CommitteeController::class, 'update'])->name('committees.update');
    Route::post('/committees/{committee}/members', [CommitteeController::class, 'addMember'])->name('committees.members.add');
    Route::put('/committees/{committee}/members/{membership}', [CommitteeController::class, 'updateMember'])->name('committees.members.update');
    Route::post('/committees/{committee}/members/{membership}/end', [CommitteeController::class, 'endMembership'])->name('committees.members.end');

    // Event & Puja Management
    Route::get('/events/dashboard', [EventDashboardController::class, 'index'])->name('events.dashboard');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::post('/events/{event}/transition', [EventController::class, 'transition'])->name('events.transition');
    Route::post('/events/{event}/close', [EventController::class, 'close'])->name('events.close');
    Route::post('/events/{event}/reopen', [EventController::class, 'reopen'])->name('events.reopen');
    Route::post('/events/{event}/adjust', [EventController::class, 'adjust'])->name('events.adjust');
    Route::post('/events/{event}/settle-funds', [EventSettlementController::class, 'settle'])->name('events.settle-funds');

    // Event Categories
    Route::get('/events-categories', [EventCategoryController::class, 'index'])->name('events.categories.index');
    Route::post('/events-categories', [EventCategoryController::class, 'store'])->name('events.categories.store');
    Route::put('/events-categories/{category}', [EventCategoryController::class, 'update'])->name('events.categories.update');
    Route::delete('/events-categories/{category}', [EventCategoryController::class, 'destroy'])->name('events.categories.destroy');
    Route::post('/events-categories/{category}/toggle-status', [EventCategoryController::class, 'toggleStatus'])->name('events.categories.toggle-status');

    // Phase 4: Donations, Pledges & Collections
    Route::get('/collections/dashboard', [CollectionDashboardController::class, 'index'])->name('collections.dashboard');

    // Pledges
    Route::get('/pledges', [PledgeController::class, 'index'])->name('pledges.index');
    Route::get('/pledges/create', [PledgeController::class, 'create'])->name('pledges.create');
    Route::post('/pledges', [PledgeController::class, 'store'])->name('pledges.store');
    Route::get('/pledges/{pledge}', [PledgeController::class, 'show'])->name('pledges.show');
    Route::get('/pledges/{pledge}/edit', [PledgeController::class, 'edit'])->name('pledges.edit');
    Route::put('/pledges/{pledge}', [PledgeController::class, 'update'])->name('pledges.update');
    Route::delete('/pledges/{pledge}', [PledgeController::class, 'destroy'])->name('pledges.destroy');

    // Collection Payments
    Route::get('/collections', [CollectionPaymentController::class, 'index'])->name('collections.index');
    Route::get('/collections/create', [CollectionPaymentController::class, 'create'])->name('collections.create');
    Route::post('/collections', [CollectionPaymentController::class, 'store'])->name('collections.store');
    Route::get('/collections/payer-obligations', [CollectionPaymentController::class, 'payerObligations'])->name('collections.payer-obligations');
    Route::get('/collections/{payment}', [CollectionPaymentController::class, 'show'])->name('collections.show');
    Route::post('/collections/{payment}/verify', [CollectionPaymentController::class, 'verify'])->name('collections.verify');
    Route::post('/collections/{payment}/reject', [CollectionPaymentController::class, 'reject'])->name('collections.reject');
    Route::post('/collections/{payment}/reverse', [CollectionPaymentController::class, 'reverse'])->name('collections.reverse');

    // Monthly Committee Contributions
    Route::get('/committee-contributions', [MonthlyContributionController::class, 'index'])->name('contributions.index');
    Route::post('/committee-contributions', [MonthlyContributionController::class, 'store'])->name('contributions.store');
    Route::post('/committee-contributions/bulk-generate', [MonthlyContributionController::class, 'bulkGenerate'])->name('contributions.bulk-generate');
    Route::post('/committee-contributions/{contribution}/waive', [MonthlyContributionController::class, 'waive'])->name('contributions.waive');

    // Receipts
    Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/{receipt}/print', [ReceiptController::class, 'print'])->name('receipts.print');
    Route::get('/receipts/{receipt}/download', [ReceiptController::class, 'download'])->name('receipts.download');

    // Collection Reports
    Route::get('/collections-reports', [CollectionReportController::class, 'index'])->name('collections.reports.index');
    Route::get('/collections-reports/event-summary', [CollectionReportController::class, 'eventSummary'])->name('collections.reports.event-summary');
    Route::get('/collections-reports/outstanding-pledges', [CollectionReportController::class, 'outstandingPledges'])->name('collections.reports.outstanding-pledges');
    Route::get('/collections-reports/committee-contributions', [CollectionReportController::class, 'committeeContributions'])->name('collections.reports.committee-contributions');

    // Phase 5: Finance & Accounting Management
    Route::prefix('finance')->name('finance.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [FinancialDashboardController::class, 'index'])->name('dashboard');

        // Chart of Accounts
        Route::get('/accounts', [FinancialAccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts', [FinancialAccountController::class, 'store'])->name('accounts.store');
        Route::get('/accounts/{account}', [FinancialAccountController::class, 'show'])->name('accounts.show');
        Route::put('/accounts/{account}', [FinancialAccountController::class, 'update'])->name('accounts.update');

        // Double-entry Journals
        Route::get('/journals', [JournalEntryController::class, 'index'])->name('journals.index');
        Route::get('/journals/{journal}', [JournalEntryController::class, 'show'])->name('journals.show');
        Route::post('/journals/{journal}/reverse', [JournalEntryController::class, 'reverse'])->name('journals.reverse');

        // Expenses
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
        Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('/expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');
        Route::post('/expenses/{expense}/reverse', [ExpenseController::class, 'reverse'])->name('expenses.reverse');

        // Fund Transfers
        Route::get('/transfers', [FundTransferController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/create', [FundTransferController::class, 'create'])->name('transfers.create');
        Route::post('/transfers', [FundTransferController::class, 'store'])->name('transfers.store');
        Route::post('/transfers/{transfer}/reverse', [FundTransferController::class, 'reverse'])->name('transfers.reverse');

        // Miscellaneous Income
        Route::get('/income', [ManualIncomeController::class, 'index'])->name('income.index');
        Route::get('/income/create', [ManualIncomeController::class, 'create'])->name('income.create');
        Route::post('/income', [ManualIncomeController::class, 'store'])->name('income.store');

        // Opening Balances
        Route::get('/opening-balances', [OpeningBalanceController::class, 'index'])->name('opening-balances.index');
        Route::post('/opening-balances', [OpeningBalanceController::class, 'store'])->name('opening-balances.store');

        // Financial Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            // Clear Income & Expense Reports (Revised)
            Route::get('/income', [FinancialReportController::class, 'incomeReport'])->name('income');
            Route::get('/income/details/{type}/{id}', [FinancialReportController::class, 'incomeDetails'])->name('income.details');
            Route::get('/expenses', [FinancialReportController::class, 'expenseReport'])->name('expenses');
            Route::get('/expenses/details/{id}', [FinancialReportController::class, 'expenseDetails'])->name('expenses.details');
            Route::get('/people-search', [FinancialReportController::class, 'searchPeople'])->name('people-search');

            Route::get('/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('trial-balance');
            Route::get('/general-ledger', [FinancialReportController::class, 'generalLedger'])->name('general-ledger');
            Route::get('/income-expense', [FinancialReportController::class, 'incomeExpense'])->name('income-expense');
            Route::get('/cash-book', [FinancialReportController::class, 'cashBook'])->name('cash-book');
            Route::get('/account-statements', [FinancialReportController::class, 'accountStatement'])->name('account-statement');
            Route::get('/account-balances', [FinancialReportController::class, 'accountBalances'])->name('account-balances');
            Route::get('/event-summary', [FinancialReportController::class, 'eventSummary'])->name('event-summary');
        });
    });

    // Phase 6: Budget & Procurement Management
    Route::prefix('procurement')->name('procurement.')->group(function () {
        // Budgets
        Route::prefix('budgets')->name('budgets.')->group(function () {
            Route::get('/', [BudgetController::class, 'index'])->name('index');
            Route::get('/create', [BudgetController::class, 'create'])->name('create');
            Route::post('/', [BudgetController::class, 'store'])->name('store');
            Route::get('/{budget}', [BudgetController::class, 'show'])->name('show');
            Route::get('/{budget}/edit', [BudgetController::class, 'edit'])->name('edit');
            Route::put('/{budget}', [BudgetController::class, 'update'])->name('update');
            Route::post('/{budget}/submit', [BudgetController::class, 'submit'])->name('submit');
            Route::post('/{budget}/approve', [BudgetController::class, 'approve'])->name('approve');
            Route::post('/{budget}/reject', [BudgetController::class, 'reject'])->name('reject');
            Route::post('/{budget}/cancel', [BudgetController::class, 'cancel'])->name('cancel');
        });

        // Vendors
        Route::prefix('vendors')->name('vendors.')->group(function () {
            Route::get('/search', [VendorController::class, 'search'])->name('search');
            Route::get('/', [VendorController::class, 'index'])->name('index');
            Route::get('/create', [VendorController::class, 'create'])->name('create');
            Route::post('/', [VendorController::class, 'store'])->name('store');
            Route::get('/{vendor}', [VendorController::class, 'show'])->name('show');
            Route::get('/{vendor}/edit', [VendorController::class, 'edit'])->name('edit');
            Route::put('/{vendor}', [VendorController::class, 'update'])->name('update');
            Route::post('/{vendor}/toggle-status', [VendorController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{vendor}', [VendorController::class, 'destroy'])->name('destroy');
        });

        // Purchase Requests
        Route::prefix('requests')->name('requests.')->group(function () {
            Route::get('/', [PurchaseRequestController::class, 'index'])->name('index');
            Route::get('/create', [PurchaseRequestController::class, 'create'])->name('create');
            Route::post('/', [PurchaseRequestController::class, 'store'])->name('store');
            Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');
            Route::get('/{purchaseRequest}/edit', [PurchaseRequestController::class, 'edit'])->name('edit');
            Route::put('/{purchaseRequest}', [PurchaseRequestController::class, 'update'])->name('update');
            Route::post('/{purchaseRequest}/submit', [PurchaseRequestController::class, 'submit'])->name('submit');
            Route::post('/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve'])->name('approve');
            Route::post('/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject'])->name('reject');
            Route::post('/{purchaseRequest}/cancel', [PurchaseRequestController::class, 'cancel'])->name('cancel');
        });

        // Purchase Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
            Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
            Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
            Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
            Route::get('/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('edit');
            Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update');
            Route::post('/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('approve');
            Route::post('/{purchaseOrder}/send', [PurchaseOrderController::class, 'sendOrder'])->name('send');
            Route::post('/{purchaseOrder}/complete', [PurchaseOrderController::class, 'complete'])->name('complete');
            Route::post('/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel');
            Route::get('/{purchaseOrder}/print', [PurchaseOrderController::class, 'printOrder'])->name('print');
        });

        // Vendor Bills
        Route::prefix('bills')->name('bills.')->group(function () {
            Route::get('/po-items/{purchaseOrder}', [VendorBillController::class, 'getBillablePoItems'])->name('po-items');
            Route::get('/', [VendorBillController::class, 'index'])->name('index');
            Route::get('/create', [VendorBillController::class, 'create'])->name('create');
            Route::post('/', [VendorBillController::class, 'store'])->name('store');
            Route::get('/{vendorBill}', [VendorBillController::class, 'show'])->name('show');
            Route::get('/{vendorBill}/edit', [VendorBillController::class, 'edit'])->name('edit');
            Route::put('/{vendorBill}', [VendorBillController::class, 'update'])->name('update');
            Route::post('/{vendorBill}/submit', [VendorBillController::class, 'submit'])->name('submit');
            Route::post('/{vendorBill}/verify', [VendorBillController::class, 'verify'])->name('verify');
            Route::post('/{vendorBill}/approve', [VendorBillController::class, 'approve'])->name('approve');
            Route::post('/{vendorBill}/reject', [VendorBillController::class, 'reject'])->name('reject');
            Route::post('/{vendorBill}/cancel', [VendorBillController::class, 'cancel'])->name('cancel');
            Route::get('/{vendorBill}/print', [VendorBillController::class, 'printBill'])->name('print');
        });

        // Vendor Payments
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/payable-bills/{vendor}', [VendorPaymentController::class, 'getPayableBills'])->name('payable-bills');
            Route::get('/', [VendorPaymentController::class, 'index'])->name('index');
            Route::get('/create', [VendorPaymentController::class, 'create'])->name('create');
            Route::post('/', [VendorPaymentController::class, 'store'])->name('store');
            Route::get('/{vendorPayment}', [VendorPaymentController::class, 'show'])->name('show');
            Route::get('/{vendorPayment}/edit', [VendorPaymentController::class, 'edit'])->name('edit');
            Route::put('/{vendorPayment}', [VendorPaymentController::class, 'update'])->name('update');
            Route::post('/{vendorPayment}/submit', [VendorPaymentController::class, 'submit'])->name('submit');
            Route::post('/{vendorPayment}/verify', [VendorPaymentController::class, 'verify'])->name('verify');
            Route::post('/{vendorPayment}/approve', [VendorPaymentController::class, 'approve'])->name('approve');
            Route::post('/{vendorPayment}/post', [VendorPaymentController::class, 'post'])->name('post');
            Route::post('/{vendorPayment}/approve-and-post', [VendorPaymentController::class, 'approveAndPost'])->name('approve-and-post');
            Route::post('/{vendorPayment}/reject', [VendorPaymentController::class, 'reject'])->name('reject');
            Route::post('/{vendorPayment}/cancel', [VendorPaymentController::class, 'cancel'])->name('cancel');
            Route::post('/{vendorPayment}/reverse', [VendorPaymentController::class, 'reverse'])->name('reverse');
            Route::get('/{vendorPayment}/print', [VendorPaymentController::class, 'printPayment'])->name('print');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/budget-vs-actual', [BudgetReportController::class, 'budgetVsActual'])->name('budget-vs-actual');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Phase 1 Role-Protected Verification Endpoints
    | (Foundation routes demonstrating server-enforced role authorization)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs');
    });

    Route::middleware('role:super_admin,treasurer')->group(function () {
        Route::get('/finance/overview', function () {
            return view('modules.finance_preview');
        })->name('finance.overview');
    });
});
