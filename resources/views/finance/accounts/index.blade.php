@extends('layouts.app')

@section('title', __('finance.chart_of_accounts'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.chart_of_accounts') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.all_systems_operational') }}</p>
        </div>
        <div>
            @if(Auth::user()->hasPermission('manage_accounts'))
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                    <i class="bi bi-plus-lg me-1"></i> {{ __('finance.add_account') }}
                </button>
            @endif
        </div>
    </div>

    <!-- Group Totals -->
    <div class="row g-3 mb-4">
        @foreach($grouped as $type => $group)
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="text-muted small text-uppercase fw-bold">{{ $group['type_title'] }}</div>
                        <div class="h5 mb-0 fw-bold mt-1 {{ $group['total'] < 0 ? 'text-danger' : 'text-dark' }}">
                            ৳{{ number_format($group['total'], 2) }}
                        </div>
                        <div class="text-muted small" style="font-size: 0.75rem;">
                            {{ count($group['accounts']) }} {{ __('finance.chart_of_accounts') }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Accounts Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="accountsTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('finance.account_code') }}</th>
                            <th>{{ __('finance.account_name') }}</th>
                            <th>{{ __('finance.account_type') }}</th>
                            <th>{{ __('finance.normal_balance') }}</th>
                            <th>{{ __('finance.parent_account') }}</th>
                            <th class="text-end">{{ __('finance.current_balance') }}</th>
                            <th class="text-center">{{ __('finance.status') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grouped as $type => $group)
                            @foreach($group['accounts'] as $item)
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
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-dark border">
                                            {{ ucfirst($acc->account_type) }}
                                        </span>
                                    </td>
                                    <td>{{ ucfirst($acc->normal_balance) }}</td>
                                    <td>{{ $acc->parent?->name_en ?? '-' }}</td>
                                    <td class="text-end fw-bold {{ $item['balance'] < 0 ? 'text-danger' : 'text-dark' }}">
                                        ৳{{ number_format($item['balance'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        @if($acc->is_active)
                                            <span class="badge bg-success">{{ __('common.status_active') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('common.status_inactive') }}</span>
                                        @endif
                                        @if($acc->is_system)
                                            <span class="badge bg-info text-dark" title="{{ __('finance.is_system') }}">System</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('finance.accounts.show', $acc) }}" class="btn btn-sm btn-outline-primary" title="{{ __('common.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Account Modal -->
@if(Auth::user()->hasPermission('manage_accounts'))
<div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.accounts.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAccountModalLabel">{{ __('finance.add_account') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">{{ __('finance.account_code') }}</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. 5090" required maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">{{ __('finance.account_name') }} (English)</label>
                        <input type="text" name="name_en" class="form-control" placeholder="e.g. Security & Surveillance" required maxlength="120">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('finance.account_name') }} (বাংলা)</label>
                        <input type="text" name="name_bn" class="form-control" placeholder="যেমন: নিরাপত্তা ও সিসিটিভি" maxlength="120">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('finance.account_type') }}</label>
                            <select name="account_type" class="form-select" required>
                                <option value="asset">{{ __('finance.asset') }}</option>
                                <option value="liability">{{ __('finance.liability') }}</option>
                                <option value="equity">{{ __('finance.equity') }}</option>
                                <option value="income">{{ __('finance.income') }}</option>
                                <option value="expense" selected>{{ __('finance.expense') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('finance.normal_balance') }}</label>
                            <select name="normal_balance" class="form-select" required>
                                <option value="debit" selected>{{ __('finance.debit') }}</option>
                                <option value="credit">{{ __('finance.credit') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('finance.parent_account') }}</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- None --</option>
                            @foreach($parentAccounts as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->code }} - {{ $parent->name_en }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.description') }}</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    TempleUI.initDataTable('#accountsTable', {
        pageLength: 25,
        order: [[0, 'asc']]
    });
});
</script>
@endpush
