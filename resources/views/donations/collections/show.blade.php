@extends('layouts.app')

@section('title', $payment->payment_code . ' — ' . __('donations.payment_details'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('collections.index') }}" class="text-decoration-none">{{ __('donations.collections') }}</a></li>
                    <li class="breadcrumb-item active">{{ $payment->payment_code }}</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2">{{ $payment->payment_code }}</span>
                @php
                    $badgeClass = match ($payment->status) {
                        'verified' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                        'pending_verification' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                        'rejected' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                        'reversed' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                        default => 'bg-secondary bg-opacity-10 text-secondary',
                    };
                @endphp
                <span class="badge {{ $badgeClass }} px-3 py-2 fs-6">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>{{ __('donations.status_'.$payment->status) }}
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fs-6">
                    {{ __('donations.type_'.$payment->collection_type) }}
                </span>
                @if($payment->receipt)
                    <a href="{{ route('receipts.show', $payment->receipt) }}" class="badge {{ $payment->receipt->isValid() ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' }} px-3 py-2 fs-6 text-decoration-none">
                        <i class="bi bi-receipt me-1"></i>{{ $payment->receipt->receipt_number }}
                    </a>
                @endif
            </div>
            <h1 class="h3 fw-bold mb-1 text-dark">৳{{ number_format((float) $payment->amount, 2) }}</h1>
            <p class="text-muted mb-0">{{ $payment->payer_display_name }} &bull; {{ $payment->payment_date->format('d F Y') }}</p>
        </div>

        <!-- Action Toolbar -->
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('collections.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>

            @if($payment->receipt && $payment->receipt->isValid() && Auth::user()->hasPermission('print_receipts'))
                <a href="{{ route('receipts.print', $payment->receipt) }}" target="_blank" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                    <i class="bi bi-printer me-1"></i> {{ __('donations.print_receipt') }}
                </a>
            @endif

            @if($payment->isPending() && Auth::user()->hasPermission('verify_collections'))
                @if($payment->submitted_by !== Auth::id() || Auth::user()->hasRole('super_admin'))
                    <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#verifyPaymentModal">
                        <i class="bi bi-check2-circle me-1"></i> {{ __('donations.verify_payment') }}
                    </button>
                @endif

                <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#rejectPaymentModal">
                    <i class="bi bi-x-circle me-1"></i> {{ __('donations.reject_payment') }}
                </button>
            @endif

            @if($payment->isVerified() && Auth::user()->hasPermission('reverse_collections'))
                <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reversePaymentModal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('donations.reverse_payment') }}
                </button>
            @endif
        </div>
    </div>

    <!-- Status Banners -->
    @if($payment->isPending())
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 p-4 d-flex align-items-center gap-3">
            <i class="bi bi-hourglass-split fs-2 text-warning"></i>
            <div>
                <h6 class="fw-bold mb-1">{{ __('donations.status_pending_verification') }}</h6>
                <p class="mb-0 small text-muted">{{ __('donations.unverified_submission_notice') }}</p>
                @if($payment->submitted_by === Auth::id() && !Auth::user()->hasRole('super_admin'))
                    <div class="small text-danger fw-semibold mt-1">
                        <i class="bi bi-exclamation-circle me-1"></i> You submitted this payment. Separation of duties requires another authorized user to verify it.
                    </div>
                @endif
            </div>
        </div>
    @elseif($payment->isVerified())
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 p-4 d-flex align-items-center gap-3">
            <i class="bi bi-check2-circle fs-2 text-success"></i>
            <div>
                <h6 class="fw-bold mb-1">{{ __('donations.status_verified') }}</h6>
                <div class="small">
                    <strong>{{ __('donations.verified_by') }}:</strong> {{ $payment->verifier?->name ?? '—' }} &bull;
                    <strong>{{ __('donations.verified_at') }}:</strong> {{ $payment->verified_at?->format('d M Y, h:i A') ?? '—' }}
                    @if($payment->verification_note)
                        <div class="mt-1"><strong>{{ __('donations.verification_note') }}:</strong> {{ $payment->verification_note }}</div>
                    @endif
                </div>
            </div>
        </div>
    @elseif($payment->isRejected())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 p-4 d-flex align-items-center gap-3">
            <i class="bi bi-x-circle fs-2 text-danger"></i>
            <div>
                <h6 class="fw-bold mb-1">{{ __('donations.status_rejected') }}</h6>
                <div class="small">
                    <strong>{{ __('donations.rejected_by') }}:</strong> {{ $payment->rejecter?->name ?? '—' }} &bull;
                    <strong>{{ __('donations.rejected_at') }}:</strong> {{ $payment->rejected_at?->format('d M Y, h:i A') ?? '—' }}
                    <div class="mt-1"><strong>{{ __('donations.rejection_reason') }}:</strong> &ldquo;{{ $payment->rejection_reason }}&rdquo;</div>
                </div>
            </div>
        </div>
    @elseif($payment->isReversed())
        <div class="alert alert-secondary border-0 shadow-sm rounded-4 mb-4 p-4 d-flex align-items-center gap-3">
            <i class="bi bi-arrow-counterclockwise fs-2 text-secondary"></i>
            <div>
                <h6 class="fw-bold mb-1">{{ __('donations.status_reversed') }}</h6>
                <div class="small text-muted">
                    <strong>{{ __('donations.reversed_by') }}:</strong> {{ $payment->reverser?->name ?? '—' }} &bull;
                    <strong>{{ __('donations.reversed_at') }}:</strong> {{ $payment->reversed_at?->format('d M Y, h:i A') ?? '—' }}
                    <div class="mt-1 text-dark"><strong>{{ __('donations.reversal_reason') }}:</strong> &ldquo;{{ $payment->reversal_reason }}&rdquo;</div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- Payment & Payer Information -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person me-2 text-primary"></i>{{ __('donations.payer') }} &amp; Method</h6>
                    <div class="mb-3">
                        <label class="small text-muted mb-0">{{ __('donations.payer') }}</label>
                        <div class="fw-bold text-dark fs-6">{{ $payment->payer_display_name }}</div>
                        @if($payment->payerPerson && $payment->payerPerson->phone)
                            <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $payment->payerPerson->phone }}</div>
                        @elseif($payment->payer_phone_override)
                            <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $payment->payer_phone_override }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted mb-0">{{ __('donations.payment_method') }}</label>
                        <div class="fw-semibold text-dark">{{ __('donations.method_'.$payment->payment_method) }}</div>
                    </div>

                    @if($payment->transaction_reference)
                        <div class="mb-3">
                            <label class="small text-muted mb-0">{{ __('donations.transaction_reference') }}</label>
                            <div class="font-monospace text-dark">
                                {{ Auth::user()->hasPermission('view_sensitive_payment_details') ? $payment->transaction_reference : substr($payment->transaction_reference, 0, 4).'****' }}
                            </div>
                        </div>
                    @endif

                    @if($payment->bank_name)
                        <div class="mb-3">
                            <label class="small text-muted mb-0">{{ __('donations.bank_name') }}</label>
                            <div class="text-dark">{{ $payment->bank_name }}</div>
                        </div>
                    @endif

                    @if($payment->cheque_number)
                        <div class="mb-3">
                            <label class="small text-muted mb-0">{{ __('donations.cheque_number') }}</label>
                            <div class="font-monospace text-dark">
                                {{ Auth::user()->hasPermission('view_sensitive_payment_details') ? $payment->cheque_number : substr($payment->cheque_number, 0, 3).'****' }}
                            </div>
                        </div>
                    @endif

                    @if($payment->payment_notes)
                        <div class="mb-3">
                            <label class="small text-muted mb-0">{{ __('donations.payment_notes') }}</label>
                            <div class="small text-muted">{{ $payment->payment_notes }}</div>
                        </div>
                    @endif

                    <div class="pt-2 border-top small text-muted">
                        <div><strong>{{ __('donations.submitted_by') }}:</strong> {{ $payment->submitter?->name ?? '—' }} ({{ $payment->created_at->format('d M Y, h:i A') }})</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Allocation Breakdown -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart me-2 text-primary"></i>{{ __('donations.allocations') }}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Target Obligation / Context</th>
                                    <th>Target Details</th>
                                    <th>Allocated Amount</th>
                                    <th class="text-end pe-3">{{ __('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payment->allocations as $alloc)
                                    <tr>
                                        <td class="ps-3">
                                            @if($alloc->target_type === 'voluntary_event' && $alloc->pledge_id)
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                    <i class="bi bi-gift me-1"></i> Excess &rarr; Event Fund
                                                </span>
                                            @elseif($alloc->target_type === 'voluntary_general' && $alloc->pledge_id)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                    <i class="bi bi-gift me-1"></i> Excess &rarr; General Fund
                                                </span>
                                            @elseif($alloc->target_type === 'voluntary_event')
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                                    Voluntary Event
                                                </span>
                                            @elseif($alloc->target_type === 'voluntary_general')
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                                    Voluntary General
                                                </span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                                    {{ $alloc->target_type }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($alloc->pledge)
                                                <a href="{{ route('pledges.show', $alloc->pledge) }}" class="text-decoration-none fw-semibold text-primary">
                                                    {{ $alloc->pledge->pledge_code }} ({{ $alloc->pledge->event?->name }})
                                                </a>
                                            @elseif($alloc->monthlyContribution)
                                                <div class="fw-semibold text-dark">
                                                    {{ $alloc->monthlyContribution->contribution_code }} — {{ $alloc->monthlyContribution->month_name }}
                                                </div>
                                            @elseif($alloc->event)
                                                <div class="fw-semibold text-dark">{{ $alloc->event->name }} (Voluntary)</div>
                                            @else
                                                <div class="fw-semibold text-dark">General Temple Fund</div>
                                            @endif
                                            @if($alloc->notes)
                                                <div class="small text-muted fst-italic">{{ $alloc->notes }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">৳{{ number_format((float) $alloc->amount, 2) }}</div>
                                        </td>
                                        <td class="text-end pe-3">
                                            @if($alloc->is_active)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Active</span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">Deactivated</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Verify Payment Modal -->
@if($payment->isPending() && Auth::user()->hasPermission('verify_collections'))
    <div class="modal fade" id="verifyPaymentModal" tabindex="-1" aria-labelledby="verifyPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('collections.verify', $payment) }}" method="POST" id="verifyPaymentForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="verifyPaymentModalLabel">
                            <i class="bi bi-check2-circle me-2 text-success"></i>{{ __('donations.verify_payment') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 rounded-3 mb-3">
                            Confirm that <strong>৳{{ number_format((float) $payment->amount, 2) }}</strong> has been verified. An official money receipt will be automatically generated upon confirmation.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('donations.verification_note') }} <span class="text-muted fw-normal">({{ __('common.optional') }})</span></label>
                            <textarea name="verification_note" rows="2" class="form-control" placeholder="Verified against bank statement / cash voucher..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmVerifyBtn" class="btn btn-success rounded-pill px-4">
                            <i class="bi bi-check-circle me-1"></i> Confirm &amp; Issue Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 2: Reject Payment Modal -->
    <div class="modal fade" id="rejectPaymentModal" tabindex="-1" aria-labelledby="rejectPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('collections.reject', $payment) }}" method="POST" id="rejectPaymentForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="rejectPaymentModalLabel">
                            <i class="bi bi-x-circle me-2 text-danger"></i>{{ __('donations.reject_payment') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-danger border-0 rounded-3 mb-3">
                            Are you sure you want to reject this payment submission? A mandatory justification reason is required.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('donations.rejection_reason') }}</label>
                            <textarea name="rejection_reason" rows="3" class="form-control" placeholder="Provide reason for rejecting this payment submission..." required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmRejectBtn" class="btn btn-danger rounded-pill px-4">
                            <i class="bi bi-x-circle me-1"></i> Reject Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Modal 3: Reverse Verified Payment Modal -->
@if($payment->isVerified() && Auth::user()->hasPermission('reverse_collections'))
    <div class="modal fade" id="reversePaymentModal" tabindex="-1" aria-labelledby="reversePaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('collections.reverse', $payment) }}" method="POST" id="reversePaymentForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="reversePaymentModalLabel">
                            <i class="bi bi-arrow-counterclockwise me-2 text-danger"></i>{{ __('donations.reverse_payment') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 rounded-3 mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i> {{ __('donations.reversal_warning') }}
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('donations.reversal_reason') }}</label>
                            <textarea name="reversal_reason" rows="3" class="form-control" placeholder="Provide justification for reversing this verified payment..." required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmReverseBtn" class="btn btn-danger rounded-pill px-4">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Confirm Reversal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#verifyPaymentForm').on('submit', function() {
            TempleUI.button.loading('#confirmVerifyBtn');
        });

        $('#rejectPaymentForm').on('submit', function() {
            TempleUI.button.loading('#confirmRejectBtn');
        });

        $('#reversePaymentForm').on('submit', function() {
            TempleUI.button.loading('#confirmReverseBtn');
        });
    });
</script>
@endpush
