@extends('layouts.app')

@section('title', $vendor->name.' ('.$vendor->vendor_code.')')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">{{ $vendor->name }}</h1>
                <span class="badge bg-secondary font-monospace">{{ $vendor->vendor_code }}</span>
                {!! $vendor->status_badge !!}
                <span class="badge bg-light text-dark border">{{ $vendor->vendor_type_label }}</span>
            </div>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('procurement.vendors.index') }}">{{ __('procurement.vendors') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $vendor->vendor_code }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            @if(Auth::user()->hasPermission('manage_vendors'))
                <a href="{{ route('procurement.vendors.edit', $vendor) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> {{ __('common.edit') }}
                </a>
                <form action="{{ route('procurement.vendors.toggle-status', $vendor) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $vendor->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                            onclick="return confirm('{{ __('procurement.confirm_toggle_status') }}')">
                        <i class="bi {{ $vendor->is_active ? 'bi-toggle-off' : 'bi-toggle-on' }} me-1"></i>
                        {{ $vendor->is_active ? __('procurement.deactivate_vendor') : __('procurement.activate_vendor') }}
                    </button>
                </form>
            @endif
            <a href="{{ route('procurement.vendors.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.expenses') }}</div>
                    <div class="h3 mb-0 fw-bold text-primary">{{ $metrics['total_count'] }}</div>
                    <small class="text-muted">{{ __('procurement.vendor_expenses_history') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.approved_budget_total') }}</div>
                    <div class="h3 mb-0 fw-bold text-success">{{ $metrics['approved_amount'] }}</div>
                    <small class="text-muted">{{ __('finance.status_approved') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.pending_expenses') }}</div>
                    <div class="h3 mb-0 fw-bold text-warning">{{ $metrics['pending_amount'] }}</div>
                    <small class="text-muted">{{ __('finance.status_pending') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Vendor Profile Details Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-1 text-primary"></i> {{ __('procurement.vendor_details') }}
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted small">{{ __('procurement.vendor_code') }}</dt>
                        <dd class="col-sm-7 fw-bold font-monospace">{{ $vendor->vendor_code }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.vendor_type') }}</dt>
                        <dd class="col-sm-7"><span class="badge bg-light text-dark border">{{ $vendor->vendor_type_label }}</span></dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.contact_person') }}</dt>
                        <dd class="col-sm-7 fw-medium">{{ $vendor->contact_person ?: '-' }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.phone') }}</dt>
                        <dd class="col-sm-7">
                            @if($vendor->phone)
                                <a href="tel:{{ $vendor->phone }}" class="text-decoration-none font-monospace">
                                    <i class="bi bi-telephone me-1 text-muted"></i>{{ $vendor->phone }}
                                </a>
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.email') }}</dt>
                        <dd class="col-sm-7">
                            @if($vendor->email)
                                <a href="mailto:{{ $vendor->email }}" class="text-decoration-none">
                                    <i class="bi bi-envelope me-1 text-muted"></i>{{ $vendor->email }}
                                </a>
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.tax_id') }}</dt>
                        <dd class="col-sm-7 font-monospace">{{ $vendor->tax_id ?: '-' }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.address') }}</dt>
                        <dd class="col-sm-7 small">{{ $vendor->address ?: '-' }}</dd>

                        <dt class="col-sm-5 text-muted small">{{ __('procurement.notes') }}</dt>
                        <dd class="col-sm-7 small text-muted fst-italic">{{ $vendor->notes ?: '-' }}</dd>

                        <hr class="my-2">

                        <dt class="col-sm-5 text-muted small">{{ __('common.created_by') }}</dt>
                        <dd class="col-sm-7 small">{{ $vendor->creator?->name ?? '-' }} ({{ $vendor->created_at->format('d M Y') }})</dd>

                        @if($vendor->updater)
                            <dt class="col-sm-5 text-muted small">{{ __('common.updated_by') }}</dt>
                            <dd class="col-sm-7 small">{{ $vendor->updater->name }} ({{ $vendor->updated_at->format('d M Y') }})</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <!-- Historical Expenses & Vouchers -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-receipt me-1 text-primary"></i> {{ __('procurement.vendor_expenses_history') }}
                    </h6>
                    <span class="badge bg-primary-subtle text-primary">{{ $expenses->total() }} {{ __('procurement.expenses') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('finance.expense_number') }}</th>
                                    <th>{{ __('finance.expense_date') }}</th>
                                    <th>{{ __('common.title') }}</th>
                                    <th>{{ __('events.event') }}</th>
                                    <th class="text-end">{{ __('common.amount') }}</th>
                                    <th class="text-center">{{ __('common.status') }}</th>
                                    <th class="text-end pe-3">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $exp)
                                    <tr>
                                        <td>
                                            <a href="{{ route('finance.expenses.show', $exp) }}" class="fw-bold font-monospace text-primary text-decoration-none">
                                                {{ $exp->expense_number }}
                                            </a>
                                        </td>
                                        <td>{{ $exp->expense_date?->format('d M Y') ?? '-' }}</td>
                                        <td>
                                            <div class="fw-semibold text-truncate" style="max-width: 180px;">{{ $exp->title_en }}</div>
                                            <small class="text-muted">{{ $exp->expenseAccount?->account_name_en }}</small>
                                        </td>
                                        <td>
                                            @if($exp->event)
                                                <span class="badge bg-light text-dark border text-truncate" style="max-width: 140px;">
                                                    {{ $exp->event->title_en }}
                                                </span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">৳{{ number_format((float) $exp->amount, 2) }}</td>
                                        <td class="text-center">
                                            @if($exp->status === 'approved')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('finance.status_approved') }}</span>
                                            @elseif($exp->status === 'pending_approval')
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('finance.status_pending') }}</span>
                                            @elseif($exp->status === 'rejected')
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('finance.status_rejected') }}</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border">{{ $exp->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('finance.expenses.show', $exp) }}" class="btn btn-outline-info btn-sm" title="{{ __('common.view') }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary opacity-50"></i>
                                            {{ __('procurement.no_expenses_recorded') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($expenses->hasPages())
                    <div class="card-footer bg-white border-top py-3">
                        {{ $expenses->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
