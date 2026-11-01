@extends('layouts.app')

@section('title', $purchaseRequest->title.' - '.__('procurement.request_details'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">{{ $purchaseRequest->title }}</h1>
                {!! $purchaseRequest->status_badge !!}
                @if($purchaseRequest->is_converted_to_po)
                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                        <i class="bi bi-box-seam me-1"></i>{{ __('procurement.converted_to_po') }}
                    </span>
                @endif
            </div>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('procurement.requests.index') }}">{{ __('procurement.purchase_requests') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $purchaseRequest->title }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(Auth::user()->hasPermission('manage_purchase_requests'))
                @if($purchaseRequest->canBeEdited())
                    <a href="{{ route('procurement.requests.edit', $purchaseRequest) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil me-1"></i> {{ __('common.edit') }}
                    </a>
                @endif

                @if($purchaseRequest->canBeSubmitted())
                    <form action="{{ route('procurement.requests.submit', $purchaseRequest) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('{{ __('procurement.submit_request_confirm') }}')">
                            <i class="bi bi-send me-1"></i> {{ __('procurement.submit_for_approval') }}
                        </button>
                    </form>
                @endif

                @if($purchaseRequest->canBeCancelled())
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="bi bi-slash-circle me-1"></i> {{ __('procurement.cancel_budget') }}
                    </button>
                @endif
            @endif

            @if(Auth::user()->hasPermission('approve_purchase_requests') && $purchaseRequest->canBeApproved())
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                    <i class="bi bi-check-circle me-1"></i> {{ __('common.approve') }}
                </button>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle me-1"></i> {{ __('common.reject') }}
                </button>
            @endif

            <a href="{{ route('procurement.requests.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Rejection Banner if Rejected -->
    @if($purchaseRequest->status === 'rejected')
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-octagon-fill fs-4 me-3 text-danger"></i>
            <div>
                <div class="fw-bold">{{ __('procurement.status_rejected') }} ({{ $purchaseRequest->rejected_at?->format('d M Y, h:i A') }})</div>
                <div class="small"><strong>{{ __('procurement.rejection_reason') }}:</strong> {{ $purchaseRequest->rejection_reason }}</div>
            </div>
        </div>
    @endif

    <!-- Budget Utilization Alert Banner -->
    @if($budgetAnalysis['has_budget'])
        <div class="card border-0 shadow-sm mb-4 border-start border-4 {{ $budgetAnalysis['is_over_budget'] ? 'border-warning' : 'border-success' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge {{ $budgetAnalysis['is_over_budget'] ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }} mb-1">
                            <i class="bi {{ $budgetAnalysis['is_over_budget'] ? 'bi-exclamation-triangle' : 'bi-shield-check' }} me-1"></i>
                            {{ $budgetAnalysis['is_over_budget'] ? __('procurement.over_budget_warning') : __('procurement.within_budget') }}
                        </span>
                        <div class="fw-bold text-dark">
                            {{ __('procurement.select_budget') }}: <span class="text-primary">{{ $purchaseRequest->budget?->title ?? $budgetAnalysis['budget_code'] }}</span>
                        </div>
                    </div>
                    <div class="text-md-end">
                        <div class="small text-muted">{{ __('procurement.budget_allocated') }}: <strong>৳{{ number_format($budgetAnalysis['total_budget'], 2) }}</strong></div>
                        <div class="small text-muted">{{ __('procurement.budget_requested') }}: <strong class="text-primary">৳{{ number_format((float) $purchaseRequest->total_estimated_amount, 2) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left: Overview & Line Items -->
        <div class="col-lg-8">
            <!-- Line Items Table -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-list-check me-1 text-primary"></i> {{ __('procurement.line_items') }}
                    </h6>
                    <span class="badge bg-primary-subtle text-primary">{{ $purchaseRequest->items->count() }} {{ __('procurement.items') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('procurement.item_description') }}</th>
                                    <th>{{ __('procurement.select_expense_account') }}</th>
                                    <th class="text-center">{{ __('procurement.quantity') }}</th>
                                    <th class="text-end">{{ __('procurement.estimated_unit_price') }}</th>
                                    <th class="text-end pe-3">{{ __('procurement.estimated_total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseRequest->items as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $item->description }}</div>
                                            @if($item->notes)
                                                <small class="text-muted"><i class="bi bi-info me-1"></i>{{ $item->notes }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $item->expenseAccount?->account_name_en }}</div>
                                        </td>
                                        <td class="text-center font-monospace">
                                            {{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} {{ $item->unit }}
                                        </td>
                                        <td class="text-end font-monospace">
                                            ৳{{ number_format((float) $item->estimated_unit_price, 2) }}
                                        </td>
                                        <td class="text-end fw-bold font-monospace pe-3">
                                            ৳{{ number_format((float) $item->estimated_total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end fw-bold">{{ __('procurement.total_estimated_amount') }}:</th>
                                    <th class="text-end fw-bold text-primary fs-5 pe-3">
                                        ৳{{ number_format((float) $purchaseRequest->total_estimated_amount, 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Justification & Notes -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-card-text me-1 text-primary"></i> {{ __('procurement.justification') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0 text-muted">{{ $purchaseRequest->justification ?: __('common.none') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-journal-text me-1 text-primary"></i> {{ __('procurement.notes') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0 text-muted">{{ $purchaseRequest->notes ?: __('common.none') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Requisition Metadata & Lifecycle -->
        <div class="col-lg-4">
            <!-- Metadata Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-1 text-primary"></i> {{ __('procurement.request_details') }}
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">{{ __('procurement.request_date') }}</dt>
                        <dd class="col-sm-7">{{ $purchaseRequest->request_date?->format('d M Y') }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.requested_by') }}</dt>
                        <dd class="col-sm-7 fw-medium">{{ $purchaseRequest->requester?->name ?? '-' }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.scope') }}</dt>
                        <dd class="col-sm-7">
                            @if($purchaseRequest->event)
                                <span class="badge bg-light text-dark border">{{ $purchaseRequest->event->title_en }}</span>
                            @else
                                <span class="badge bg-light text-secondary border">{{ __('procurement.type_general_fund') }}</span>
                            @endif
                        </dd>

                        @if($purchaseRequest->budget)
                            <dt class="col-sm-5 text-muted small">{{ __('procurement.select_budget') }}</dt>
                            <dd class="col-sm-7">
                                <a href="{{ route('procurement.budgets.show', $purchaseRequest->budget) }}" class="text-decoration-none">
                                    {{ $purchaseRequest->budget->title }}
                                </a>
                            </dd>
                        @endif

                        <hr class="my-2">

                        <dt class="col-sm-5 text-muted small">{{ __('common.created_by') }}</dt>
                        <dd class="col-sm-7 small">{{ $purchaseRequest->creator?->name ?? '-' }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('common.created_at') }}</dt>
                        <dd class="col-sm-7 small">{{ $purchaseRequest->created_at->format('d M Y, h:i A') }}</dd>

                        @if($purchaseRequest->submitted_at)
                            <dt class="col-sm-5 text-muted small">{{ __('procurement.status_submitted') }}</dt>
                            <dd class="col-sm-7 small">{{ $purchaseRequest->submitted_at->format('d M Y, h:i A') }}</dd>
                        @endif

                        @if($purchaseRequest->approved_by)
                            <dt class="col-sm-5 text-muted small">{{ __('procurement.status_approved') }}</dt>
                            <dd class="col-sm-7 small text-success">{{ $purchaseRequest->approver?->name }} ({{ $purchaseRequest->approved_at?->format('d M Y') }})</dd>
                        @endif

                        @if($purchaseRequest->rejected_by)
                            <dt class="col-sm-5 text-muted small">{{ __('procurement.status_rejected') }}</dt>
                            <dd class="col-sm-7 small text-danger">{{ $purchaseRequest->rejecter?->name }} ({{ $purchaseRequest->rejected_at?->format('d M Y') }})</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.requests.approve', $purchaseRequest) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveModalLabel">
                        <i class="bi bi-check-circle me-1"></i> {{ __('procurement.approve_budget') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('procurement.approve_request_confirm') }}</p>
                    <div class="mt-3 p-3 bg-light rounded">
                        <div><strong>{{ __('common.title') }}:</strong> <span>{{ $purchaseRequest->title }}</span></div>
                        <div><strong>{{ __('procurement.total_estimated_amount') }}:</strong> ৳{{ number_format((float) $purchaseRequest->total_estimated_amount, 2) }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle me-1"></i> {{ __('common.approve') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.requests.reject', $purchaseRequest) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="bi bi-x-circle me-1"></i> {{ __('procurement.reject_budget') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('procurement.reject_request_confirm') }}</p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label small fw-bold">
                            {{ __('procurement.rejection_reason') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="3" class="form-control"
                                  placeholder="{{ __('procurement.rejection_reason_placeholder') }}" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-x-circle me-1"></i> {{ __('common.reject') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('procurement.requests.cancel', $purchaseRequest) }}" method="POST">
                @csrf
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="cancelModalLabel">
                        <i class="bi bi-slash-circle me-1"></i> {{ __('procurement.cancel_budget') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('procurement.cancel_request_confirm') }}</p>
                    <div class="mb-3">
                        <label for="cancel_reason" class="form-label small fw-bold">{{ __('procurement.notes') }}</label>
                        <textarea name="reason" id="cancel_reason" rows="2" class="form-control"
                                  placeholder="{{ __('procurement.notes_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-dark btn-sm">
                        <i class="bi bi-slash-circle me-1"></i> {{ __('procurement.cancel_budget') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
