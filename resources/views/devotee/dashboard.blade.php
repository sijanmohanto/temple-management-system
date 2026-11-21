@extends('layouts.app')

@section('title', __('devotee.dashboard_title'))

@section('content')
<div class="container-fluid px-0">
    <!-- Welcome Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden"
         style="background: linear-gradient(135deg, var(--temple-maroon), var(--temple-maroon-dark));">
        <div class="card-body p-4 position-relative">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-circle rounded-circle bg-white text-dark d-flex align-items-center justify-content-center fw-bold shadow-sm"
                         style="width: 64px; height: 64px; font-size: 1.5rem; background-color: var(--temple-saffron-light) !important; color: var(--temple-maroon) !important;">
                        ॐ
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark font-monospace">{{ $person->person_code }}</span>
                            <span class="badge bg-success bg-opacity-75"><i class="bi bi-shield-check me-1"></i> {{ __('devotee.devotee_member') }}</span>
                            @if($person->gotra)
                                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50">
                                    <i class="bi bi-flower1 me-1"></i> {{ $person->gotra }}
                                </span>
                            @endif
                        </div>
                        <h2 class="h3 fw-bold mb-0 text-white mt-1">
                            {{ $person->name_en }}
                            @if($person->name_bn)
                                <span class="fs-5 fw-normal opacity-75">({{ $person->name_bn }})</span>
                            @endif
                        </h2>
                        <small class="text-light opacity-75">
                            <i class="bi bi-telephone me-1"></i> {{ $person->phone ?? '—' }}
                            @if($person->email)
                                <span class="mx-2">&bull;</span> <i class="bi bi-envelope me-1"></i> {{ $person->email }}
                            @endif
                        </small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('devotee.payments.index') }}" class="btn btn-light rounded-pill px-3 fw-semibold">
                        <i class="bi bi-wallet2 me-1"></i> {{ __('devotee.my_payments') }}
                    </a>
                    <a href="{{ route('devotee.profile') }}" class="btn btn-outline-light rounded-pill px-3">
                        <i class="bi bi-person-gear me-1"></i> {{ __('devotee.profile_settings') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Four Financial Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Pledged -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary"
                         style="width: 52px; height: 52px; font-size: 1.5rem;">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">{{ __('devotee.total_pledged') }}</span>
                        <h4 class="fw-bold mb-0 text-dark">৳{{ number_format($totalPledged, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Verified Paid -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success"
                         style="width: 52px; height: 52px; font-size: 1.5rem;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">{{ __('devotee.total_paid') }}</span>
                        <h4 class="fw-bold mb-0 text-success">৳{{ number_format($totalPaid, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remaining Due -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger"
                         style="width: 52px; height: 52px; font-size: 1.5rem;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">{{ __('devotee.total_due') }}</span>
                        <h4 class="fw-bold mb-0 text-danger">৳{{ number_format($totalDue, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning"
                         style="width: 52px; height: 52px; font-size: 1.5rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">{{ __('devotee.pending_payments') }}</span>
                        <h4 class="fw-bold mb-0 text-warning">৳{{ number_format($pendingPayments, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Eligible Running Events (Each Handled Independently) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-calendar4-event text-danger me-2"></i> {{ __('devotee.running_events_title') }}
            </h5>
            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1">
                {{ $runningEventCards->count() }} {{ __('devotee.active_events_count') }}
            </span>
        </div>
        <div class="card-body p-4">
            @if($runningEventCards->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2 mb-0">{{ __('devotee.no_running_events') }}</p>
                </div>
            @else
                <div class="row g-4">
                    @foreach($runningEventCards as $item)
                        @php
                            $event = $item['event'];
                        @endphp
                        <div class="col-md-6 col-xl-4 d-flex">
                            <div class="card h-100 w-100 border rounded-4 shadow-sm overflow-hidden d-flex flex-column">
                                <div style="height: 130px; overflow: hidden; position: relative; background: #1e1b18;">
                                    <img src="{{ $event->featured_image_url }}" alt="{{ $event->name }}" style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        @if($item['status'] === 'paid')
                                            <span class="badge bg-success shadow-sm"><i class="bi bi-check-circle me-1"></i> {{ __('devotee.paid') }}</span>
                                        @elseif($item['status'] === 'partial')
                                            <span class="badge bg-warning text-dark shadow-sm"><i class="bi bi-clock me-1"></i> {{ __('devotee.partial') }}</span>
                                        @elseif($item['status'] === 'unpaid')
                                            <span class="badge bg-danger shadow-sm"><i class="bi bi-exclamation-circle me-1"></i> {{ __('devotee.unpaid') }}</span>
                                        @else
                                            <span class="badge bg-secondary shadow-sm">{{ __('devotee.voluntary') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="p-3 bg-light border-bottom">
                                    <span class="badge bg-primary bg-opacity-10 text-primary mb-1">
                                        {{ $event->category?->name ?? 'Puja' }} • {{ $event->event_year }}
                                    </span>
                                    <h6 class="fw-bold text-dark mb-0 fs-6 text-truncate" title="{{ $event->name }}">{{ $event->name }}</h6>
                                    @if($event->venue)
                                        <small class="text-muted d-block mt-1 text-truncate" title="{{ $event->venue }}">
                                            <i class="bi bi-geo-alt me-1"></i> {{ $event->venue }}
                                        </small>
                                    @endif
                                </div>

                                <div class="card-body p-3 flex-grow-1">
                                    <div class="row text-center g-2 mb-3">
                                        <div class="col-4">
                                            <div class="p-2 rounded bg-light border">
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">{{ __('devotee.pledged') }}</small>
                                                <span class="fw-bold text-dark" style="font-size: 0.85rem;">৳{{ number_format($item['pledged'], 0) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="p-2 rounded bg-light border">
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">{{ __('devotee.paid') }}</small>
                                                <span class="fw-bold text-success" style="font-size: 0.85rem;">৳{{ number_format($item['paid'], 0) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="p-2 rounded bg-light border">
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">{{ __('devotee.due') }}</small>
                                                <span class="fw-bold text-danger" style="font-size: 0.85rem;">৳{{ number_format($item['due'], 0) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    @if($item['pledge'] && $item['pledged'] > 0)
                                        @php
                                            $pct = min(100, round(($item['paid'] / $item['pledged']) * 100));
                                        @endphp
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span>{{ __('devotee.collection_progress') }}</span>
                                                <span class="fw-semibold">{{ $pct }}%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        {{ $event->start_date ? $event->start_date->format('d M') : '' }}
                                        {{ $event->end_date ? '— '.$event->end_date->format('d M Y') : '' }}
                                    </small>
                                    <a href="{{ $item['pay_url'] }}" class="btn btn-sm temple-btn-maroon px-3 rounded-pill fw-semibold">
                                        <i class="bi bi-wallet2 me-1"></i> {{ __('devotee.pay_now') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- 3. Recent Payment Records -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-clock-history text-secondary me-2"></i> {{ __('devotee.recent_payments_title') }}
            </h5>
            <a href="{{ route('devotee.payments.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                {{ __('devotee.view_all_payments') }} <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('portal.public.payment_code') }}</th>
                        <th>{{ __('portal.public.event') }}</th>
                        <th>{{ __('portal.public.amount') }}</th>
                        <th>{{ __('portal.public.payment_method') }}</th>
                        <th>{{ __('portal.public.payment_date') }}</th>
                        <th>{{ __('portal.public.status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $payment->payment_code }}</span>
                                @if($payment->transaction_reference)
                                    <br><small class="text-muted font-monospace"><i class="bi bi-hash"></i>{{ $payment->transaction_reference }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $payment->event?->name ?? '—' }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">৳{{ number_format((float) $payment->amount, 2) }}</div>
                            </td>
                            <td>
                                @include('partials.payment-method-badge', ['method' => $payment->payment_method])
                            </td>
                            <td>
                                <span class="small text-muted">{{ $payment->payment_date ? $payment->payment_date->format('d M Y') : '—' }}</span>
                            </td>
                            <td>
                                @if($payment->isVerified())
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> {{ __('portal.public.status_verified') }}</span>
                                @elseif($payment->isPending())
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> {{ __('portal.public.status_pending') }}</span>
                                @elseif($payment->isRejected())
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> {{ __('portal.public.status_rejected') }}</span>
                                @elseif($payment->isReversed())
                                    <span class="badge bg-dark"><i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('portal.public.status_reversed') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($payment->isVerified() && $payment->receipt && ! $payment->receipt->isVoided())
                                    <a href="{{ route('public.payments.receipt', $payment->receipt->receipt_number) }}" target="_blank" class="btn btn-sm btn-outline-success" title="{{ __('portal.public.view_receipt') }}">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                @endif
                                <a href="{{ route('public.payments.show', $payment->payment_code) }}" class="btn btn-sm btn-outline-primary" title="{{ __('common.view') }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                {{ __('devotee.no_payments_yet') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
