@extends('layouts.app')

@section('title', __('donations.committee_contributions'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('donations.committee_contributions') }}</h1>
            <p class="text-muted small mb-0">{{ __('donations.all_contributions') }} &bull; {{ $contributions->count() }} {{ __('common.details') }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('collections.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('donations.dashboard') }}
            </a>
            @if(Auth::user()->hasPermission('manage_committee_contributions'))
                <button type="button" class="btn btn-outline-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">
                    <i class="bi bi-lightning-charge me-1"></i> {{ __('donations.generate_contributions') }}
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);" data-bs-toggle="modal" data-bs-target="#addContributionModal">
                    <i class="bi bi-plus-circle-fill me-1"></i> {{ __('donations.add_contribution') }}
                </button>
            @endif
        </div>
    </div>

    <!-- Non-Refundable Temple Fund Notice -->
    <div class="alert alert-info border-0 rounded-4 shadow-sm d-flex align-items-center gap-3 mb-4 p-3">
        <i class="bi bi-info-circle-fill fs-3 text-info"></i>
        <div class="small">
            <strong>{{ __('common.note') }}:</strong> {{ __('donations.committee_contribution_notice') }}
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">{{ __('donations.expected_amount') }}</span>
                        <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-calendar-check fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0">৳{{ number_format($totalExpected, 2) }}</h3>
                    <small class="text-muted">{{ __('donations.contribution_month') }}: {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">{{ __('donations.paid_amount') }}</span>
                        <div class="p-2 rounded-circle bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check-circle fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-0">৳{{ number_format($totalPaid, 2) }}</h3>
                    <small class="text-muted">{{ __('donations.verified_amount') }}</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">{{ __('donations.outstanding_amount') }}</span>
                        <div class="p-2 rounded-circle bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-hourglass-split fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold {{ $totalOutstanding > 0 ? 'text-danger' : 'text-muted' }} mb-0">৳{{ number_format($totalOutstanding, 2) }}</h3>
                    <small class="text-muted">{{ __('donations.status_unpaid') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('contributions.index') }}" class="row g-3 align-items-end" id="contributionsFilterForm">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">{{ __('committees.committee') ?? 'Committee' }}</label>
                    <select name="committee_id" id="filterCommittee" class="form-select select2">
                        @foreach($committees as $committee)
                            <option value="{{ $committee->id }}" {{ $selectedCommitteeId == $committee->id ? 'selected' : '' }}>
                                {{ $committee->name_en }} ({{ $committee->name_bn ?? $committee->name_en }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">{{ __('donations.contribution_month') }}</label>
                    <input type="month" name="month" id="filterMonth" class="form-control" value="{{ $selectedMonth }}">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">
                        <i class="bi bi-funnel me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('contributions.index') }}" class="btn btn-outline-secondary rounded-pill" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Contributions Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="contributionsDataTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('donations.contribution_code') }}</th>
                            <th>{{ __('people.person') ?? 'Member' }}</th>
                            <th>{{ __('committees.designation') ?? 'Designation' }}</th>
                            <th>{{ __('donations.expected_amount') }}</th>
                            <th>{{ __('donations.paid_amount') }}</th>
                            <th>{{ __('donations.outstanding_amount') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contributions as $contribution)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace">{{ $contribution->contribution_code }}</span>
                                    <div class="small text-muted mt-1">{{ $contribution->month_name }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $contribution->member_name }}</div>
                                    @if($contribution->membership?->person?->phone)
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $contribution->membership->person->phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                        {{ $contribution->membership?->designation ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">৳{{ number_format((float) $contribution->expected_amount, 2) }}</div>
                                </td>
                                <td>
                                    <div class="text-success fw-semibold">৳{{ number_format($contribution->verified_paid_amount, 2) }}</div>
                                    @if($contribution->has_excess_contribution)
                                        <div class="small text-primary fw-semibold" title="{{ __('donations.excess_committee_contribution_notice', ['amount' => number_format($contribution->excess_contributed_amount, 2)]) }}">
                                            <i class="bi bi-heart-fill me-1"></i>+৳{{ number_format($contribution->excess_contributed_amount, 2) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold {{ $contribution->outstanding_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                        ৳{{ number_format($contribution->outstanding_amount, 2) }}
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match ($contribution->status) {
                                            'paid' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                            'partial' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25',
                                            'unpaid' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                            'waived' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                            default => 'bg-secondary bg-opacity-10 text-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} px-2 py-1">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>{{ __('donations.status_'.$contribution->status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        @if(Auth::user()->hasPermission('record_collections') && $contribution->outstanding_amount > 0 && $contribution->status !== 'waived')
                                            <a href="{{ route('collections.create', ['monthly_contribution_id' => $contribution->id]) }}" class="btn btn-outline-success" title="{{ __('donations.collect_payment') }}">
                                                <i class="bi bi-cash me-1"></i> {{ __('donations.collect_payment') }}
                                            </a>
                                        @endif
                                        @if(Auth::user()->hasPermission('manage_committee_contributions') && $contribution->verified_paid_amount == 0 && $contribution->status !== 'waived')
                                            <button type="button" class="btn btn-outline-danger waive-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#waiveModal" 
                                                data-action="{{ route('contributions.waive', $contribution) }}"
                                                data-code="{{ $contribution->contribution_code }}"
                                                data-member="{{ $contribution->member_name }}"
                                                title="{{ __('donations.waive_contribution') }}">
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Individual Contribution -->
@if(Auth::user()->hasPermission('manage_committee_contributions'))
    <div class="modal fade" id="addContributionModal" tabindex="-1" aria-labelledby="addContributionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('contributions.store') }}" method="POST" id="addContributionForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="addContributionModalLabel">
                            <i class="bi bi-plus-circle me-2 text-primary"></i>{{ __('donations.add_contribution') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('people.person') ?? 'Committee Member' }}</label>
                            <select name="committee_membership_id" class="form-select select2" required>
                                <option value="">Select Member</option>
                                @foreach($activeMemberships as $membership)
                                    <option value="{{ $membership->id }}">
                                        {{ $membership->person->name_en }} ({{ $membership->designation }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('donations.contribution_month') }}</label>
                            <input type="month" name="contribution_month" class="form-control" value="{{ $selectedMonth }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('donations.expected_amount') }} (৳)</label>
                            <input type="number" step="0.01" min="1" name="expected_amount" class="form-control fw-bold" value="500.00" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">{{ __('donations.notes') }}</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                            <i class="bi bi-check-circle me-1"></i> {{ __('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Bulk Generate Contributions -->
    <div class="modal fade" id="bulkGenerateModal" tabindex="-1" aria-labelledby="bulkGenerateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('contributions.bulk-generate') }}" method="POST" id="bulkGenerateForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="bulkGenerateModalLabel">
                            <i class="bi bi-lightning-charge me-2 text-warning"></i>{{ __('donations.generate_contributions') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border rounded-3 mb-3 small">
                            This will create monthly contribution obligations for all active members of the selected committee for the designated month, skipping any members who already have an entry for that month.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('committees.committee') ?? 'Committee' }}</label>
                            <select name="committee_id" class="form-select select2" required>
                                @foreach($committees as $committee)
                                    <option value="{{ $committee->id }}" {{ $selectedCommitteeId == $committee->id ? 'selected' : '' }}>
                                        {{ $committee->name_en }} ({{ $committee->name_bn ?? $committee->name_en }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('donations.contribution_month') }}</label>
                            <input type="month" name="contribution_month" class="form-control" value="{{ $selectedMonth }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('donations.expected_amount') }} (৳)</label>
                            <input type="number" step="0.01" min="1" name="default_expected_amount" class="form-control fw-bold" value="500.00" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold">
                            <i class="bi bi-lightning-charge me-1"></i> {{ __('donations.generate_contributions') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Waive Contribution -->
    <div class="modal fade" id="waiveModal" tabindex="-1" aria-labelledby="waiveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="" method="POST" id="waiveContributionForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="waiveModalLabel">
                            <i class="bi bi-slash-circle me-2 text-danger"></i>{{ __('donations.waive_contribution') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 rounded-3 mb-3 small">
                            Are you sure you want to waive the monthly contribution for <strong id="waiveMemberName"></strong> (<span id="waiveCode" class="font-monospace"></span>)? This obligation will be marked waived and no further payment will be expected.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold required">{{ __('donations.waiver_reason') }}</label>
                            <textarea name="waiver_reason" rows="3" class="form-control" required placeholder="State the reason for waiving this contribution..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">
                            <i class="bi bi-check-circle me-1"></i> Confirm Waiver
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
        @if($contributions->isNotEmpty())
            TempleUI.initDataTable('#contributionsDataTable', {
                pageLength: 25,
                columns: [
                    { orderable: true },
                    { orderable: true },
                    { orderable: true },
                    { orderable: true },
                    { orderable: false },
                    { orderable: false },
                    { orderable: true },
                    { orderable: false }
                ]
            });
        @endif

        $('.waive-btn').on('click', function() {
            var actionUrl = $(this).data('action');
            var code = $(this).data('code');
            var member = $(this).data('member');

            $('#waiveContributionForm').attr('action', actionUrl);
            $('#waiveCode').text(code);
            $('#waiveMemberName').text(member);
        });
    });
</script>
@endpush
