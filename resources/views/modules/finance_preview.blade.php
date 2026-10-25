@extends('layouts.app')

@section('title', __('nav.finance'))

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="bi bi-cash-stack text-success me-2"></i> {{ __('nav.finance') }} &amp; {{ __('nav.ledger') }}
            </h1>
            <p class="text-muted small mb-0">
                Centralized financial ledger architecture blueprint (Protected by <code>role:super_admin,treasurer</code>)
            </p>
        </div>
        <div>
            <span class="badge bg-success px-3 py-2 fs-6">
                <i class="bi bi-shield-check me-1"></i> Treasurer &amp; Admin Authorized
            </span>
        </div>
    </div>

    <!-- Architectural Blueprint Notice -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-3">
                <i class="bi bi-journal-check text-primary me-2"></i> Phase 2 Central Financial Ledger Architecture Rules
            </h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-light border h-100">
                        <div class="fw-bold text-success mb-1">
                            <i class="bi bi-check2-circle me-1"></i> Explicit Payment Allocation
                        </div>
                        <p class="text-muted small mb-0">
                            Transactions can never be counted twice. Pledges, puja donations, monthly committee contributions, and hall rent advances use isolated voucher allocation.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-light border h-100">
                        <div class="fw-bold text-primary mb-1">
                            <i class="bi bi-lock-fill me-1"></i> Immutable Confirmed Records
                        </div>
                        <p class="text-muted small mb-0">
                            Confirmed ledger entries cannot be overwritten or deleted. Corrections require authorized reversing adjustment entries with audit accountability.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-3 bg-light border h-100">
                        <div class="fw-bold text-warning mb-1">
                            <i class="bi bi-diagram-3 me-1"></i> Double-Entry Core
                        </div>
                        <p class="text-muted small mb-0">
                            Standard chart of accounts: Cash in Hand, Bank Accounts, Devotee General Fund, Puja Funds, Building Fund, and Expenditure Heads.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
