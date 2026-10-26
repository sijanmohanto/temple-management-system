<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CommitteeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventDashboardController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ProfileController;
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

    // Event Categories
    Route::get('/events-categories', [EventCategoryController::class, 'index'])->name('events.categories.index');
    Route::post('/events-categories', [EventCategoryController::class, 'store'])->name('events.categories.store');
    Route::put('/events-categories/{category}', [EventCategoryController::class, 'update'])->name('events.categories.update');
    Route::delete('/events-categories/{category}', [EventCategoryController::class, 'destroy'])->name('events.categories.destroy');
    Route::post('/events-categories/{category}/toggle-status', [EventCategoryController::class, 'toggleStatus'])->name('events.categories.toggle-status');

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
