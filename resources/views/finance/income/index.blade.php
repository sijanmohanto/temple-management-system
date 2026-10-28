@extends('layouts.app')

@section('title', __('finance.misc_income'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.misc_income') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.misc_income_subtitle') }}</p>
        </div>
        <div>
            @if(Auth::user()->hasPermission('record_income'))
                <a href="{{ route('finance.income.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('finance.new_misc_income') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Income Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="incomeTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('finance.income_number') }}</th>
                            <th>{{ __('finance.income_date') }}</th>
                            <th>{{ __('common.title') }}</th>
                            <th>{{ __('finance.income_category') }}</th>
                            <th>{{ __('finance.deposit_account') }}</th>
                            <th>{{ __('finance.payer_name') }}</th>
                            <th class="text-end">{{ __('common.amount') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomes as $inc)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $inc->income_number }}</td>
                                <td>{{ $inc->income_date->format('Y-m-d') }}</td>
                                <td class="fw-semibold">{{ $inc->title_en }}</td>
                                <td>{{ $inc->incomeAccount?->name_en }}</td>
                                <td>{{ $inc->depositAccount?->name_en }} ({{ ucfirst(str_replace('_', ' ', $inc->payment_method)) }})</td>
                                <td>{{ $inc->payer_name ?: '-' }}</td>
                                <td class="text-end fw-bold text-success">৳{{ number_format($inc->amount, 2) }}</td>
                                <td class="text-end">
                                    @if($inc->journalEntry)
                                        <a href="{{ route('finance.journals.show', $inc->journalEntry) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('finance.voucher_details') }}">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">{{ __('common.no_records_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($incomes->hasPages())
                <div class="mt-3">
                    {{ $incomes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
