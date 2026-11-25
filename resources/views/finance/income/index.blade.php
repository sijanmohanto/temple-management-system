@extends('layouts.app')

@section('title', __('finance.misc_income'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('finance.misc_income') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.misc_income_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('finance.finance_dashboard') }}
            </a>
            @if(Auth::user()->hasPermission('record_income'))
                <a href="{{ route('finance.income.create') }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('finance.new_misc_income') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Income Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="incomesDataTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('donations.payer_short') }}</th>
                            <th>{{ __('common.description') }}</th>
                            <th>{{ __('finance.income_category') }}</th>
                            <th>{{ __('finance.deposit_account_short') }}</th>
                            <th>{{ __('finance.entry_date_short') }}</th>
                            <th class="text-end">{{ __('common.amount_short') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incomes as $inc)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-light text-primary d-flex align-items-center justify-content-center border flex-shrink-0 shadow-sm" style="width: 38px; height: 38px; min-width: 38px;">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $inc->payer_name ?: __('common.unspecified') }}</div>
                                            @if($inc->reference_number)
                                                <small class="text-muted"><i class="bi bi-receipt me-1"></i>{{ $inc->reference_number }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="d-none">{{ $inc->income_number }}</span>
                                </td>
                                <td>
                                    @if($inc->title_bn || $inc->title_en)
                                        <div class="fw-semibold text-dark">{{ app()->getLocale() === 'bn' && $inc->title_bn ? $inc->title_bn : ($inc->title_en ?: $inc->title_bn) }}</div>
                                    @endif
                                    @if($inc->event)
                                        <div class="mt-1">
                                            <span class="badge bg-light text-secondary border font-xs">
                                                <i class="bi bi-calendar-event me-1"></i>{{ $inc->event->title_en }}
                                            </span>
                                        </div>
                                    @endif
                                    @if($inc->notes || $inc->description)
                                        <div class="small text-muted text-truncate" style="max-width: 220px;" title="{{ $inc->notes ?: $inc->description }}">
                                            {{ $inc->notes ?: $inc->description }}
                                        </div>
                                    @endif
                                    @if(!$inc->title_bn && !$inc->title_en && !$inc->event && !$inc->notes && !$inc->description)
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                        {{ $inc->incomeAccount?->name_en }}
                                    </span>
                                    @if($inc->incomeAccount?->name_bn)
                                        <div class="small text-muted mt-1">{{ $inc->incomeAccount->name_bn }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $inc->depositAccount?->name_en }}</div>
                                    <div class="mt-1">
                                        @include('partials.payment-method-badge', ['method' => $inc->payment_method])
                                    </div>
                                </td>
                                <td>
                                    <div class="text-nowrap text-secondary">
                                        <i class="bi bi-calendar3 me-1 text-muted"></i>{{ $inc->income_date->format('d M Y') }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-success text-nowrap fs-6">৳{{ number_format((float) $inc->amount, 2) }}</div>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1 justify-content-end">
                                        @if($inc->journalEntry)
                                            <a href="{{ route('finance.journals.show', $inc->journalEntry) }}" class="table-action-btn table-action-btn-view" title="{{ __('finance.voucher_details') }}">
                                                <i class="bi bi-journal-text"></i>
                                            </a>
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    TempleUI.initDataTable('#incomesDataTable', {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[4, 'desc']],
        columns: [
            { orderable: true, className: 'ps-3' },
            { orderable: true },
            { orderable: true },
            { orderable: true },
            { orderable: true },
            { orderable: true, className: 'text-end' },
            { orderable: false, className: 'text-end pe-3' }
        ]
    });
});
</script>
@endpush
