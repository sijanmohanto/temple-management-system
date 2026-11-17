@extends('layouts.public')

@section('title', $payment->payment_code . ' — ' . __('portal.public.my_contributions') . ' — ' . __('common.app_name'))

@section('content')
<div class="py-2">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 small bg-white p-2 px-3 rounded-3 shadow-sm border">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> {{ __('portal.public.home') }}
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('public.payments.history') }}" class="text-decoration-none text-muted">
                    {{ __('portal.public.my_contributions') }}
                </a>
            </li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                {{ $payment->payment_code }}
            </li>
        </ol>
    </nav>

    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background: linear-gradient(135deg, #560e15 0%, #75141c 50%, #b45309 100%);">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-warning text-dark px-3 py-1 font-monospace fs-6">{{ $payment->payment_code }}</span>
                        @if($payment->isVerified())
                            <span class="badge bg-success px-3 py-1 fs-6"><i class="bi bi-check-circle-fill me-1"></i>{{ __('portal.public.status_verified') }}</span>
                        @elseif($payment->isRejected())
                            <span class="badge bg-danger px-3 py-1 fs-6"><i class="bi bi-x-circle-fill me-1"></i>{{ __('portal.public.status_rejected') }}</span>
                        @elseif($payment->isReversed())
                            <span class="badge bg-secondary px-3 py-1 fs-6"><i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('portal.public.status_reversed') }}</span>
                        @else
                            <span class="badge bg-warning text-dark px-3 py-1 fs-6"><i class="bi bi-hourglass-split me-1"></i>{{ __('portal.public.status_pending') }}</span>
                        @endif
                    </div>
                    <h1 class="h2 fw-bold mb-1">৳ {{ number_format((float) $payment->amount, 2) }}</h1>
                    <p class="text-white-50 mb-0 fs-6">
                        {{ $payment->payer_display_name }} &bull; {{ $payment->payment_date ? $payment->payment_date->format('d M Y') : '—' }}
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('public.payments.history') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> {{ __('portal.public.back_to_contributions') }}
                    </a>
                    @if($payment->isVerified() && $payment->receipt && ! $payment->receipt->isVoided())
                        <a href="{{ route('public.payments.receipt', $payment->receipt->receipt_number) }}" target="_blank" class="btn btn-light btn-sm fw-semibold">
                            <i class="bi bi-receipt me-1"></i> {{ __('portal.public.view_receipt') }}
                        </a>
                        <a href="{{ route('public.payments.receipt.download', $payment->receipt->receipt_number) }}" class="btn btn-warning btn-sm fw-semibold text-dark">
                            <i class="bi bi-download me-1"></i> {{ __('portal.payments.download_receipt') }}
                        </a>
                    @endif
                    <a href="{{ route('public.complaints.create', ['payment_code' => $payment->payment_code]) }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-flag me-1"></i> {{ __('portal.public.report_issue') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Alerts -->
    @if($payment->isRejected())
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start gap-3 p-3 rounded-4 mb-4">
            <i class="bi bi-exclamation-octagon-fill fs-3 text-danger"></i>
            <div>
                <h6 class="fw-bold mb-1 text-danger">{{ __('portal.public.payment_rejected_title') }}</h6>
                <p class="mb-0 text-danger-emphasis">
                    <strong>{{ __('donations.rejection_reason') }}:</strong> {{ $payment->rejection_reason ?: __('portal.public.no_reason_specified') }}
                </p>
            </div>
        </div>
    @elseif($payment->isReversed())
        <div class="alert alert-secondary border-danger border-start border-4 shadow-sm p-3 rounded-4 mb-4">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-arrow-counterclockwise fs-4 text-danger"></i>
                <h6 class="fw-bold mb-0 text-danger">{{ __('portal.public.payment_reversed_title') }}</h6>
            </div>
            <p class="mb-1 text-secondary">
                <strong>{{ __('donations.reversal_reason') }}:</strong> {{ $payment->reversal_reason ?: __('portal.public.no_reason_specified') }}
            </p>
            @if($payment->reversed_at)
                <small class="text-muted d-block">
                    {{ __('donations.reversed_at') }}: {{ $payment->reversed_at->format('d M Y, h:i A') }} &bull; {{ __('portal.payments.reversal_receipt_voided_notice') }}
                </small>
            @endif
        </div>
    @elseif(! $payment->isVerified())
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 p-3 rounded-4 mb-4">
            <i class="bi bi-clock-history fs-3 text-warning"></i>
            <div>
                <h6 class="fw-bold mb-1 text-warning-emphasis">{{ __('portal.public.payment_pending_title') }}</h6>
                <p class="mb-0 small text-warning-emphasis">
                    {{ __('portal.public.payment_pending_help') }}
                </p>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Details -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-card-checklist me-2 text-primary"></i>{{ __('portal.public.payment_details_title') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small d-block mb-1">{{ __('portal.public.payment_code') }}</label>
                            <span class="font-monospace fw-bold text-dark fs-6">{{ $payment->payment_code }}</span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block mb-1">{{ __('portal.public.payment_date') }}</label>
                            <span class="fw-semibold text-dark">{{ $payment->payment_date ? $payment->payment_date->format('d M Y') : '—' }}</span>
                            @if($payment->created_at)
                                <small class="text-muted d-block">{{ $payment->created_at->format('h:i A') }}</small>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block mb-1">{{ __('portal.public.amount') }}</label>
                            <span class="font-monospace fw-bold fs-5 text-success">৳ {{ number_format((float) $payment->amount, 2) }}</span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block mb-1">{{ __('portal.public.select_payment_method') }}</label>
                            <span class="badge bg-light text-dark border px-2 py-1 fs-6">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block mb-1">{{ __('portal.public.transaction_reference') }}</label>
                            <span class="font-monospace fw-semibold text-dark">{{ $payment->transaction_reference ?: '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block mb-1">{{ __('portal.public.sender_mobile') }}</label>
                            <span class="fw-semibold text-dark font-monospace">{{ $payment->sender_mobile ?: '—' }}</span>
                        </div>

                        @if($payment->receiverAccount)
                            <div class="col-12">
                                <hr class="my-2">
                                <label class="text-muted small d-block mb-1">{{ __('portal.public.receiver_account_details') }}</label>
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="fw-semibold text-dark">{{ $payment->receiverAccount->account_name }}</div>
                                    <div class="small text-muted font-monospace">{{ $payment->receiverAccount->account_number }} ({{ $payment->receiverAccount->type_name }})</div>
                                    @if($payment->receiverAccount->bank_name)
                                        <div class="small text-muted">{{ $payment->receiverAccount->bank_name }} - {{ $payment->receiverAccount->branch_name }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($payment->payment_notes)
                            <div class="col-12">
                                <label class="text-muted small d-block mb-1">{{ __('portal.public.payment_notes') }}</label>
                                <div class="p-3 bg-light rounded-3 border small text-secondary">
                                    {{ $payment->payment_notes }}
                                </div>
                            </div>
                        @endif

                        @if($payment->payment_proof_path)
                            <div class="col-12">
                                <label class="text-muted small d-block mb-1">{{ __('portal.public.payment_proof') }}</label>
                                <div>
                                    <a href="{{ route('public.payments.proof', $payment->payment_code) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark-arrow-down me-1"></i> {{ __('portal.actions.download') }} {{ __('portal.public.payment_proof') }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Allocation & Pledge Side Card -->
        <div class="col-lg-5">
            <!-- Event Card -->
            @if($payment->event)
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title fw-bold mb-0 text-dark">
                            <i class="bi bi-calendar-event me-2 text-primary"></i>{{ __('events.event') }}
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1">
                            <a href="{{ route('public.events.show', $payment->event->slug) }}" class="text-decoration-none text-dark">
                                {{ $payment->event->name }}
                            </a>
                        </h6>
                        <span class="badge bg-light text-muted border font-monospace">{{ $payment->event->event_code }}</span>
                        <span class="badge bg-light text-muted border">{{ $payment->event->event_year }}</span>
                    </div>
                </div>
            @endif

            <!-- Allocation Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-pie-chart me-2 text-primary"></i>{{ __('portal.payments.allocation_title') }}
                    </h6>
                </div>
                <div class="card-body p-3">
                    @if($pledge)
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-uppercase text-primary">{{ __('portal.payments.pledge_fulfillment') }}</span>
                                <span class="badge bg-white text-dark border font-monospace">{{ $pledge->pledge_code }}</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>{{ __('portal.payments.pledged_amount') }}:</span>
                                <span class="fw-bold text-dark">৳ {{ number_format((float) $pledge->pledged_amount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>{{ __('portal.payments.total_verified_paid') }}:</span>
                                <span class="fw-bold text-success">৳ {{ number_format((float) $pledge->verified_paid_amount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted border-top pt-1 mt-1">
                                <span>{{ __('portal.payments.remaining_due') }}:</span>
                                <span class="fw-bold {{ $pledge->outstanding_amount > 0 ? 'text-danger' : 'text-success' }}">
                                    ৳ {{ number_format((float) $pledge->outstanding_amount, 2) }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <span class="small fw-bold text-uppercase text-success d-block mb-1">
                                <i class="bi bi-heart me-1"></i> {{ __('portal.public.voluntary_contribution_details') }}
                            </span>
                            <p class="small text-muted mb-0">
                                {{ __('portal.public.voluntary_donation') }}
                            </p>
                        </div>
                    @endif

                    @forelse($allocations as $allocation)
                        <div class="d-flex justify-content-between align-items-center p-2 mb-2 bg-light-subtle rounded border">
                            <div>
                                @if($allocation->target_type === \App\Models\PaymentAllocation::TARGET_PLEDGE)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                                        <i class="bi bi-bookmark-check me-1"></i> {{ __('portal.payments.pledge_fulfillment') }}
                                    </span>
                                @elseif($allocation->target_type === \App\Models\PaymentAllocation::TARGET_VOLUNTARY_EVENT)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                        <i class="bi bi-heart me-1"></i> {{ __('portal.payments.voluntary_event_donation') }}
                                    </span>
                                @else
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle small">
                                        <i class="bi bi-bank me-1"></i> {{ __('portal.payments.voluntary_general_donation') }}
                                    </span>
                                @endif
                                @if(! $allocation->is_active)
                                    <span class="badge bg-secondary-subtle text-secondary small ms-1">{{ __('portal.public.inactive_allocation') }}</span>
                                @endif
                            </div>
                            <span class="font-monospace fw-bold text-dark">
                                ৳ {{ number_format((float) $allocation->amount, 2) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">{{ __('portal.payments.no_allocations') }}</p>
                    @endforelse

                    <div class="small text-muted mt-3">
                        <i class="bi bi-info-circle me-1 text-primary"></i>
                        {{ __('portal.public.allocation_invariant_note') }}
                    </div>
                </div>
            </div>

            <!-- Receipt Card if verified -->
            @if($payment->isVerified() && $payment->receipt)
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="card-title fw-bold mb-0 text-dark">
                            <i class="bi bi-receipt me-2 text-success"></i>{{ __('donations.receipt') }}
                        </h6>
                        <span class="badge {{ $payment->receipt->isVoided() ? 'bg-secondary' : 'bg-success' }} font-monospace">
                            {{ $payment->receipt->receipt_number }}
                        </span>
                    </div>
                    <div class="card-body p-3">
                        @if($payment->receipt->isVoided())
                            <div class="alert alert-danger small mb-0">
                                <strong>{{ __('donations.receipt_status_voided') }}</strong>
                                <div>{{ __('donations.void_reason') }}: {{ $payment->receipt->void_reason }}</div>
                            </div>
                        @else
                            <p class="small text-muted mb-3">{{ __('portal.public.receipt_verified_available') }}</p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('public.payments.receipt', $payment->receipt->receipt_number) }}" target="_blank" class="btn btn-outline-success btn-sm flex-grow-1">
                                    <i class="bi bi-eye me-1"></i> {{ __('portal.public.view_receipt') }}
                                </a>
                                <a href="{{ route('public.payments.receipt.download', $payment->receipt->receipt_number) }}" class="btn btn-success btn-sm flex-grow-1">
                                    <i class="bi bi-download me-1"></i> {{ __('portal.payments.download_receipt') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
