@extends('layouts.app')

@section('title', __('donations.dashboard'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('donations.collections_management') }}</h1>
            <p class="text-muted small mb-0">{{ __('donations.dashboard') }} &bull; {{ __('donations.payment_year') }}: <strong>{{ $currentYear }}</strong></p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if(Auth::user()->hasPermission('record_collections'))
                <a href="{{ route('collections.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                    <i class="bi bi-cash-plus me-1"></i> {{ __('donations.submit_payment') }}
                </a>
            @endif
            @if(Auth::user()->hasPermission('manage_pledges'))
                <a href="{{ route('pledges.create') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                    <i class="bi bi-journal-plus me-1"></i> {{ __('donations.add_pledge') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('donations.total_verified') }}</div>
                <div class="fs-4 fw-bold text-success mt-1">৳{{ number_format($metrics['total_verified_amount'], 2) }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-check2-circle text-success me-1"></i>{{ $metrics['total_verified_count'] }} {{ __('donations.payments') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('donations.pending_verification_amount') }}</div>
                <div class="fs-4 fw-bold text-warning-emphasis mt-1">৳{{ number_format($metrics['pending_amount'], 2) }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-hourglass-split text-warning me-1"></i>{{ $metrics['pending_count'] }} {{ __('donations.status_pending_verification') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('donations.total_pledged') }}</div>
                <div class="fs-4 fw-bold text-primary mt-1">৳{{ number_format($metrics['pledge_total_amount'], 2) }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-journal-bookmark text-primary me-1"></i>{{ $metrics['pledge_count'] }} {{ __('donations.pledges') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('donations.total_outstanding') }}</div>
                <div class="fs-4 fw-bold text-danger mt-1">৳{{ number_format($metrics['pledge_outstanding'], 2) }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-exclamation-circle text-danger me-1"></i>{{ __('donations.status_unpaid') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('donations.committee_contributions') }}</div>
                <div class="fs-4 fw-bold text-dark mt-1">৳{{ number_format($metrics['monthly_committee_collections'], 2) }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-calendar-check text-dark me-1"></i>৳{{ number_format($metrics['committee_contributions_outstanding'], 2) }} dues</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="text-muted small fw-semibold">{{ __('donations.type_event_donation') }}</div>
                <div class="fs-4 fw-bold text-info mt-1">৳{{ number_format($metrics['event_voluntary_collections'], 2) }}</div>
                <div class="small text-muted mt-auto pt-2"><i class="bi bi-heart text-info me-1"></i>Voluntary</div>
            </div>
        </div>
    </div>

    <!-- Pending Verification Alert Queue Banner -->
    @if($metrics['pending_count'] > 0)
        <div class="alert alert-warning border-0 shadow-sm rounded-4 p-3 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">{{ __('donations.pending_verification_queue') }}: {{ $metrics['pending_count'] }} {{ __('donations.payments') }} (৳{{ number_format($metrics['pending_amount'], 2) }})</h6>
                    <small class="text-muted">{{ __('donations.unverified_submission_notice') }}</small>
                </div>
            </div>
            <div>
                <a href="{{ route('collections.index', ['status' => 'pending_verification']) }}" class="btn btn-warning rounded-pill px-3 text-dark fw-bold">
                    <i class="bi bi-check-circle me-1"></i> Review Queue
                </a>
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- Recent Verified Payments -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-cash-stack me-2 text-success"></i>{{ __('donations.recent_verified_payments') }}</h5>
                    <a href="{{ route('collections.index', ['status' => 'verified']) }}" class="btn btn-sm btn-link text-decoration-none">{{ __('common.view_all') }}</a>
                </div>
                <div class="card-body p-4">
                    @if($recentVerified->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <p class="mb-0">No verified collections recorded yet.</p>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-3">
                            @foreach($recentVerified as $p)
                                <div class="p-3 border rounded-3 bg-light bg-opacity-50 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-light text-dark border font-monospace">{{ $p->payment_code }}</span>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.72rem;">
                                                ৳{{ number_format((float) $p->amount, 2) }}
                                            </span>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">
                                                {{ __('donations.type_'.$p->collection_type) }}
                                            </span>
                                        </div>
                                        <div class="fw-bold text-dark">{{ $p->payer_display_name }}</div>
                                        <div class="small text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>{{ $p->payment_date->format('d M Y') }} &bull;
                                            <i class="bi bi-credit-card me-1"></i>{{ __('donations.method_'.$p->payment_method) }}
                                        </div>
                                    </div>
                                    <div class="text-md-end d-flex align-items-center gap-2">
                                        @if($p->receipt)
                                            <a href="{{ route('receipts.print', $p->receipt) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="{{ __('donations.print_receipt') }}">
                                                <i class="bi bi-printer me-1"></i> {{ $p->receipt->receipt_number }}
                                            </a>
                                        @endif
                                        <a href="{{ route('collections.show', $p) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Active Events Collection Summary -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar-event me-2 text-primary"></i>Active Events Summary</h5>
                    <a href="{{ route('collections.reports.event-summary') }}" class="btn btn-sm btn-link text-decoration-none">{{ __('donations.reports') }}</a>
                </div>
                <div class="card-body p-4">
                    @if($activeEvents->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <p class="mb-0">No active events currently operating.</p>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-3">
                            @foreach($activeEvents as $evt)
                                <div class="p-3 border rounded-3">
                                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                                        <h6 class="fw-bold text-dark mb-0">{{ $evt->name }}</h6>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.7rem;">{{ $evt->event_year }}</span>
                                    </div>
                                    <div class="small text-muted mb-2"><i class="bi bi-calendar3 me-1"></i>{{ $evt->start_date->format('d M') }} - {{ $evt->end_date->format('d M Y') }}</div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span><i class="bi bi-journal-bookmark me-1"></i>{{ $evt->pledges_count }} Pledges</span>
                                        <a href="{{ route('pledges.index', ['event_id' => $evt->id]) }}" class="text-decoration-none text-primary">View Pledges &rarr;</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
