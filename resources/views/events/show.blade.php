@extends('layouts.app')

@section('title', $event->name . ' (' . $event->event_code . ')')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('events.index') }}" class="text-decoration-none">{{ __('events.events') }}</a></li>
                    <li class="breadcrumb-item active">{{ $event->event_code }}</li>
                </ol>
            </nav>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2">{{ $event->event_code }}</span>
                @php
                    $badgeClass = match ($event->status) {
                        'draft' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                        'upcoming' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                        'active' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                        'closing' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                        'closed' => 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25',
                        default => 'bg-secondary bg-opacity-10 text-secondary',
                    };
                @endphp
                <span class="badge {{ $badgeClass }} px-3 py-2 fs-6">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>{{ __('events.status_'.$event->status) }}
                </span>
                @if($metrics['is_settled'])
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 fs-6">
                        <i class="bi bi-check-circle-fill me-1"></i>{{ __('events.event_settled') }}
                    </span>
                @endif
                @if($event->isClosed())
                    @if($event->isLocked())
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 fs-6">
                            <i class="bi bi-lock-fill me-1"></i>{{ __('events.mode_close_and_lock') }}
                        </span>
                    @elseif($event->allowsAuthorizedAdjustments())
                        <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 px-3 py-2 fs-6">
                            <i class="bi bi-pencil-square me-1"></i>{{ __('events.mode_close_with_authorized_adjustments') }}
                        </span>
                    @endif
                @endif
                <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2 fs-6">
                    {{ $event->category?->name }}
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 fs-6">
                    {{ $event->event_year }}
                </span>
            </div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ $event->name }}</h1>
            @if($event->name_bn && app()->getLocale() !== 'bn')
                <p class="text-muted mb-0">{{ $event->name_bn }}</p>
            @endif
        </div>

        <!-- Action Toolbar -->
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>

            <!-- Event Settlement Action -->
            @if(in_array($event->status, ['closing', 'closed']) && ! $metrics['is_settled'] && (Auth::user()->hasPermission('close_events') || Auth::user()->hasPermission('manage_events') || Auth::user()->hasRole('super_admin')))
                <button type="button" class="btn btn-outline-success rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#settleFundsModal">
                    <i class="bi bi-bank me-1"></i> {{ __('events.settle_event_funds') }}
                </button>
            @endif

            @if(! $event->isClosed())
                @if(Auth::user()->hasPermission('manage_events'))
                    <a href="{{ route('events.edit', $event) }}" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-pencil me-1"></i> {{ __('common.edit') }}
                    </a>
                @endif

                @if(Auth::user()->hasPermission('close_events'))
                    <button type="button" class="btn btn-warning rounded-pill px-3 text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#closeEventModal">
                        <i class="bi bi-door-closed me-1"></i> {{ __('events.close_event') }}
                    </button>
                @endif
            @else
                @if(Auth::user()->hasPermission('reopen_events'))
                    <button type="button" class="btn btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reopenEventModal">
                        <i class="bi bi-arrow-repeat me-1"></i> {{ __('events.reopen_event') }}
                    </button>
                @endif

                @if($event->allowsAuthorizedAdjustments() && Auth::user()->hasPermission('adjust_closed_events'))
                    <button type="button" class="btn btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#adjustEventModal">
                        <i class="bi bi-pencil-square me-1"></i> {{ __('events.post_close_adjust') }}
                    </button>
                @endif
            @endif

            @if($event->status === 'draft' && Auth::user()->hasPermission('manage_events'))
                <form action="{{ route('events.destroy', $event) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('events.delete_draft_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-3">
                        <i class="bi bi-trash me-1"></i> {{ __('common.delete') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Settled Status Banner -->
    @if($metrics['is_settled'])
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-check-circle-fill text-success fs-2"></i>
                    <div>
                        <h5 class="fw-bold mb-1 text-success">{{ __('events.settlement_completed_banner') }}</h5>
                        <div class="small text-muted">
                            <strong>{{ __('events.settlement_number') }}:</strong> <span class="font-monospace text-dark fw-bold">{{ $metrics['settlement']->settlement_number }}</span> &bull;
                            <strong>{{ __('events.surplus_transferred') }}:</strong> <span class="text-success fw-bold">৳{{ number_format((float) $metrics['settlement']->surplus_amount, 2) }}</span> &bull;
                            <strong>{{ __('events.settled_by') }}:</strong> {{ $metrics['settlement']->settledBy?->name ?? '—' }} &bull;
                            <strong>{{ __('events.settlement_date') }}:</strong> {{ $metrics['settlement']->settlement_date?->format('d M Y') }}
                        </div>
                    </div>
                </div>
                @if($metrics['settlement']->journalEntry)
                    <a href="{{ route('finance.journals.show', $metrics['settlement']->journalEntry) }}" class="btn btn-sm btn-outline-success rounded-pill px-3 bg-white">
                        <i class="bi bi-file-earmark-text me-1"></i> {{ __('events.view_settlement_voucher') }}
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- Closed Status Information Banner -->
    @if($event->isClosed())
        <div class="alert {{ $event->isLocked() ? 'alert-danger' : 'alert-warning' }} border-0 shadow-sm rounded-4 mb-4 p-4">
            <div class="d-flex align-items-start gap-3">
                <i class="bi {{ $event->isLocked() ? 'bi-shield-lock-fill text-danger' : 'bi-exclamation-triangle-fill text-warning' }} fs-3 mt-1"></i>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1">
                        @if($event->isLocked())
                            {{ __('events.mode_close_and_lock') }} — {{ __('events.field_locked_notice') }}
                        @else
                            {{ __('events.mode_close_with_authorized_adjustments') }}
                        @endif
                    </h5>
                    <p class="mb-2">
                        @if($event->isLocked())
                            {{ __('events.mode_close_and_lock_desc') }}
                        @else
                            {{ __('events.mode_close_with_authorized_adjustments_desc') }}
                        @endif
                    </p>
                    <div class="small">
                        <strong>{{ __('events.closed_by') }}:</strong> {{ $event->closedBy?->name ?? '—' }} &bull;
                        <strong>{{ __('events.closed_at') }}:</strong> {{ $event->closed_at?->format('d M Y, h:i A') ?? '—' }}
                        @if($event->closing_reason)
                            <div class="mt-1">
                                <strong>{{ __('events.closing_reason') }}:</strong> &ldquo;{{ $event->closing_reason }}&rdquo;
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Event Financial & Collection Overview Dashboard Cards -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-cash-coin fs-4 text-primary"></i>
                <h5 class="fw-bold mb-0 text-dark">{{ __('events.collection_summary') }}</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($metrics['is_above_target'])
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#aboveTargetModal" title="{{ __('events.above_target_donors_report') }}">
                        <i class="bi bi-arrow-up-circle me-1"></i> +৳{{ number_format($metrics['above_target_amount'], 2) }} {{ __('events.above_pledged_target') }}
                    </button>
                @elseif($metrics['shortfall_amount'] > 0)
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#outstandingDuesModal" title="{{ __('events.outstanding_dues_report') }}">
                        <i class="bi bi-exclamation-triangle me-1 text-warning"></i> ৳{{ number_format($metrics['shortfall_amount'], 2) }} {{ __('events.target_shortfall') }}
                    </button>
                @endif
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#excessReportModal" title="{{ __('events.excess_contributions_report') }}">
                    <i class="bi bi-gift me-1"></i> {{ __('events.excess_contributions_report') }}
                </button>
            </div>
        </div>
        <div class="card-body p-4 pt-2">
            <div class="row g-3">
                <!-- Card 1: Total Pledged -->
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="small text-muted mb-1">{{ __('events.total_pledged') }}</div>
                        <div class="fs-4 fw-bold text-dark">৳{{ number_format($metrics['total_pledged'], 2) }}</div>
                        <div class="small text-muted mt-1"><i class="bi bi-bookmark me-1"></i>{{ count($event->pledges) }} {{ __('donations.pledges') }}</div>
                    </div>
                </div>

                <!-- Card 2: Collected Against Pledges -->
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="small text-muted mb-1">{{ __('events.verified_pledge_collections') }}</div>
                        <div class="fs-4 fw-bold text-success">৳{{ number_format($metrics['verified_pledge_collections'], 2) }}</div>
                        <div class="small text-muted mt-1"><i class="bi bi-check2-circle me-1 text-success"></i>{{ __('donations.status_verified') }}</div>
                    </div>
                </div>

                <!-- Card 3: Outstanding Pledges (Clickable) -->
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-light rounded-4 border h-100 cursor-pointer shadow-hover transition-all" role="button" data-bs-toggle="modal" data-bs-target="#outstandingDuesModal" title="Click to view outstanding dues">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="small text-muted mb-1">{{ __('events.outstanding_pledges') }}</div>
                            <i class="bi bi-box-arrow-up-right text-muted small"></i>
                        </div>
                        <div class="fs-4 fw-bold {{ $metrics['outstanding_pledges'] > 0 ? 'text-danger' : 'text-success' }}">
                            ৳{{ number_format($metrics['outstanding_pledges'], 2) }}
                        </div>
                        <div class="small text-danger mt-1"><i class="bi bi-clock-history me-1"></i>{{ count($outstandingDues) }} {{ __('donations.status_unpaid') }}/partial</div>
                    </div>
                </div>

                <!-- Card 4: Event Excess Contributions (Clickable) -->
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-light rounded-4 border h-100 cursor-pointer shadow-hover transition-all" role="button" data-bs-toggle="modal" data-bs-target="#excessReportModal" title="Click to view excess contributions report">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="small text-muted mb-1">{{ __('events.event_excess_contributions') }}</div>
                            <i class="bi bi-box-arrow-up-right text-muted small"></i>
                        </div>
                        <div class="fs-4 fw-bold text-primary">৳{{ number_format($metrics['event_excess_contributions'], 2) }}</div>
                        <div class="small text-primary mt-1"><i class="bi bi-gift me-1"></i>{{ count($excessReport) }} {{ __('donations.additional_contribution') }}</div>
                    </div>
                </div>

                <!-- Card 5: Voluntary Event Donations -->
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="small text-muted mb-1">{{ __('events.voluntary_event_donations') }}</div>
                        <div class="fs-4 fw-bold text-dark">৳{{ number_format($metrics['voluntary_event_donations'], 2) }}</div>
                        <div class="small text-muted mt-1"><i class="bi bi-heart me-1 text-danger"></i>Direct guest/voluntary</div>
                    </div>
                </div>

                <!-- Card 6: Total Verified Event Collections -->
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-4 h-100">
                        <div class="small text-success fw-semibold mb-1">{{ __('events.total_verified_collections') }}</div>
                        <div class="fs-4 fw-bold text-success">৳{{ number_format($metrics['total_verified_collections'], 2) }}</div>
                        <div class="small text-muted mt-1"><i class="bi bi-shield-check me-1 text-success"></i>Pledges + Excess + Voluntary</div>
                    </div>
                </div>

                <!-- Card 7: Total Approved Event Expenses -->
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="small text-muted mb-1">{{ __('events.total_approved_expenses') }}</div>
                        <div class="fs-4 fw-bold text-dark">৳{{ number_format($metrics['total_approved_expenses'], 2) }}</div>
                        <div class="small text-muted mt-1"><i class="bi bi-receipt me-1"></i>Approved event vouchers</div>
                    </div>
                </div>

                <!-- Card 8: Net Event Cash Surplus -->
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 {{ $metrics['net_surplus'] >= 0 ? 'bg-primary bg-opacity-10 border border-primary border-opacity-25' : 'bg-danger bg-opacity-10 border border-danger border-opacity-25' }} rounded-4 h-100">
                        <div class="small {{ $metrics['net_surplus'] >= 0 ? 'text-primary' : 'text-danger' }} fw-semibold mb-1">{{ __('events.net_surplus') }}</div>
                        <div class="fs-4 fw-bold {{ $metrics['net_surplus'] >= 0 ? 'text-primary' : 'text-danger' }}">
                            ৳{{ number_format($metrics['net_surplus'], 2) }}
                        </div>
                        <div class="small text-muted mt-1">Collections &minus; Expenses</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2 bg-light p-2 rounded-4 border" id="eventTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 fw-semibold" id="donors-tab" data-bs-toggle="tab" data-bs-target="#tab-donors" type="button" role="tab">
                <i class="bi bi-people me-1"></i> {{ __('events.donor_comparison') }}
                <span class="badge bg-secondary ms-1">{{ count($donorComparison) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-semibold" id="settlement-tab" data-bs-toggle="tab" data-bs-target="#tab-settlement" type="button" role="tab">
                <i class="bi bi-bank me-1"></i> {{ __('events.financial_settlement') }}
                @if($metrics['is_settled'])
                    <span class="badge bg-success ms-1"><i class="bi bi-check"></i></span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-semibold" id="details-tab" data-bs-toggle="tab" data-bs-target="#tab-details" type="button" role="tab">
                <i class="bi bi-info-circle me-1"></i> {{ __('events.event_dates') }} &amp; {{ __('events.description') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-semibold" id="lifecycle-tab" data-bs-toggle="tab" data-bs-target="#tab-lifecycle" type="button" role="tab">
                <i class="bi bi-clock-history me-1"></i> {{ __('events.lifecycle_history') }}
                <span class="badge bg-secondary ms-1">{{ count($event->lifecycleLogs) }}</span>
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="eventTabContent">
        <!-- Tab 1: Donors & Collection Comparison -->
        <div class="tab-pane fade show active" id="tab-donors" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">{{ __('events.donor_comparison') }}</h5>
                        <div class="small text-muted">Individual devotee pledges, verified payments applied, excess contributions, and remaining dues.</div>
                    </div>
                    @if(! $event->isLocked())
                        <div class="d-flex gap-2">
                            <a href="{{ route('pledges.create', ['event_id' => $event->id]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="bi bi-plus-circle me-1"></i> {{ __('donations.add_pledge') }}
                            </a>
                            <a href="{{ route('collections.create', ['event_id' => $event->id]) }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                                <i class="bi bi-cash-stack me-1"></i> {{ __('donations.collect_payment') }}
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="donorComparisonTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ __('donations.payer') }}</th>
                                    <th>{{ __('donations.type') }}</th>
                                    <th class="text-end">{{ __('events.total_pledged') }} (৳)</th>
                                    <th class="text-end">{{ __('events.verified_pledge_collections') }} (৳)</th>
                                    <th class="text-end">{{ __('events.event_excess_contributions') }} (৳)</th>
                                    <th class="text-end">{{ __('events.outstanding_pledges') }} (৳)</th>
                                    <th>{{ __('donations.status') }}</th>
                                    <th class="text-end pe-3">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($donorComparison as $row)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark">{{ $row['donor_name'] }}</div>
                                            <div class="small text-muted font-monospace">{{ $row['pledge_code'] }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $row['donor_type'] }}</span>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            ৳{{ number_format($row['pledged_amount'], 2) }}
                                        </td>
                                        <td class="text-end text-success fw-bold">
                                            @if($row['is_pledge'])
                                                ৳{{ number_format($row['verified_applied'], 2) }}
                                            @else
                                                ৳{{ number_format($row['voluntary_amount'] ?? 0.0, 2) }}
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($row['excess_amount'] > 0)
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace">
                                                    +৳{{ number_format($row['excess_amount'], 2) }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($row['outstanding_amount'] > 0)
                                                <span class="text-danger fw-bold">৳{{ number_format($row['outstanding_amount'], 2) }}</span>
                                            @else
                                                <span class="text-muted">৳0.00</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($row['is_pledge'])
                                                @php
                                                    $stBadge = match ($row['status']) {
                                                        'paid' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                                        'partial' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                                                        'unpaid' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                                        default => 'bg-secondary bg-opacity-10 text-secondary',
                                                    };
                                                @endphp
                                                <span class="badge {{ $stBadge }} px-2 py-1">
                                                    {{ __('donations.status_'.$row['status']) }}
                                                </span>
                                            @else
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
                                                    {{ __('donations.type_event_donation') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm">
                                                @if($row['is_pledge'])
                                                    <a href="{{ route('pledges.show', $row['pledge_id']) }}" class="btn btn-outline-secondary" title="{{ __('common.view') }}">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    @if($row['outstanding_amount'] > 0 && ! $event->isLocked())
                                                        <a href="{{ route('collections.create', ['pledge_id' => $row['pledge_id']]) }}" class="btn btn-outline-success" title="{{ __('donations.collect_payment') }}">
                                                            <i class="bi bi-cash"></i>
                                                        </a>
                                                    @endif
                                                @elseif(! empty($row['payment_id']))
                                                    <a href="{{ route('collections.show', $row['payment_id']) }}" class="btn btn-outline-secondary" title="{{ __('common.view') }}">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            {{ __('donations.no_records_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Financial Settlement -->
        <div class="tab-pane fade" id="tab-settlement" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-bank me-2 text-primary"></i>{{ __('events.financial_settlement_title') }}</h5>
                    <div class="small text-muted">Reconciled event collections, expenses, surplus balance, and fund settlement audit history.</div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card border rounded-4 p-4 h-100 bg-light">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-calculator me-2 text-primary"></i>Financial Reconciliation Snapshot</h6>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-muted">{{ __('events.total_verified_collections') }}:</span>
                                    <span class="fw-bold text-success fs-6">৳{{ number_format($metrics['total_verified_collections'], 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="text-muted">{{ __('events.total_approved_expenses') }}:</span>
                                    <span class="fw-bold text-danger fs-6">&minus; ৳{{ number_format($metrics['total_approved_expenses'], 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                                    <span class="fw-bold text-dark fs-6">{{ __('events.net_surplus') }}:</span>
                                    <span class="fw-bold {{ $metrics['net_surplus'] >= 0 ? 'text-primary' : 'text-danger' }} fs-5">
                                        ৳{{ number_format($metrics['net_surplus'], 2) }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-3">
                                    <span class="text-muted">Destination Fund Account:</span>
                                    <span class="badge bg-light text-dark border font-monospace">3010 - General Temple Fund</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card border rounded-4 p-4 h-100 bg-white">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check me-2 text-primary"></i>{{ __('events.settlement_status') }}</h6>
                                
                                @if($metrics['is_settled'])
                                    <div class="alert alert-success border-0 rounded-3 mb-3 p-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            <span class="fw-bold text-success">{{ __('events.event_settled') }}</span>
                                        </div>
                                        <div class="small text-muted mb-1">
                                            <strong>{{ __('events.settlement_number') }}:</strong>
                                            <span class="font-monospace text-dark">{{ $metrics['settlement']->settlement_number }}</span>
                                        </div>
                                        <div class="small text-muted mb-1">
                                            <strong>{{ __('events.surplus_transferred') }}:</strong>
                                            <span class="text-success fw-bold">৳{{ number_format((float) $metrics['settlement']->surplus_amount, 2) }}</span>
                                        </div>
                                        <div class="small text-muted mb-1">
                                            <strong>{{ __('events.settled_by') }}:</strong>
                                            {{ $metrics['settlement']->settledBy?->name ?? '—' }}
                                        </div>
                                        <div class="small text-muted">
                                            <strong>{{ __('events.settlement_date') }}:</strong>
                                            {{ $metrics['settlement']->settlement_date?->format('d M Y') }}
                                        </div>
                                        @if($metrics['settlement']->notes)
                                            <div class="mt-2 pt-2 border-top small text-dark fst-italic">
                                                &ldquo;{{ $metrics['settlement']->notes }}&rdquo;
                                            </div>
                                        @endif
                                    </div>

                                    @if($metrics['settlement']->journalEntry)
                                        <a href="{{ route('finance.journals.show', $metrics['settlement']->journalEntry) }}" class="btn btn-outline-success rounded-pill w-100">
                                            <i class="bi bi-receipt me-1"></i> {{ __('events.view_settlement_voucher') }} ({{ $metrics['settlement']->journalEntry->journal_number }})
                                        </a>
                                    @endif
                                @else
                                    @if(in_array($event->status, ['closing', 'closed']))
                                        <div class="alert alert-warning border-0 rounded-3 mb-3 p-3 small text-muted">
                                            <i class="bi bi-info-circle text-warning-emphasis me-1"></i>
                                            This event has entered the closing phase with a net surplus balance of <strong>৳{{ number_format($metrics['net_surplus'], 2) }}</strong>. Authorized users may formally execute the fund settlement to transfer surplus funds into the General Temple Fund.
                                        </div>

                                        @if(Auth::user()->hasPermission('close_events') || Auth::user()->hasPermission('manage_events') || Auth::user()->hasRole('super_admin'))
                                            <button type="button" class="btn btn-primary rounded-pill w-100 py-2 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);" data-bs-toggle="modal" data-bs-target="#settleFundsModal">
                                                <i class="bi bi-check2-circle me-1"></i> {{ __('events.settle_event_funds') }}
                                            </button>
                                        @else
                                            <div class="text-muted small text-center">Settlement requires event closing authorization permissions.</div>
                                        @endif
                                    @else
                                        <div class="alert alert-light border rounded-3 p-3 text-muted small">
                                            <i class="bi bi-clock me-1"></i> {{ __('events.financial_settlement_pending_notice') }}
                                            <div class="mt-2">Current event status: <span class="badge bg-secondary">{{ __('events.status_'.$event->status) }}</span></div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Details & Schedule -->
        <div class="tab-pane fade" id="tab-details" role="tabpanel">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <!-- Schedule & Location -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-calendar3 me-2 text-primary"></i>{{ __('events.event_dates') }}</h6>
                            <div class="mb-3">
                                <label class="small text-muted mb-0">{{ __('events.start_date') }}</label>
                                <div class="fw-semibold text-dark">{{ $event->start_date->format('d F Y') }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted mb-0">{{ __('events.end_date') }}</label>
                                <div class="fw-semibold text-dark">{{ $event->end_date->format('d F Y') }}</div>
                            </div>
                            <div>
                                <label class="small text-muted mb-0">{{ __('events.venue') }}</label>
                                <div class="fw-semibold text-dark">{{ $event->venue ?: '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Collection Window -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-cash-stack me-2 text-primary"></i>{{ __('events.collection_period') }}</h6>
                            @if($event->collection_start_date && $event->collection_end_date)
                                <div class="mb-3">
                                    <label class="small text-muted mb-0">{{ __('events.collection_start_date') }}</label>
                                    <div class="fw-semibold text-dark">{{ $event->collection_start_date->format('d F Y') }}</div>
                                </div>
                                <div>
                                    <label class="small text-muted mb-0">{{ __('events.collection_end_date') }}</label>
                                    <div class="fw-semibold text-dark">{{ $event->collection_end_date->format('d F Y') }}</div>
                                </div>
                            @else
                                <div class="text-muted small">{{ __('common.none') }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Administrative Details -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check me-2 text-primary"></i>{{ __('common.details') }}</h6>
                            <div class="mb-2 small">
                                <span class="text-muted">{{ __('events.created_by') }}:</span>
                                <span class="fw-semibold text-dark ms-1">{{ $event->creator?->name ?? '—' }}</span>
                            </div>
                            <div class="mb-2 small">
                                <span class="text-muted">{{ __('events.created_at') }}:</span>
                                <span class="fw-semibold text-dark ms-1">{{ $event->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            @if($event->updated_by)
                                <div class="mb-2 small">
                                    <span class="text-muted">{{ __('events.last_updated') }}:</span>
                                    <span class="fw-semibold text-dark ms-1">{{ $event->updated_at->format('d M Y, h:i A') }}</span>
                                </div>
                            @endif
                            @if($event->reopened_by)
                                <div class="mt-3 pt-2 border-top small">
                                    <div class="text-success fw-bold"><i class="bi bi-arrow-repeat me-1"></i>{{ __('events.action_reopened') }}</div>
                                    <div><span class="text-muted">{{ __('events.reopened_by') }}:</span> {{ $event->reopenedBy?->name ?? '—' }}</div>
                                    <div><span class="text-muted">{{ __('events.reopened_at') }}:</span> {{ $event->reopened_at?->format('d M Y, h:i A') }}</div>
                                    @if($event->reopen_reason)
                                        <div class="fst-italic text-muted mt-1">&ldquo;{{ $event->reopen_reason }}&rdquo;</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <!-- Descriptions -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4 p-md-5">
                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-card-text me-2 text-primary"></i>{{ __('events.description') }}</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6 class="fw-semibold text-muted small text-uppercase mb-2">{{ __('events.description_en') }}</h6>
                                    <div class="p-3 bg-light rounded-3 text-dark" style="min-height: 120px; white-space: pre-line;">{{ $event->description_en ?: '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-semibold text-muted small text-uppercase mb-2">{{ __('events.description_bn') }}</h6>
                                    <div class="p-3 bg-light rounded-3 text-dark" style="min-height: 120px; white-space: pre-line;">{{ $event->description_bn ?: '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lifecycle Transitions Toolbar (when not closed) -->
                    @if(! $event->isClosed() && Auth::user()->hasPermission('manage_events'))
                        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-arrow-left-right me-2 text-primary"></i>{{ __('events.transition_status') }}</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    @if($event->status === 'draft')
                                        <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="target_status" value="upcoming">
                                            <button type="submit" class="btn btn-outline-info rounded-pill px-3">
                                                <i class="bi bi-arrow-right-circle me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_upcoming')]) }}
                                            </button>
                                        </form>
                                        <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="target_status" value="active">
                                            <button type="submit" class="btn btn-outline-success rounded-pill px-3">
                                                <i class="bi bi-play-circle me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_active')]) }}
                                            </button>
                                        </form>
                                    @elseif($event->status === 'upcoming')
                                        <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="target_status" value="active">
                                            <button type="submit" class="btn btn-success rounded-pill px-3">
                                                <i class="bi bi-play-circle-fill me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_active')]) }}
                                            </button>
                                        </form>
                                        <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="target_status" value="draft">
                                            <button type="submit" class="btn btn-outline-secondary rounded-pill px-3">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_draft')]) }}
                                            </button>
                                        </form>
                                    @elseif($event->status === 'active')
                                        <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="target_status" value="closing">
                                            <button type="submit" class="btn btn-outline-warning rounded-pill px-3 text-dark">
                                                <i class="bi bi-hourglass-split me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_closing')]) }}
                                            </button>
                                        </form>
                                    @elseif($event->status === 'closing')
                                        <form action="{{ route('events.transition', $event) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="target_status" value="active">
                                            <button type="submit" class="btn btn-outline-success rounded-pill px-3">
                                                <i class="bi bi-play-circle me-1"></i> {{ __('events.transition_to', ['status' => __('events.status_active')]) }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tab 4: Lifecycle History & Audit -->
        <div class="tab-pane fade" id="tab-lifecycle" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-warning-emphasis"></i>{{ __('events.lifecycle_history') }}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="eventLifecycleLogsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ __('events.lifecycle_action') }}</th>
                                    <th>{{ __('events.lifecycle_transition') }}</th>
                                    <th>{{ __('events.lifecycle_reason') }}</th>
                                    <th>{{ __('events.lifecycle_user') }}</th>
                                    <th class="text-end pe-3">{{ __('events.lifecycle_date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($event->lifecycleLogs as $log)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark border fw-bold" style="font-size: 0.72rem;">
                                                {{ __('events.action_'.$log->action) }}
                                            </span>
                                            @if($log->closing_mode)
                                                <div class="small mt-1">
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.65rem;">
                                                        {{ $log->closing_mode === 'close_and_lock' ? __('events.mode_close_and_lock') : __('events.mode_close_with_authorized_adjustments') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->from_status || $log->to_status)
                                                <div class="small">
                                                    <span class="badge bg-light text-secondary border">{{ $log->from_status ? __('events.status_'.$log->from_status) : '—' }}</span>
                                                    <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                                    <span class="badge bg-light text-dark border">{{ $log->to_status ? __('events.status_'.$log->to_status) : '—' }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif

                                            @if(! empty($log->changes))
                                                <div class="mt-2">
                                                    <a class="small text-primary text-decoration-none" data-bs-toggle="collapse" href="#logChanges{{ $log->id }}" role="button">
                                                        <i class="bi bi-code-slash me-1"></i>{{ __('events.lifecycle_changes') }} ({{ count($log->changes) }})
                                                    </a>
                                                    <div class="collapse mt-1" id="logChanges{{ $log->id }}">
                                                        <div class="p-2 bg-light border rounded small font-monospace" style="font-size: 0.72rem;">
                                                            @foreach($log->changes as $field => $diff)
                                                                <div><strong>{{ $field }}:</strong> {{ is_array($diff) ? json_encode($diff) : $diff }}</div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small text-dark">{{ $log->reason ?: '—' }}</div>
                                            @if($log->ip_address)
                                                <div class="text-muted" style="font-size: 0.68rem;"><i class="bi bi-globe me-1"></i>{{ $log->ip_address }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-dark">{{ $log->user?->name ?? 'System' }}</div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="small text-dark">{{ $log->created_at->format('d M Y') }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ $log->created_at->format('h:i A') }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            {{ __('events.no_logs_found') }}
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

<!-- Modal A: Above Pledged Target Donors Modal -->
<div class="modal fade" id="aboveTargetModal" tabindex="-1" aria-labelledby="aboveTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-up-circle-fill text-success fs-4"></i>
                    <h5 class="modal-title fw-bold text-dark" id="aboveTargetModalLabel">
                        {{ __('events.above_target_donors_report') }}
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-success border-0 rounded-3 mb-4 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Devotees whose verified payments exceeded their initial pledge commitment for this event, detailing excess amounts and selected destination funds.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="aboveTargetTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('donations.payer') }}</th>
                                <th>{{ __('events.event') }}</th>
                                <th class="text-end">{{ __('events.total_pledged') }} (৳)</th>
                                <th class="text-end">{{ __('events.verified_pledge_collections') }} (৳)</th>
                                <th class="text-end">{{ __('donations.additional_contribution') }} (৳)</th>
                                <th>{{ __('donations.excess_destination') }}</th>
                                <th>{{ __('donations.receipt_number') }}</th>
                                <th class="text-end pe-3">{{ __('donations.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($aboveTargetDonors as $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">{{ $item['donor_name'] }}</div>
                                        <div class="small text-muted font-monospace">{{ $item['pledge_code'] }} &bull; {{ $item['donor_phone'] }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $event->name }}</span>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        ৳{{ number_format($item['pledged_amount'], 2) }}
                                    </td>
                                    <td class="text-end text-success fw-bold">
                                        ৳{{ number_format($item['verified_pledge_amount'], 2) }}
                                    </td>
                                    <td class="text-end text-primary fw-bold">
                                        +৳{{ number_format($item['excess_amount'], 2) }}
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                            {{ $item['destinations'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">{{ $item['receipt_numbers'] }}</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            {{ __('donations.status_'.$item['status']) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        {{ __('donations.no_records_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal B: Outstanding Dues Modal -->
<div class="modal fade" id="outstandingDuesModal" tabindex="-1" aria-labelledby="outstandingDuesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-danger fs-4"></i>
                    <h5 class="modal-title fw-bold text-dark" id="outstandingDuesModalLabel">
                        {{ __('events.outstanding_dues_report') }}
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning border-0 rounded-3 mb-4 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Devotees with unpaid or partial event pledges, computed strictly as <code>max(0, Pledged &minus; Verified)</code> without counting pending or unverified payments.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="outstandingDuesTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('donations.payer') }}</th>
                                <th>{{ __('events.event') }}</th>
                                <th class="text-end">{{ __('events.total_pledged') }} (৳)</th>
                                <th class="text-end">{{ __('events.verified_pledge_collections') }} (৳)</th>
                                <th class="text-end text-danger">{{ __('events.outstanding_pledges') }} (৳)</th>
                                <th>{{ __('donations.payment_date') }}</th>
                                <th>{{ __('donations.status') }}</th>
                                <th class="text-end pe-3">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($outstandingDues as $due)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">{{ $due['donor_name'] }}</div>
                                        <div class="small text-muted font-monospace">{{ $due['pledge_code'] }} &bull; {{ $due['donor_phone'] }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $event->name }}</span>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        ৳{{ number_format($due['pledged_amount'], 2) }}
                                    </td>
                                    <td class="text-end text-success">
                                        ৳{{ number_format($due['verified_paid'], 2) }}
                                    </td>
                                    <td class="text-end text-danger fw-bold">
                                        ৳{{ number_format($due['outstanding_amount'], 2) }}
                                    </td>
                                    <td>
                                        <div class="small text-muted">{{ $due['last_payment_date'] }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $due['status'] === 'partial' ? 'bg-warning bg-opacity-10 text-warning-emphasis' : 'bg-danger bg-opacity-10 text-danger' }} border px-2 py-1">
                                            {{ __('donations.status_'.$due['status']) }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        @if(! $event->isLocked())
                                            <a href="{{ route('collections.create', ['pledge_id' => $due['pledge_id']]) }}" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                <i class="bi bi-cash me-1"></i> {{ __('donations.collect_payment') }}
                                            </a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        {{ __('donations.no_records_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal C: Excess Contribution Report Modal -->
<div class="modal fade" id="excessReportModal" tabindex="-1" aria-labelledby="excessReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-gift-fill text-primary fs-4"></i>
                    <h5 class="modal-title fw-bold text-dark" id="excessReportModalLabel">
                        {{ __('events.excess_contributions_report') }}
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-primary bg-primary bg-opacity-10 border-0 rounded-3 mb-4 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Comprehensive audit trail of all excess contributions originating from this event's pledges, detailing whether contributors routed excess funds to the Current Event Fund or General Temple Fund.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="excessReportTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">{{ __('donations.payer') }}</th>
                                <th>{{ __('donations.pledges') }}</th>
                                <th class="text-end">{{ __('donations.additional_contribution') }} (৳)</th>
                                <th>{{ __('donations.excess_destination') }}</th>
                                <th>{{ __('donations.payment_date') }}</th>
                                <th>{{ __('donations.receipt_number') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($excessReport as $ex)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark">{{ $ex['donor_name'] }}</div>
                                        <div class="small text-muted font-monospace">{{ $ex['payment_code'] }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">{{ $ex['pledge_code'] }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-primary">
                                        ৳{{ number_format($ex['amount'], 2) }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $ex['target_type'] === 'voluntary_event' ? 'bg-primary' : 'bg-success' }} bg-opacity-10 {{ $ex['target_type'] === 'voluntary_event' ? 'text-primary' : 'text-success' }} border">
                                            {{ $ex['destination_fund'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small text-dark">{{ $ex['payment_date'] }}</div>
                                    </td>
                                    <td>
                                        @if($ex['receipt_number'] !== '—' && ! empty($ex['receipt_id']))
                                            <a href="{{ route('receipts.show', $ex['receipt_id']) }}" class="badge bg-light text-dark border text-decoration-none">
                                                <i class="bi bi-receipt me-1"></i>{{ $ex['receipt_number'] }}
                                            </a>
                                        @else
                                            <span class="badge bg-light text-dark border font-monospace">{{ $ex['receipt_number'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        {{ __('donations.no_records_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal D: Settle Event Funds Modal -->
@if(in_array($event->status, ['closing', 'closed']) && ! $metrics['is_settled'] && (Auth::user()->hasPermission('close_events') || Auth::user()->hasPermission('manage_events') || Auth::user()->hasRole('super_admin')))
    <div class="modal fade" id="settleFundsModal" tabindex="-1" aria-labelledby="settleFundsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('events.settle-funds', $event) }}" method="POST" id="settleFundsForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-bank fs-4 text-success"></i>
                            <h5 class="modal-title fw-bold text-dark" id="settleFundsModalLabel">
                                {{ __('events.settle_event_funds') }}
                            </h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 rounded-3 mb-4">
                            <i class="bi bi-info-circle me-1"></i>
                            {{ __('events.settlement_notice') }}
                        </div>

                        <!-- Summary Snapshot inside modal -->
                        <div class="card border rounded-3 p-3 bg-light mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">{{ __('events.total_verified_collections') }}:</span>
                                <span class="fw-bold text-success fs-6">৳{{ number_format($metrics['total_verified_collections'], 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">{{ __('events.total_approved_expenses') }}:</span>
                                <span class="fw-bold text-danger fs-6">&minus; ৳{{ number_format($metrics['total_approved_expenses'], 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fw-bold text-dark fs-6">{{ __('events.net_surplus') }} to Transfer:</span>
                                <span class="fw-bold text-primary fs-5">৳{{ number_format($metrics['net_surplus'], 2) }}</span>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold required">{{ __('events.settlement_date') }}</label>
                                <input type="date" name="settlement_date" class="form-control" value="{{ old('settlement_date', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Destination Fund</label>
                                <input type="text" class="form-control" value="3010 - General Temple Fund" readonly disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('events.settlement_notes') }}</label>
                            <textarea name="notes" rows="3" class="form-control" placeholder="Provide any concluding counter remarks or executive approval notes for this settlement...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmSettleBtn" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> {{ __('events.confirm_settlement') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Modal 1: Close Event Modal -->
@if(! $event->isClosed() && Auth::user()->hasPermission('close_events'))
    <div class="modal fade" id="closeEventModal" tabindex="-1" aria-labelledby="closeEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('events.close', $event) }}" method="POST" id="closeEventForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="closeEventModalLabel">
                            <i class="bi bi-door-closed me-2 text-warning"></i>{{ __('events.close_event_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 rounded-3 mb-4">
                            <i class="bi bi-info-circle me-1"></i>{{ __('events.close_event_warning') }}
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold required">{{ __('events.closing_mode') }}</label>
                            
                            <div class="form-check p-3 border rounded-3 mb-3 bg-light">
                                <input class="form-check-input ms-0 me-3" type="radio" name="closing_mode" id="modeLock" value="close_and_lock" checked>
                                <label class="form-check-label" for="modeLock">
                                    <div class="fw-bold text-danger"><i class="bi bi-lock-fill me-1"></i>{{ __('events.mode_close_and_lock') }}</div>
                                    <div class="small text-muted mt-1">{{ __('events.mode_close_and_lock_desc') }}</div>
                                </label>
                            </div>

                            <div class="form-check p-3 border rounded-3 bg-light">
                                <input class="form-check-input ms-0 me-3" type="radio" name="closing_mode" id="modeAdjust" value="close_with_authorized_adjustments">
                                <label class="form-check-label" for="modeAdjust">
                                    <div class="fw-bold text-warning-emphasis"><i class="bi bi-pencil-square me-1"></i>{{ __('events.mode_close_with_authorized_adjustments') }}</div>
                                    <div class="small text-muted mt-1">{{ __('events.mode_close_with_authorized_adjustments_desc') }}</div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('events.closing_reason') }}</label>
                            <textarea name="closing_reason" rows="3" class="form-control" placeholder="Provide justification for closing this event..." required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmCloseBtn" class="btn btn-warning rounded-pill px-4 text-dark fw-bold">
                            <i class="bi bi-check-circle me-1"></i>{{ __('events.close_event') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Modal 2: Reopen Event Modal -->
@if($event->isClosed() && Auth::user()->hasPermission('reopen_events'))
    <div class="modal fade" id="reopenEventModal" tabindex="-1" aria-labelledby="reopenEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('events.reopen', $event) }}" method="POST" id="reopenEventForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="reopenEventModalLabel">
                            <i class="bi bi-arrow-repeat me-2 text-success"></i>{{ __('events.reopen_event_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 rounded-3 mb-4">
                            <i class="bi bi-shield-check me-1"></i>{{ __('events.reopen_event_warning') }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('events.target_status') }}</label>
                            <select name="target_status" class="form-select" required>
                                <option value="active">{{ __('events.status_active') }}</option>
                                <option value="upcoming">{{ __('events.status_upcoming') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('events.reopen_reason') }}</label>
                            <textarea name="reopen_reason" rows="3" class="form-control" placeholder="Provide justification for reopening this closed event..." required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmReopenBtn" class="btn btn-success rounded-pill px-4">
                            <i class="bi bi-check2-circle me-1"></i>{{ __('events.reopen_event') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Modal 3: Post-Closing Adjustment Modal (Mode B only) -->
@if($event->allowsAuthorizedAdjustments() && Auth::user()->hasPermission('adjust_closed_events'))
    <div class="modal fade" id="adjustEventModal" tabindex="-1" aria-labelledby="adjustEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('events.adjust', $event) }}" method="POST" id="adjustEventForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="adjustEventModalLabel">
                            <i class="bi bi-pencil-square me-2 text-primary"></i>{{ __('events.post_close_adjust_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-secondary border-0 rounded-3 mb-4">
                            <i class="bi bi-info-circle me-1"></i>{{ __('events.post_close_adjust_notice') }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('events.venue') }}</label>
                            <input type="text" name="venue" class="form-control" value="{{ old('venue', $event->venue) }}">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('events.description_en') }}</label>
                                <textarea name="description_en" rows="4" class="form-control">{{ old('description_en', $event->description_en) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('events.description_bn') }}</label>
                                <textarea name="description_bn" rows="4" class="form-control">{{ old('description_bn', $event->description_bn) }}</textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('events.adjustment_reason') }}</label>
                            <textarea name="adjustment_reason" rows="3" class="form-control" placeholder="Provide justification for this post-closing modification..." required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="confirmAdjustBtn" class="btn btn-primary rounded-pill px-4" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                            <i class="bi bi-check2-circle me-1"></i>{{ __('common.save_changes') }}
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
        TempleUI.initDataTable('#donorComparisonTable', {
            pageLength: 10,
            searching: true,
            paging: true,
            info: true,
            order: [[5, 'desc']]
        });

        TempleUI.initDataTable('#aboveTargetTable', {
            pageLength: 10,
            searching: true,
            paging: true,
            info: true,
            order: [[4, 'desc']]
        });

        TempleUI.initDataTable('#outstandingDuesTable', {
            pageLength: 10,
            searching: true,
            paging: true,
            info: true,
            order: [[4, 'desc']]
        });

        TempleUI.initDataTable('#excessReportTable', {
            pageLength: 10,
            searching: true,
            paging: true,
            info: true,
            order: [[2, 'desc']]
        });

        TempleUI.initDataTable('#eventLifecycleLogsTable', {
            pageLength: 10,
            searching: false,
            paging: true,
            info: true,
            order: [[4, 'desc']]
        });

        $('#closeEventForm').on('submit', function() {
            TempleUI.button.loading('#confirmCloseBtn');
        });

        $('#reopenEventForm').on('submit', function() {
            TempleUI.button.loading('#confirmReopenBtn');
        });

        $('#adjustEventForm').on('submit', function() {
            TempleUI.button.loading('#confirmAdjustBtn');
        });

        $('#settleFundsForm').on('submit', function() {
            TempleUI.button.loading('#confirmSettleBtn');
        });
    });
</script>
@endpush
