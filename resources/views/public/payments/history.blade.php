@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', __('portal.public.my_contributions') . ' — ' . __('common.app_name'))

@section('content')
<div class="py-2">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 small bg-white p-2 px-3 rounded-3 shadow-sm border">
            <li class="breadcrumb-item">
                <a href="{{ auth()->check() ? route('devotee.dashboard') : route('home') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> {{ auth()->check() ? __('devotee.my_dashboard') : __('portal.public.home') }}
                </a>
            </li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                {{ __('portal.public.my_contributions') }}
            </li>
        </ol>
    </nav>

    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background: linear-gradient(135deg, #560e15 0%, #75141c 50%, #b45309 100%);">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="badge bg-warning text-dark px-3 py-1 font-monospace mb-2">{{ $donor->person_code }}</span>
                    <h1 class="h2 fw-bold mb-1">{{ __('portal.public.my_contributions') }}</h1>
                    <p class="text-white-50 mb-0 fs-6">
                        {{ app()->getLocale() === 'bn' && !empty($donor->name_bn) ? $donor->name_bn : $donor->name_en }} — {{ __('portal.public.my_contributions_subtitle') }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('public.events.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-calendar-event me-1"></i> {{ __('portal.public.events') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('public.payments.history') }}" method="GET" class="row g-3 align-items-end" id="contributionsFilterForm">
                <div class="col-md-5">
                    <label for="event_id" class="form-label small fw-bold text-muted mb-1">{{ __('portal.public.filter_event') }}</label>
                    <select name="event_id" id="event_id" class="form-select form-select-sm">
                        <option value="">-- {{ __('portal.all_events') }} --</option>
                        @foreach($events as $ev)
                            <option value="{{ $ev->id }}" {{ request('event_id') == $ev->id ? 'selected' : '' }}>
                                {{ $ev->name }} ({{ $ev->event_year }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label small fw-bold text-muted mb-1">{{ __('portal.public.filter_status_label') }}</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>-- {{ __('portal.public.all_statuses') }} --</option>
                        <option value="pending_verification" {{ request('status') === 'pending_verification' ? 'selected' : '' }}>{{ __('portal.public.status_pending') }}</option>
                        <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>{{ __('portal.public.status_verified') }}</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('portal.public.status_rejected') }}</option>
                        <option value="reversed" {{ request('status') === 'reversed' ? 'selected' : '' }}>{{ __('portal.public.status_reversed') }}</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> {{ __('portal.public.filter_button') }}
                    </button>
                    @if(request()->hasAny(['event_id', 'status']))
                        <a href="{{ route('public.payments.history') }}" class="btn btn-outline-secondary btn-sm" title="{{ __('common.reset') }}">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Payments List Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="myContributionsTable">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3">{{ __('events.event') }}</th>
                            <th>{{ __('donations.payment_method_short') }}</th>
                            <th>{{ __('common.date') }}</th>
                            <th class="text-end">{{ __('common.amount_short') }}</th>
                            <th class="text-center">{{ __('common.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex flex-column">
                                        @if($payment->event)
                                            <a href="{{ route('public.events.show', $payment->event->slug) }}" class="text-decoration-none fw-semibold text-dark">
                                                {{ $payment->event->name }}
                                            </a>
                                            <div class="small text-muted">{{ $payment->event->event_year }}</div>
                                        @else
                                            <span class="text-muted fw-semibold">—</span>
                                        @endif
                                        <div class="mt-1 d-flex align-items-center gap-1 flex-wrap">
                                            <a href="{{ route('public.payments.show', $payment->payment_code) }}" class="badge bg-light text-primary border font-monospace text-decoration-none" title="{{ __('portal.public.payment_code') }}">
                                                #{{ $payment->payment_code }}
                                            </a>
                                            @if($payment->pledge)
                                                <span class="badge bg-light text-muted border font-monospace" title="{{ __('donations.pledge') }}">
                                                    {{ $payment->pledge->pledge_code }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        @include('partials.payment-method-badge', ['method' => $payment->payment_method])
                                        @if($payment->transaction_reference)
                                            <div class="font-monospace small text-dark fw-semibold mt-1"># {{ $payment->transaction_reference }}</div>
                                        @endif
                                        @if($payment->receiverAccount)
                                            <div class="small text-muted">{{ $payment->receiverAccount->account_name }}</div>
                                        @endif
                                        @if($payment->sender_mobile)
                                            <div class="small text-muted"><i class="bi bi-phone"></i> {{ $payment->sender_mobile }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td data-order="{{ $payment->payment_date ? $payment->payment_date->timestamp : 0 }}">
                                    <span class="text-nowrap font-monospace text-muted small">
                                        {{ $payment->payment_date ? $payment->payment_date->format('d M Y') : '—' }}
                                    </span>
                                </td>
                                <td class="text-end font-monospace fw-bold fs-6 text-dark" data-order="{{ $payment->amount }}">
                                    ৳{{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="text-center">
                                    @if($payment->status === 'verified')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> {{ __('portal.public.status_verified') }}
                                        </span>
                                    @elseif($payment->status === 'rejected')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1" title="{{ $payment->rejection_reason }}">
                                            <i class="bi bi-x-circle-fill me-1"></i> {{ __('portal.public.status_rejected') }}
                                        </span>
                                    @elseif($payment->status === 'reversed')
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary px-3 py-1" title="{{ $payment->reversal_reason }}">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('portal.public.status_reversed') }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1">
                                            <i class="bi bi-hourglass-split me-1"></i> {{ __('portal.public.status_pending') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('public.payments.show', $payment->payment_code) }}" class="btn btn-sm btn-outline-primary" title="{{ __('portal.public.view_details') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if($payment->isVerified() && $payment->receipt && !$payment->receipt->is_void)
                                            <a href="{{ route('public.payments.receipt', $payment->receipt->receipt_number) }}" target="_blank" class="btn btn-sm btn-success" title="{{ __('portal.public.view_receipt') }}">
                                                <i class="bi bi-receipt"></i>
                                            </a>
                                            <a href="{{ route('public.payments.receipt.download', $payment->receipt->receipt_number) }}" class="btn btn-sm btn-outline-success" title="{{ __('portal.public.download_receipt') }}">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        @endif

                                        <!-- Quick Modal Trigger -->
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#paymentModal{{ $payment->id }}" title="{{ __('portal.public.submission_summary') }}">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 text-secondary opacity-50 mb-2"></i>
                                    <p class="mb-2">{{ __('portal.public.no_contributions_found') }}</p>
                                    <a href="{{ route('public.events.index') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-calendar-event me-1"></i> {{ __('portal.public.events') }}
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Details Modals for Payments -->
@foreach($payments as $payment)
    <div class="modal fade text-start" id="paymentModal{{ $payment->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $payment->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold" id="modalLabel{{ $payment->id }}">
                        <i class="bi bi-receipt text-primary me-2"></i> {{ __('portal.public.submission_summary') }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded-3 border">
                        <div>
                            <small class="text-muted d-block">{{ __('portal.public.payment_code') }}</small>
                            <span class="font-monospace fw-bold fs-5 text-dark">{{ $payment->payment_code }}</span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">{{ __('portal.public.amount') }}</small>
                            <span class="font-monospace fw-bold fs-5 text-success">৳{{ number_format($payment->amount, 2) }}</span>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush small mb-3">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('events.event') }}:</span>
                            <span class="fw-semibold text-dark">{{ $payment->event?->name }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">{{ __('portal.public.select_payment_method') }}:</span>
                            <span>@include('partials.payment-method-badge', ['method' => $payment->payment_method])</span>
                        </li>
                        @if($payment->receiverAccount)
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">{{ __('portal.public.receiver_account_details') }}:</span>
                                <span class="fw-semibold text-dark">{{ $payment->receiverAccount->account_name }} ({{ $payment->receiverAccount->account_number }})</span>
                            </li>
                        @endif
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('portal.public.transaction_reference') }}:</span>
                            <span class="font-monospace fw-semibold text-dark">{{ $payment->transaction_reference ?: '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('portal.public.sender_mobile') }}:</span>
                            <span class="fw-semibold text-dark">{{ $payment->sender_mobile ?: '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('portal.public.payment_date') }}:</span>
                            <span class="fw-semibold text-dark">{{ $payment->payment_date ? $payment->payment_date->format('d M Y') : '—' }}</span>
                        </li>
                        @if($payment->payment_notes)
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">{{ __('portal.public.payment_notes') }}:</span>
                                <span class="text-dark">{{ $payment->payment_notes }}</span>
                            </li>
                        @endif
                        @if($payment->status === 'rejected' && $payment->rejection_reason)
                            <li class="list-group-item px-0">
                                <div class="alert alert-danger small mb-0 mt-2">
                                    <strong>{{ __('portal.public.rejection_reason_label') }}:</strong>
                                    <p class="mb-0">{{ $payment->rejection_reason }}</p>
                                </div>
                            </li>
                        @endif
                        @if($payment->status === 'reversed' && $payment->reversal_reason)
                            <li class="list-group-item px-0">
                                <div class="alert alert-secondary small mb-0 mt-2">
                                    <strong>{{ __('portal.public.reversal_reason_label') }}:</strong>
                                    <p class="mb-0">{{ $payment->reversal_reason }}</p>
                                    @if($payment->reversed_at)
                                        <small class="text-muted d-block mt-1">{{ __('portal.public.reversed_at_label') }}: {{ $payment->reversed_at->format('d M Y, h:i A') }}</small>
                                    @endif
                                </div>
                            </li>
                        @endif
                    </ul>

                    @if($payment->payment_proof_path)
                        <div class="mt-3">
                            <label class="small text-muted fw-bold mb-1">{{ __('portal.public.payment_proof') }}:</label>
                            <div>
                                <a href="{{ route('public.payments.proof', $payment->payment_code) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-file-earmark-arrow-down me-1"></i> {{ __('portal.actions.download') }} {{ __('portal.public.payment_proof') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-top py-2 d-flex justify-content-between">
                    <a href="{{ route('public.payments.show', $payment->payment_code) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('portal.public.view_full_details') }}
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('portal.public.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    TempleUI.initDataTable('#myContributionsTable', {
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        searching: true,
        paging: true,
        info: true,
        order: [],
        columnDefs: [
            { targets: [5], orderable: false, searchable: false }
        ]
    });
});
</script>
@endpush
