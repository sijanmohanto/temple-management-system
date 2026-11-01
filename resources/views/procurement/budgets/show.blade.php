@extends('layouts.app')

@section('title', $budget->title)

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">
                    <i class="bi bi-calculator text-primary me-2"></i>{{ $budget->title }}
                </h1>
                {!! $budget->status_badge !!}
            </div>
            <div class="text-muted small">
                {{ $budget->type_label }}
                @if($budget->event)
                    &bull; <i class="bi bi-calendar-event me-1 text-primary"></i>{{ $budget->event->title_en }}
                @else
                    &bull; <i class="bi bi-calendar me-1"></i>{{ __('procurement.fiscal_year') }}: {{ $budget->fiscal_year }}
                @endif
                &bull; {{ __('procurement.version') }}: {{ $budget->version }}
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('procurement.budgets.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>

            @if($budget->canBeEdited() && Auth::user()->hasPermission('manage_budgets'))
                <a href="{{ route('procurement.budgets.edit', $budget) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('common.edit') }}
                </a>
            @endif

            @if($budget->canBeSubmitted() && Auth::user()->hasPermission('manage_budgets'))
                <form action="{{ route('procurement.budgets.submit', $budget) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('procurement.submit_budget_confirm') }}')">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm text-dark">
                        <i class="bi bi-send me-1"></i> {{ __('procurement.submit_for_approval') }}
                    </button>
                </form>
            @endif

            @if($budget->canBeApprovedBy(Auth::user()))
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveBudgetModal">
                    <i class="bi bi-check-circle me-1"></i> {{ __('procurement.approve_budget') }}
                </button>
            @endif

            @if($budget->canBeRejectedBy(Auth::user()))
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectBudgetModal">
                    <i class="bi bi-x-circle me-1"></i> {{ __('procurement.reject_budget') }}
                </button>
            @endif

            @if($budget->isApproved())
                <a href="{{ route('procurement.reports.budget-vs-actual', ['budget_id' => $budget->id]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-graph-up me-1"></i> {{ __('procurement.budget_vs_actual') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Rejection Alert if rejected -->
    @if($budget->isRejected() && $budget->rejection_reason)
        <div class="alert alert-danger d-flex align-items-center mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong class="d-block">{{ __('procurement.status_rejected') }}:</strong>
                {{ $budget->rejection_reason }}
                <div class="small text-muted mt-1">
                    {{ __('common.by') }}: {{ $budget->rejecter?->name ?? 'Admin' }} &bull; {{ $budget->rejected_at?->format('d M Y, h:i A') }}
                </div>
            </div>
        </div>
    @endif

    <!-- Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.total_allocated') }}</div>
                    <div class="h3 mb-0 fw-bold text-primary">৳{{ number_format((float) $budget->total_amount, 2) }}</div>
                    <small class="text-muted">{{ $budget->items->count() }} {{ __('procurement.items') }}</small>
                </div>
            </div>
        </div>

        @if($variance)
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm border-start border-4 border-danger h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.actual_expenses') }}</div>
                        <div class="h3 mb-0 fw-bold text-danger">{{ $variance['summary']['total_actual_formatted'] }}</div>
                        <small class="text-muted">{{ __('finance.approved') }} {{ __('finance.expenses') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm border-start border-4 {{ $variance['summary']['is_over_budget'] ? 'border-danger' : 'border-success' }} h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-bold text-uppercase">
                            {{ $variance['summary']['is_over_budget'] ? __('procurement.over_budget') : __('procurement.remaining_budget') }}
                        </div>
                        <div class="h3 mb-0 fw-bold {{ $variance['summary']['is_over_budget'] ? 'text-danger' : 'text-success' }}">
                            {{ $variance['summary']['is_over_budget'] ? $variance['summary']['total_over_budget_formatted'] : $variance['summary']['total_remaining_formatted'] }}
                        </div>
                        <small class="text-muted">{{ $variance['summary']['overall_utilization_pct'] }}% {{ __('procurement.utilization_percentage') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.pending_expenses') }}</div>
                        <div class="h3 mb-0 fw-bold text-warning">{{ $variance['summary']['total_pending_formatted'] }}</div>
                        <small class="text-muted">{{ __('finance.pending_approval') }}</small>
                    </div>
                </div>
            </div>
        @else
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.budget_type') }}</div>
                        <div class="h5 mb-0 fw-bold text-dark">{{ $budget->type_label }}</div>
                        <small class="text-muted">{{ $budget->fiscal_year }}</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm border-start border-4 border-secondary h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-bold text-uppercase">{{ __('common.created_by') }}</div>
                        <div class="h6 mb-0 fw-bold text-dark">{{ $budget->creator?->name ?? 'System' }}</div>
                        <small class="text-muted">{{ $budget->created_at->format('d M Y') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm border-start border-4 border-dark h-100">
                    <div class="card-body">
                        <div class="text-muted small fw-bold text-uppercase">{{ __('common.status') }}</div>
                        <div class="h5 mb-0 fw-bold">{!! $budget->status_badge !!}</div>
                        <small class="text-muted">{{ $budget->updated_at->diffForHumans() }}</small>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Line Items Table Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-list-check me-2 text-primary"></i>{{ __('procurement.line_items') }}
            </h6>
            <span class="badge bg-light text-dark border">{{ $budget->items->count() }} {{ __('procurement.items') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('finance.chart_of_accounts') }}</th>
                            <th class="text-end">{{ __('procurement.allocated_amount') }}</th>
                            @if($variance)
                                <th class="text-end">{{ __('procurement.actual_expenses') }}</th>
                                <th class="text-end">{{ __('procurement.remaining_budget') }}</th>
                                <th class="text-center" style="width: 20%;">{{ __('procurement.utilization_percentage') }}</th>
                            @endif
                            <th>{{ __('common.notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($budget->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $item->expenseAccount?->account_name }}</div>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    ৳{{ number_format((float) $item->allocated_amount, 2) }}
                                </td>
                                @if($variance)
                                    @php
                                        $vRow = collect($variance['items'])->firstWhere('account_id', $item->financial_account_id);
                                    @endphp
                                    <td class="text-end fw-bold text-danger">
                                        {{ $vRow['actual_formatted'] ?? '৳0.00' }}
                                    </td>
                                    <td class="text-end fw-bold {{ ($vRow['is_over_budget'] ?? false) ? 'text-danger' : 'text-success' }}">
                                        {{ ($vRow['is_over_budget'] ?? false) ? '+' . $vRow['over_budget_formatted'] : $vRow['remaining_formatted'] }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar {{ ($vRow['is_over_budget'] ?? false) ? 'bg-danger' : 'bg-primary' }}" style="width: {{ min(100, $vRow['utilization_pct'] ?? 0) }}%;"></div>
                                            </div>
                                            <small class="fw-semibold text-muted" style="font-size: 0.75rem;">{{ $vRow['utilization_pct'] ?? 0 }}%</small>
                                        </div>
                                    </td>
                                @endif
                                <td class="small text-muted">{{ $item->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $variance ? 6 : 3 }}" class="text-center py-4 text-muted">
                                    {{ __('procurement.no_budgets_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th class="fw-bold">{{ __('common.total') }}:</th>
                            <th class="text-end fw-bold text-primary">৳{{ number_format((float) $budget->total_amount, 2) }}</th>
                            @if($variance)
                                <th class="text-end fw-bold text-danger">{{ $variance['summary']['total_actual_formatted'] }}</th>
                                <th class="text-end fw-bold {{ $variance['summary']['is_over_budget'] ? 'text-danger' : 'text-success' }}">
                                    {{ $variance['summary']['is_over_budget'] ? '+' . $variance['summary']['total_over_budget_formatted'] : $variance['summary']['total_remaining_formatted'] }}
                                </th>
                                <th class="text-center fw-bold">{{ $variance['summary']['overall_utilization_pct'] }}%</th>
                            @endif
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Audit Details Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-shield-check me-2 text-primary"></i>{{ __('common.audit_information') }}
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-md-3">
                    <span class="text-muted d-block">{{ __('common.created_by') }}:</span>
                    <strong>{{ $budget->creator?->name ?? 'N/A' }}</strong>
                    <div class="text-muted">{{ $budget->created_at->format('d M Y, h:i A') }}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block">{{ __('procurement.status_submitted') }}:</span>
                    <strong>{{ $budget->submitter?->name ?? 'N/A' }}</strong>
                    <div class="text-muted">{{ $budget->submitted_at?->format('d M Y, h:i A') ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block">{{ __('procurement.status_approved') }}:</span>
                    <strong>{{ $budget->approver?->name ?? 'N/A' }}</strong>
                    <div class="text-muted">{{ $budget->approved_at?->format('d M Y, h:i A') ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block">{{ __('common.notes') }}:</span>
                    <span class="text-dark">{{ $budget->notes ?: '-' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Approve Budget -->
@if($budget->canBeApprovedBy(Auth::user()))
    <div class="modal fade" id="approveBudgetModal" tabindex="-1" aria-labelledby="approveBudgetModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('procurement.budgets.approve', $budget) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-success" id="approveBudgetModalLabel">
                            <i class="bi bi-check-circle me-1"></i> {{ __('procurement.approve_budget') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">{{ __('procurement.approve_budget_confirm') }}</p>
                        <div class="alert alert-light border small text-muted">
                            <div><strong>{{ __('common.title') }}:</strong> {{ $budget->title }}</div>
                            <div><strong>{{ __('procurement.total_allocated') }}:</strong> ৳{{ number_format((float) $budget->total_amount, 2) }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-success btn-sm px-3">
                            <i class="bi bi-check-lg me-1"></i> {{ __('common.confirm') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

<!-- Modal: Reject Budget -->
@if($budget->canBeRejectedBy(Auth::user()))
    <div class="modal fade" id="rejectBudgetModal" tabindex="-1" aria-labelledby="rejectBudgetModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('procurement.budgets.reject', $budget) }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger" id="rejectBudgetModalLabel">
                            <i class="bi bi-x-circle me-1"></i> {{ __('procurement.reject_budget') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">{{ __('procurement.reject_budget_confirm') }}</p>
                        <div class="mb-3">
                            <label class="form-label required small fw-bold">{{ __('procurement.rejection_reason') }}</label>
                            <textarea name="rejection_reason" class="form-control form-control-sm" rows="3" placeholder="{{ __('procurement.rejection_reason_placeholder') }}" required minlength="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-danger btn-sm px-3">
                            <i class="bi bi-x-lg me-1"></i> {{ __('procurement.reject_budget') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
