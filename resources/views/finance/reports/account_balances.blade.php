@extends('layouts.app')

@section('title', __('finance.account_balances'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.account_balances') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.live_balances_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> {{ __('finance.print_report') }}
            </button>
        </div>
    </div>

    <!-- Grouped Cards -->
    <div class="row g-4">
        @foreach($grouped as $type => $group)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">{{ $group['type_title'] }}</h5>
                        <span class="badge bg-light text-dark border fs-6">
                            {{ __('common.total') }}: ৳{{ number_format($group['total'], 2) }}
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('finance.account_code') }}</th>
                                        <th>{{ __('finance.account_name') }}</th>
                                        <th>{{ __('finance.normal') }}</th>
                                        <th class="text-end">{{ __('finance.current_balance') }} (৳)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($group['accounts'] as $item)
                                        @php $acc = $item['account']; @endphp
                                        <tr>
                                            <td class="fw-bold font-monospace">{{ $acc->code }}</td>
                                            <td>
                                                <a href="{{ route('finance.accounts.show', $acc) }}" class="text-decoration-none fw-semibold">
                                                    {{ $acc->name_en }}
                                                </a>
                                                @if($acc->name_bn)
                                                    <div class="text-muted small">{{ $acc->name_bn }}</div>
                                                @endif
                                            </td>
                                            <td>{{ ucfirst($acc->normal_balance) }}</td>
                                            <td class="text-end fw-bold {{ $item['balance'] < 0 ? 'text-danger' : 'text-dark' }}">
                                                ৳{{ number_format($item['balance'], 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-3 text-muted">{{ __('common.no_records_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
