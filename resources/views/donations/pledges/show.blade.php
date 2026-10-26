@extends('layouts.app')

@section('title', $pledge->pledge_code . ' — ' . __('donations.pledge_details'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('pledges.index') }}" class="text-decoration-none">{{ __('donations.pledges') }}</a></li>
                    <li class="breadcrumb-item active">{{ $pledge->pledge_code }}</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2">{{ $pledge->pledge_code }}</span>
                @php
                    $badgeClass = match ($pledge->status) {
                        'paid' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                        'partial' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                        'unpaid' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                        'cancelled' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                        default => 'bg-secondary bg-opacity-10 text-secondary',
                    };
                @endphp
                <span class="badge {{ $badgeClass }} px-3 py-2 fs-6">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>{{ __('donations.status_'.$pledge->status) }}
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fs-6">
                    {{ $pledge->pledge_year }}
                </span>
            </div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ $pledge->owner_name }}</h1>
            <p class="text-muted mb-0"><i class="bi bi-calendar-event me-1"></i>{{ $pledge->event?->name ?? '—' }}</p>
        </div>

        <!-- Action Toolbar -->
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('pledges.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>

            @if(Auth::user()->hasPermission('record_collections') && $pledge->outstanding_amount > 0 && $pledge->status !== 'cancelled')
                <a href="{{ route('collections.create', ['pledge_id' => $pledge->id]) }}" class="btn btn-success rounded-pill px-4 shadow-sm">
                    <i class="bi bi-cash-plus me-1"></i> {{ __('donations.collect_payment') }}
                </a>
            @endif

            @if(Auth::user()->hasPermission('manage_pledges') && $pledge->status !== 'cancelled' && $pledge->status !== 'paid')
                <a href="{{ route('pledges.edit', $pledge) }}" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-pencil me-1"></i> {{ __('common.edit') }}
                </a>
            @endif

            @if(Auth::user()->hasPermission('manage_pledges') && $pledge->verified_paid_amount == 0 && $pledge->status !== 'cancelled')
                <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cancelPledgeModal">
                    <i class="bi bi-x-circle me-1"></i> {{ __('donations.cancel_pledge') }}
                </button>
            @endif
        </div>
    </div>

    <!-- Excess Contribution Positive Notice -->
    @if($pledge->has_excess_contribution)
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 p-4 d-flex align-items-center gap-3">
            <i class="bi bi-heart-fill fs-2 text-success"></i>
            <div>
                <h5 class="fw-bold mb-1 text-success-emphasis">{{ __('donations.additional_contribution') }}: ৳{{ number_format($pledge->excess_contributed_amount, 2) }}</h5>
                <p class="mb-0 text-success-emphasis">
                    {{ __('donations.excess_contribution_notice', ['amount' => number_format($pledge->excess_contributed_amount, 2)]) }}
                </p>
            </div>
        </div>
    @endif

    <!-- Fulfillment Progress Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            @php
                $pct = $pledge->pledged_amount > 0 ? min(100, round(($pledge->verified_paid_amount / $pledge->pledged_amount) * 100)) : 0;
            @endphp
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-dark">Fulfillment Progress</span>
                <span class="fw-bold text-primary">{{ $pct }}%</span>
            </div>
            <div class="progress" style="height: 12px; border-radius: 6px;">
                <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-danger') }}" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="row g-3 text-center mt-3 pt-2 border-top">
                <div class="{{ $pledge->has_excess_contribution ? 'col-3' : 'col-4' }}">
                    <div class="text-muted small">{{ __('donations.pledged_amount') }}</div>
                    <div class="fs-5 fw-bold text-dark">৳{{ number_format((float) $pledge->pledged_amount, 2) }}</div>
                </div>
                <div class="{{ $pledge->has_excess_contribution ? 'col-3 border-start' : 'col-4 border-start border-end' }}">
                    <div class="text-muted small">{{ __('donations.paid_amount') }}</div>
                    <div class="fs-5 fw-bold text-success">৳{{ number_format($pledge->verified_paid_amount, 2) }}</div>
                </div>
                @if($pledge->has_excess_contribution)
                    <div class="col-3 border-start border-end">
                        <div class="text-muted small">{{ __('donations.additional_contribution') }}</div>
                        <div class="fs-5 fw-bold text-primary">+৳{{ number_format($pledge->excess_contributed_amount, 2) }}</div>
                        <div class="small text-muted">({{ __('donations.total_contributed') }}: ৳{{ number_format($pledge->total_contributed_amount, 2) }})</div>
                    </div>
                @endif
                <div class="{{ $pledge->has_excess_contribution ? 'col-3' : 'col-4' }}">
                    <div class="text-muted small">{{ __('donations.outstanding_amount') }}</div>
                    <div class="fs-5 fw-bold {{ $pledge->outstanding_amount > 0 ? 'text-danger' : 'text-muted' }}">
                        ৳{{ number_format($pledge->outstanding_amount, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Details Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>{{ __('common.details') }}</h6>
                    <div class="mb-3">
                        <label class="small text-muted mb-0">{{ __('donations.pledged_on') }}</label>
                        <div class="fw-semibold text-dark">{{ $pledge->pledged_on->format('d F Y') }}</div>
                    </div>
                    @if($pledge->purpose)
                        <div class="mb-3">
                            <label class="small text-muted mb-0">{{ __('donations.purpose') }}</label>
                            <div class="fw-semibold text-dark">{{ $pledge->purpose }}</div>
                        </div>
                    @endif
                    @if($pledge->notes)
                        <div class="mb-3">
                            <label class="small text-muted mb-0">{{ __('donations.notes') }}</label>
                            <div class="small text-muted">{{ $pledge->notes }}</div>
                        </div>
                    @endif
                    <div class="small pt-2 border-top">
                        <span class="text-muted">{{ __('events.created_by') }}:</span>
                        <span class="fw-semibold text-dark ms-1">{{ $pledge->creator?->name ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Installments & Payment History -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>{{ __('donations.installments') }}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ __('donations.payment_code') }}</th>
                                    <th>{{ __('donations.payment_date') }}</th>
                                    <th>{{ __('donations.payment_method') }}</th>
                                    <th>{{ __('donations.amount') }}</th>
                                    <th>{{ __('donations.receipts') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                    <th class="text-end pe-3">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pledge->allocations as $alloc)
                                    @php $payment = $alloc->payment; @endphp
                                    @if($payment)
                                        <tr>
                                            <td class="ps-3">
                                                <span class="badge bg-light text-dark border font-monospace">{{ $payment->payment_code }}</span>
                                            </td>
                                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                            <td>
                                                <i class="bi bi-credit-card me-1 text-muted"></i>{{ __('donations.method_'.$payment->payment_method) }}
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark">৳{{ number_format((float) $alloc->amount, 2) }}</div>
                                            </td>
                                            <td>
                                                @if($payment->receipt)
                                                    <a href="{{ route('receipts.show', $payment->receipt) }}" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 text-decoration-none">
                                                        <i class="bi bi-receipt me-1"></i>{{ $payment->receipt->receipt_number }}
                                                    </a>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $payment->status === 'verified' ? 'success' : ($payment->status === 'pending_verification' ? 'warning' : 'secondary') }} bg-opacity-10 text-{{ $payment->status === 'verified' ? 'success' : ($payment->status === 'pending_verification' ? 'warning' : 'secondary') }} border">
                                                    {{ __('donations.status_'.$payment->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <a href="{{ route('collections.show', $payment) }}" class="btn btn-sm btn-outline-primary" title="{{ __('common.view') }}">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No payment installments recorded for this pledge yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Pledge Modal -->
@if(Auth::user()->hasPermission('manage_pledges') && $pledge->verified_paid_amount == 0 && $pledge->status !== 'cancelled')
    <div class="modal fade" id="cancelPledgeModal" tabindex="-1" aria-labelledby="cancelPledgeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('pledges.destroy', $pledge) }}" method="POST" id="cancelPledgeForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="cancelPledgeModalLabel">
                            <i class="bi bi-x-circle me-2 text-danger"></i>{{ __('donations.cancel_pledge') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-danger border-0 rounded-3 mb-3">
                            Are you sure you want to cancel this pledge? This action will mark it cancelled in the audit records.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('donations.cancellation_reason') }}</label>
                            <textarea name="cancellation_reason" rows="3" class="form-control" placeholder="Provide cancellation justification..." required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmCancelBtn" class="btn btn-danger rounded-pill px-4">
                            <i class="bi bi-check-circle me-1"></i> Confirm Cancellation
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
        $('#cancelPledgeForm').on('submit', function() {
            TempleUI.button.loading('#confirmCancelBtn');
        });
    });
</script>
@endpush
