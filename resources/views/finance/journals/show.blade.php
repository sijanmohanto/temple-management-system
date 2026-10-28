@extends('layouts.app')

@section('title', $journal->journal_number . ' — ' . __('finance.voucher_details'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ $journal->journal_number }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.voucher_details') }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($journal->status === 'posted' && Auth::user()->hasPermission('reverse_transactions'))
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reverseJournalModal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('finance.reverse_entry') }}
                </button>
            @endif
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> {{ __('finance.print_report') }}
            </button>
            <a href="{{ route('finance.journals.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <!-- Reversal Status Banner -->
    @if($journal->status === 'reversed')
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-octagon-fill fs-3 me-3 text-danger"></i>
                <div>
                    <h6 class="mb-1 fw-bold">{{ __('finance.reversed') }}</h6>
                    <div class="small">
                        {{ $journal->reversal_reason ?: __('finance.reversal_warning') }}
                    </div>
                </div>
            </div>
            @if($journal->reversedByJournal)
                <a href="{{ route('finance.journals.show', $journal->reversedByJournal) }}" class="btn btn-danger btn-sm">
                    {{ __('common.view') }} {{ $journal->reversedByJournal->journal_number }}
                </a>
            @endif
        </div>
    @elseif($journal->reversed_journal_id && $journal->reversedJournal)
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-3 me-3 text-info"></i>
                <div>
                    <h6 class="mb-1 fw-bold">{{ __('finance.reversal_voucher') }}</h6>
                    <div class="small">{{ __('finance.reversal_voucher_notice', ['number' => $journal->reversedJournal->journal_number]) }}</div>
                </div>
            </div>
            <a href="{{ route('finance.journals.show', $journal->reversedJournal) }}" class="btn btn-info btn-sm text-white">
                {{ __('finance.view_original_voucher', ['number' => $journal->reversedJournal->journal_number]) }}
            </a>
        </div>
    @endif

    <!-- Voucher Summary Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('finance.entry_date') }}</span>
                    <div class="fs-6 fw-bold mt-1">{{ $journal->entry_date->format('d M Y') }}</div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('finance.source_type') }}</span>
                    <div class="mt-1">
                        <span class="badge bg-light text-dark border">
                            {{ ucfirst(str_replace('_', ' ', $journal->source_type)) }}
                        </span>
                        @if($journal->source_id)
                            <span class="text-muted small">#{{ $journal->source_id }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('finance.status') }}</span>
                    <div class="mt-1">
                        @if($journal->status === 'posted')
                            <span class="badge bg-success">{{ __('finance.posted') }}</span>
                        @elseif($journal->status === 'reversed')
                            <span class="badge bg-danger">{{ __('finance.reversed') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($journal->status) }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('finance.posted_by') }}</span>
                    <div class="fs-6 fw-semibold mt-1">
                        {{ $journal->poster?->name ?? ($journal->creator?->name ?? 'System') }}
                    </div>
                    <div class="text-muted small">{{ $journal->posted_at ? $journal->posted_at->format('d M Y H:i') : '' }}</div>
                </div>
                <div class="col-12 border-top pt-3">
                    <span class="text-muted small fw-bold text-uppercase">{{ __('common.description') }}</span>
                    <div class="fs-6 mt-1">{{ $journal->description }}</div>
                    @if($journal->event)
                        <div class="text-primary small mt-1">
                            <i class="bi bi-calendar-event me-1"></i> {{ $journal->event->title_en }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Balanced Double-Entry Lines Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">{{ __('finance.double_entry_lines') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('finance.account_code') }}</th>
                            <th>{{ __('finance.account_title') }}</th>
                            <th>{{ __('finance.line_description') }}</th>
                            <th>{{ __('finance.event_tag') }}</th>
                            <th class="text-end">{{ __('finance.debit') }} (৳)</th>
                            <th class="text-end">{{ __('finance.credit') }} (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($journal->lines as $line)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $line->account?->code }}</td>
                                <td>
                                    <a href="{{ route('finance.accounts.show', $line->account) }}" class="text-decoration-none fw-semibold">
                                        {{ $line->account?->name_en }}
                                    </a>
                                    @if($line->account?->name_bn)
                                        <div class="text-muted small">{{ $line->account?->name_bn }}</div>
                                    @endif
                                </td>
                                <td>{{ $line->description ?: '-' }}</td>
                                <td>{{ $line->event?->title_en ?: '-' }}</td>
                                <td class="text-end fw-bold {{ $line->entry_type === 'debit' ? 'text-success' : 'text-muted' }}">
                                    {{ $line->entry_type === 'debit' ? number_format($line->amount, 2) : '-' }}
                                </td>
                                <td class="text-end fw-bold {{ $line->entry_type === 'credit' ? 'text-primary' : 'text-muted' }}">
                                    {{ $line->entry_type === 'credit' ? number_format($line->amount, 2) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold fs-6">
                        <tr>
                            <td colspan="4" class="text-end">{{ __('common.total') }}:</td>
                            <td class="text-end text-success">৳{{ number_format($journal->total_debit, 2) }}</td>
                            <td class="text-end text-primary">৳{{ number_format($journal->total_credit, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Reversal Modal -->
@if($journal->status === 'posted' && Auth::user()->hasPermission('reverse_transactions'))
<div class="modal fade" id="reverseJournalModal" tabindex="-1" aria-labelledby="reverseJournalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('finance.journals.reverse', $journal) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="reverseJournalModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ __('finance.reverse_entry') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        {{ __('finance.reversal_warning') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label required fw-bold">{{ __('finance.reversal_reason') }}</label>
                        <textarea name="reason" class="form-control" rows="3" required minlength="5" maxlength="500" placeholder="{{ __('finance.reversal_reason_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('finance.reverse_entry') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
