@extends('layouts.app')

@section('title', __('finance.opening_balances'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.opening_balances') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.opening_balance_equity_note') }}</p>
        </div>
        <div>
            <a href="{{ route('finance.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Explanation Alert -->
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-shield-lock-fill fs-3 me-3 text-info"></i>
            <div>
                <h6 class="mb-1 fw-bold">{{ __('finance.controlled_opening_balance_setup') }}</h6>
                <div class="small">{{ __('finance.opening_balance_explainer') }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Entry Form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">{{ __('finance.setup_opening_balance') }}</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('finance.opening-balances.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label required">{{ __('finance.select_account') }}</label>
                            <select name="account_id" class="form-select select2-basic @error('account_id') is-invalid @enderror" required>
                                <option value="">-- {{ __('common.select') }} --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->code }} - {{ $acc->name_en }} ({{ ucfirst($acc->account_type) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('finance.normal_balance') }}</label>
                                <select name="balance_type" class="form-select @error('balance_type') is-invalid @enderror" required>
                                    <option value="debit" {{ old('balance_type') === 'debit' ? 'selected' : '' }}>{{ __('finance.debit') }} ({{ __('finance.asset') }} / {{ __('finance.expense') }})</option>
                                    <option value="credit" {{ old('balance_type') === 'credit' ? 'selected' : '' }}>{{ __('finance.credit') }} ({{ __('finance.liability') }} / {{ __('finance.equity') }} / {{ __('finance.income') }})</option>
                                </select>
                                @error('balance_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">{{ __('common.amount') }} (৳)</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="0.00" required>
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">{{ __('finance.entry_date') }}</label>
                            <input type="date" name="entry_date" class="form-control @error('entry_date') is-invalid @enderror" value="{{ old('entry_date', date('Y-01-01')) }}" required>
                            @error('entry_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('common.notes') }}</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="e.g. Verified physical cash count and bank statement as of fiscal year start...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i> {{ __('finance.setup_opening_balance') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recorded Opening Balances List -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">{{ __('finance.recorded_opening_entries') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('finance.journal_number') }}</th>
                                    <th>{{ __('finance.entry_date') }}</th>
                                    <th>{{ __('finance.account') }}</th>
                                    <th class="text-end">{{ __('common.amount') }}</th>
                                    <th class="text-center">{{ __('finance.status') }}</th>
                                    <th class="text-end">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($openingEntries as $entry)
                                    @php
                                        $targetLine = $entry->lines->firstWhere('account.code', '!=', '3020');
                                    @endphp
                                    <tr>
                                        <td class="fw-bold font-monospace">{{ $entry->journal_number }}</td>
                                        <td>{{ $entry->entry_date->format('Y-m-d') }}</td>
                                        <td>
                                            @if($targetLine)
                                                <span class="fw-semibold">{{ $targetLine->account?->name_en }}</span>
                                                <div class="text-muted small">{{ $targetLine->account?->code }} ({{ ucfirst($targetLine->entry_type) }})</div>
                                            @else
                                                {{ $entry->description }}
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            ৳{{ number_format($entry->total_debit, 2) }}
                                        </td>
                                        <td class="text-center">
                                            @if($entry->status === 'posted')
                                                <span class="badge bg-success">{{ __('finance.posted') }}</span>
                                            @elseif($entry->status === 'reversed')
                                                <span class="badge bg-danger">{{ __('finance.reversed') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('finance.journals.show', $entry) }}" class="btn btn-sm btn-outline-primary" title="{{ __('common.view') }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            {{ __('common.no_records_found') }}
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2-basic').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush
