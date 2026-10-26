@extends('layouts.app')

@section('title', __('donations.reports'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('donations.reports') }}</h1>
            <p class="text-muted small mb-0">{{ __('donations.collections_management') }} &bull; Financial &amp; Obligation Reports</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('collections.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('donations.dashboard') }}
            </a>
        </div>
    </div>

    <!-- Report Directory Cards -->
    <div class="row g-4">
        <!-- Event-wise Collection Summary -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 rounded-4 bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-calendar-event fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">{{ __('donations.event_summary_report') }}</h5>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary">Puja &amp; Event Finances</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        Comprehensive breakdown of pledges, verified collections (pledge &amp; voluntary), pending verification amounts, and outstanding balances per festival or event.
                    </p>
                    <a href="{{ route('collections.reports.event-summary') }}" class="btn btn-outline-primary rounded-pill w-100 mt-2">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> View Event Summary
                    </a>
                </div>
            </div>
        </div>

        <!-- Outstanding Pledges Ledger -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 rounded-4 bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-hourglass-split fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">{{ __('donations.outstanding_pledges_report') }}</h5>
                            <span class="badge bg-danger bg-opacity-10 text-danger">Unpaid Manat Ledger</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        Detailed ledger of all unfulfilled and partially paid pledges with devotee/family contacts, pledged amounts, verified payments, and remaining due balances.
                    </p>
                    <a href="{{ route('collections.reports.outstanding-pledges') }}" class="btn btn-outline-danger rounded-pill w-100 mt-2">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> View Outstanding Ledger
                    </a>
                </div>
            </div>
        </div>

        <!-- Monthly Committee Contributions -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 rounded-4 bg-success bg-opacity-10 text-success">
                            <i class="bi bi-people-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">{{ __('donations.committee_contributions_report') }}</h5>
                            <span class="badge bg-success bg-opacity-10 text-success">12-Month Committee Dues</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        Month-by-month financial summary of committee membership contributions across the selected calendar year, tracking expected, collected, and due temple funds.
                    </p>
                    <a href="{{ route('collections.reports.committee-contributions') }}" class="btn btn-outline-success rounded-pill w-100 mt-2">
                        <i class="bi bi-calendar3 me-1"></i> View Committee Dues Ledger
                    </a>
                </div>
            </div>
        </div>

        <!-- Official Receipt Register -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 rounded-4 bg-warning bg-opacity-10 text-warning-emphasis">
                            <i class="bi bi-receipt fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">{{ __('donations.receipt_register_report') }}</h5>
                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis">Sequential Audit Log</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        Master audit register of all generated sequential money receipts with payer information, verified amounts, timestamps, issuers, and void statuses.
                    </p>
                    <a href="{{ route('receipts.index') }}" class="btn btn-outline-warning rounded-pill w-100 mt-2 text-dark">
                        <i class="bi bi-journal-text me-1"></i> View Receipt Register
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
