@extends('layouts.app')

@section('title', $payment->payment_code)

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('portal.payments.index') }}">{{ __('portal.payments.title') }}</a></li>
                    <li class="breadcrumb-item active">{{ $payment->payment_code }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ $payment->payment_code }}</h1>
            <p class="text-muted small mb-0">{{ $payment->payer_display_name }} &bull; ৳ {{ number_format((float) $payment->amount, 2) }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('portal.payments.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('portal.actions.back_to_list') }}
            </a>
            @if($payment->receipt)
                <a href="{{ route('receipts.show', $payment->receipt) }}" class="btn btn-outline-primary">
                    <i class="bi bi-receipt me-1"></i> {{ $payment->receipt->receipt_number }}
                </a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Submission Overview --}}
        <div class="col-lg-8">
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4">
                <i class="bi bi-shield-exclamation fs-3 me-3 text-warning"></i>
                <div class="small fw-semibold">
                    {{ __('portal.payments.trx_statement_warning') }}
                </div>
            </div>

            {{-- Main Payment Details Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">{{ __('portal.payments.verification_title') }}</h5>
                    <div>
                        @if($payment->isVerified())
                            <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>{{ __('donations.status_verified') }}</span>
                        @elseif($payment->isRejected())
                            <span class="badge bg-danger fs-6"><i class="bi bi-x-circle me-1"></i>{{ __('donations.status_rejected') }}</span>
                        @elseif($payment->isReversed())
                            <span class="badge bg-secondary fs-6"><i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('donations.status_reversed') }}</span>
                        @else
                            <span class="badge bg-warning text-dark fs-6"><i class="bi bi-clock me-1"></i>{{ __('donations.status_pending') }}</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.payments.amount') }}</label>
                            <div class="fs-4 fw-bold text-success">৳ {{ number_format((float) $payment->amount, 2) }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.payments.method') }}</label>
                            <div>
                                <span class="badge bg-dark fs-6">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.payments.trx_id') }}</label>
                            <div><code class="fw-bold fs-6">{{ $payment->transaction_reference ?: '—' }}</code></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.payments.sender_mobile') }}</label>
                            <div class="fw-semibold">{{ $payment->sender_mobile ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.payments.receiver_account') }}</label>
                            <div>
                                @if($payment->receiverAccount)
                                    <span class="fw-semibold">{{ $payment->receiverAccount->account_name }}</span>
                                    <div class="small text-muted">{{ $payment->receiverAccount->account_number }} ({{ $payment->receiverAccount->type_name }})</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.payments.event') }}</label>
                            <div>
                                @if($payment->event)
                                    <a href="{{ route('portal.events.show', $payment->event) }}" class="fw-semibold text-decoration-none">
                                        {{ $payment->event->name }}
                                    </a>
                                    <div class="small text-muted">{{ $payment->event->event_code }} ({{ $payment->event->event_year }})</div>
                                @else
                                    <span class="badge bg-light text-dark border">{{ __('portal.payments.general_temple_donation') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($payment->payment_notes)
                        <hr>
                        <div class="mb-3">
                            <label class="text-muted small">Donor Remarks / Notes</label>
                            <p class="text-secondary mb-0">{{ $payment->payment_notes }}</p>
                        </div>
                    @endif

                    @if($payment->rejection_reason)
                        <div class="alert alert-danger mb-0">
                            <h6 class="alert-heading fw-bold mb-1">
                                <i class="bi bi-x-octagon me-1"></i> {{ __('portal.payments.rejection_reason') }}
                            </h6>
                            <p class="mb-0">{{ $payment->rejection_reason }}</p>
                            @if($payment->rejecter)
                                <div class="small text-muted mt-2">
                                    Rejected by {{ $payment->rejecter->name }} on {{ $payment->rejected_at?->format('d M Y, h:i A') }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Donor & Devotee Linking Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('portal.payments.donor') }}</h6>
                    <div>
                        @if($payment->payer_person_id)
                            <span class="badge bg-success-subtle text-success border">
                                <i class="bi bi-person-check me-1"></i> {{ __('portal.payments.registered_devotee') }}
                            </span>
                        @else
                            <span class="badge bg-light text-muted border">
                                <i class="bi bi-person-dash me-1"></i> {{ __('portal.payments.guest_donor') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="text-muted small">{{ __('common.name') }}</label>
                            <div class="fw-semibold">{{ $payment->payer_display_name }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">{{ __('common.phone') }}</label>
                            <div class="fw-semibold">{{ $payment->public_donor_phone ?: ($payment->payer_phone_override ?: $payment->sender_mobile ?: '—') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">{{ __('common.email') }}</label>
                            <div>{{ $payment->public_donor_email ?: '—' }}</div>
                        </div>
                    </div>

                    {{-- Devotee Linking Section if unlinked --}}
                    @if(! $payment->payer_person_id && auth()->user()->can('verify_public_payments'))
                        <hr>
                        <h6 class="fw-bold mb-2 text-secondary">{{ __('portal.payments.link_devotee') }}</h6>
                        <form action="{{ route('portal.payments.link-devotee', $payment) }}" method="POST" class="row g-2 align-items-center">
                            @csrf
                            <div class="col-md-8">
                                <select name="person_id" id="linkPersonSelect" class="form-select select2-enable" required>
                                    <option value="">-- {{ __('portal.payments.select_person') }} --</option>
                                    @foreach($suggestedPersons as $sp)
                                        <option value="{{ $sp->id }}">
                                            {{ $sp->name_en }} ({{ $sp->person_code }}) - {{ $sp->phone }} [{{ __('portal.payments.suggested_match') }}]
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-link-45deg me-1"></i> {{ __('portal.payments.link_devotee') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Pledge Status & Calculation Card --}}
            @if($pledge)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-semibold text-primary">
                            <i class="bi bi-bookmark-check-fill me-1"></i> {{ __('portal.payments.pledge_details') }}
                        </h6>
                        <div>
                            <span class="badge bg-light text-dark border font-monospace">{{ $pledge->pledge_code }}</span>
                            <span class="badge {{ $pledge->isPaid() ? 'bg-success' : ($pledge->isPartial() ? 'bg-info' : 'bg-secondary') }} ms-1">
                                {{ strtoupper($pledge->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 text-center mb-3">
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded border">
                                    <div class="text-muted small mb-1">{{ __('portal.payments.original_pledged_amount') }}</div>
                                    <div class="fs-5 fw-bold text-dark">৳ {{ number_format((float) $pledge->pledged_amount, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded border">
                                    <div class="text-muted small mb-1">{{ __('portal.payments.total_verified_paid') }}</div>
                                    <div class="fs-5 fw-bold text-primary">৳ {{ number_format((float) $pledge->total_contributed_amount, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded border">
                                    <div class="text-muted small mb-1">{{ __('portal.payments.remaining_pledge_due') }}</div>
                                    <div class="fs-5 fw-bold {{ $pledge->outstanding_amount > 0 ? 'text-danger' : 'text-success' }}">
                                        ৳ {{ number_format((float) $pledge->outstanding_amount, 2) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded border">
                                    <div class="text-muted small mb-1">{{ __('portal.payments.extra_contribution') }}</div>
                                    <div class="fs-5 fw-bold text-success">৳ {{ number_format((float) $pledge->excess_contributed_amount, 2) }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Calculation Breakdown --}}
                        <div class="p-3 bg-light-subtle rounded border">
                            <h6 class="fw-bold mb-2 small text-uppercase text-secondary">
                                <i class="bi bi-calculator me-1"></i> Payment Allocation Breakdown
                            </h6>
                            @if($payment->isVerified())
                                @php
                                    $pledgeAlloc = (float) $payment->allocations()->where('target_type', 'pledge')->sum('amount');
                                    $excessAlloc = (float) $payment->allocations()->whereIn('target_type', ['voluntary_event', 'voluntary_general'])->sum('amount');
                                @endphp
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span>{{ __('portal.payments.applied_to_pledge') }}:</span>
                                    <span class="fw-bold text-dark">৳ {{ number_format($pledgeAlloc, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-0 small">
                                    <span>{{ __('portal.payments.extra_event_contribution') }}:</span>
                                    <span class="fw-bold text-success">৳ {{ number_format($excessAlloc, 2) }}</span>
                                </div>
                            @else
                                @php
                                    $estPledge = min((float) $payment->amount, (float) $pledge->outstanding_amount);
                                    $estExcess = max((float) $payment->amount - (float) $pledge->outstanding_amount, 0.0);
                                    $estRemaining = max((float) $pledge->outstanding_amount - (float) $payment->amount, 0.0);
                                @endphp
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span>Projected Settlement (Applied to Pledge):</span>
                                    <span class="fw-bold text-dark">৳ {{ number_format($estPledge, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span>Projected Extra Event Contribution:</span>
                                    <span class="fw-bold text-success">৳ {{ number_format($estExcess, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-0 small">
                                    <span>Projected Remaining Pledge Due:</span>
                                    <span class="fw-bold {{ $estRemaining > 0 ? 'text-danger' : 'text-success' }}">৳ {{ number_format($estRemaining, 2) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Accounting & Ledger Details (If Verified) --}}
            @if($payment->journalEntry)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="card-title mb-0 fw-semibold">
                            <i class="bi bi-journal-text text-success me-1"></i> Double-Entry Accounting Ledger Entry
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account</th>
                                        <th class="text-end">Debit (৳)</th>
                                        <th class="text-end">Credit (৳)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payment->journalEntry->lines as $line)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $line->account?->account_name }}</div>
                                                <div class="small text-muted">{{ $line->account?->account_code }}</div>
                                            </td>
                                            <td class="text-end fw-semibold {{ $line->debit_amount > 0 ? 'text-primary' : 'text-muted' }}">
                                                {{ $line->debit_amount > 0 ? number_format((float) $line->debit_amount, 2) : '—' }}
                                            </td>
                                            <td class="text-end fw-semibold {{ $line->credit_amount > 0 ? 'text-success' : 'text-muted' }}">
                                                {{ $line->credit_amount > 0 ? number_format((float) $line->credit_amount, 2) : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar: Proof of Payment & Actions --}}
        <div class="col-lg-4">
            {{-- Proof Image Card --}}
            <div class="card shadow-sm border-0 mb-4 text-center">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('portal.payments.payment_proof') }}</h6>
                </div>
                <div class="card-body p-4">
                    @if($payment->payment_proof_path)
                        <img src="{{ $payment->payment_proof_url }}" alt="Proof" class="img-fluid rounded border shadow-sm mb-3" style="max-height: 250px;">
                        <div>
                            <a href="{{ $payment->payment_proof_url }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrows-fullscreen me-1"></i> {{ __('portal.actions.preview') }}
                            </a>
                        </div>
                    @else
                        <div class="py-4 text-muted">
                            <i class="bi bi-file-earmark-image text-secondary fs-1 d-block mb-2"></i>
                            <div class="small">{{ __('portal.payments.no_proof') }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions Card --}}
            @if($payment->status === \App\Models\CollectionPayment::STATUS_PENDING)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="card-title mb-0 fw-semibold">{{ __('common.actions') }}</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        @if(auth()->user()->can('verify_public_payments'))
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#verifyPaymentModal">
                                <i class="bi bi-check-lg me-1"></i> {{ __('portal.payments.verify_and_post') }}
                            </button>
                        @endif

                        @if(auth()->user()->can('reject_public_payments') || auth()->user()->can('verify_public_payments'))
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectPaymentModal">
                                <i class="bi bi-x-lg me-1"></i> {{ __('portal.payments.reject') }}
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Audit Details --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('common.audit_information') }}</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('portal.payments.submission_time') }}:</span>
                            <span class="fw-semibold">{{ $payment->created_at ? $payment->created_at->format('d M Y, h:i A') : $payment->payment_date->format('d M Y') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Recorded By:</span>
                            <span class="fw-semibold">{{ $payment->submitter?->name ?? 'System / Public Portal' }}</span>
                        </li>
                        @if($payment->isVerified())
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Verified By:</span>
                                <span class="fw-semibold">{{ $payment->verifier?->name ?? '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Verified At:</span>
                                <span class="fw-semibold">{{ $payment->verified_at?->format('d M Y, h:i A') }}</span>
                            </li>
                            @if($payment->verification_note)
                                <li class="list-group-item">
                                    <div class="text-muted">Note:</div>
                                    <div>{{ $payment->verification_note }}</div>
                                </li>
                            @endif
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals for Verify and Reject --}}
@if($payment->status === \App\Models\CollectionPayment::STATUS_PENDING)
    <div class="modal fade" id="verifyPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('portal.payments.verify', $payment) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-check-circle me-1"></i> {{ __('portal.payments.verify_and_post') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            {{ __('portal.payments.confirm_verify') }}
                        </p>
                        @if($pledge)
                            <div class="p-3 bg-light rounded border mb-3 small">
                                <div class="fw-bold text-dark mb-1"><i class="bi bi-bookmark-check text-primary me-1"></i> Pledge: {{ $pledge->pledge_code }}</div>
                                <div class="text-muted">Donor: {{ $payment->payer_display_name }}</div>
                                <div class="text-muted">Remaining Due: ৳ {{ number_format((float) $pledge->outstanding_amount, 2) }}</div>
                            </div>
                            <input type="hidden" name="pledge_id" value="{{ $pledge->id }}">
                        @endif
                        <div class="mb-3">
                            <label for="modal_verification_note" class="form-label small fw-bold">
                                {{ __('portal.payments.verification_note') }}
                            </label>
                            <textarea name="verification_note" id="modal_verification_note" rows="2" class="form-control" placeholder="Optional audit note..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            {{ __('portal.actions.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> {{ __('portal.payments.verify_and_post') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="rejectPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('portal.payments.reject', $payment) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-x-circle me-1"></i> {{ __('portal.payments.reject') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            {{ __('portal.payments.confirm_reject') }}
                        </p>
                        <div class="mb-3">
                            <label for="modal_rejection_reason" class="form-label small fw-bold">
                                {{ __('portal.payments.rejection_reason') }} <span class="text-danger">*</span>
                            </label>
                            <textarea name="rejection_reason" id="modal_rejection_reason" rows="3" class="form-control" required placeholder="State why this submission is rejected..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            {{ __('portal.actions.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-lg me-1"></i> {{ __('portal.payments.reject') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
