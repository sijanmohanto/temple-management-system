@extends('layouts.app')

@section('title', __('finance.event_financial_summary'))

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('finance.event_financial_summary') }}</h1>
            <p class="text-muted small mb-0">{{ __('finance.event_summary_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> {{ __('finance.print_report') }}
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('finance.reports.event-summary') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small fw-bold">{{ __('finance.select_event') }}</label>
                    <select name="event_id" class="form-select select2-basic">
                        <option value="">-- {{ __('finance.all_events') }} --</option>
                        @foreach($events as $ev)
                            <option value="{{ $ev->id }}" {{ $eventId == $ev->id ? 'selected' : '' }}>
                                {{ $ev->title_en }} ({{ $ev->category?->name_en }}) — {{ $ev->start_date->format('M Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">{{ __('common.filter') }}</button>
                    <a href="{{ route('finance.reports.event-summary') }}" class="btn btn-outline-secondary">{{ __('common.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summaries Cards / Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="eventSummaryReportTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('events.event') }}</th>
                            <th>{{ __('common.category') }}</th>
                            <th>{{ __('common.date') }}</th>
                            <th class="text-end">{{ __('finance.budget') }} (৳)</th>
                            <th class="text-end text-success">{{ __('finance.total_collections') }} (৳)</th>
                            <th class="text-end text-danger">{{ __('finance.total_expenses') }} (৳)</th>
                            <th class="text-end">{{ __('finance.net_surplus_deficit') }} (৳)</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summaries as $item)
                            @php $ev = $item['event']; @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $ev->title_en }}</div>
                                    @if($ev->title_bn)
                                        <div class="text-muted small">{{ $ev->title_bn }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $ev->category?->name_en ?? '-' }}
                                    </span>
                                </td>
                                <td>{{ $ev->start_date->format('d M Y') }}</td>
                                <td class="text-end text-muted">
                                    {{ $item['budget'] > 0 ? '৳' . number_format($item['budget'], 2) : '-' }}
                                </td>
                                <td class="text-end fw-bold text-success">
                                    ৳{{ number_format($item['total_income'], 2) }}
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    ৳{{ number_format($item['total_expense'], 2) }}
                                </td>
                                <td class="text-end fw-bold {{ $item['net_surplus'] >= 0 ? 'text-primary' : 'text-danger' }}">
                                    ৳{{ number_format($item['net_surplus'], 2) }}
                                    <div style="font-size: 0.75rem;">
                                        ({{ $item['net_surplus'] >= 0 ? __('finance.surplus') : __('finance.deficit') }})
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('finance.reports.income-expense', ['event_id' => $ev->id]) }}" class="btn btn-sm btn-outline-primary" title="Detailed Statement">
                                        <i class="bi bi-file-earmark-spreadsheet"></i>
                                    </a>
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

    @if(count($summaries) > 0)
        TempleUI.initDataTable('#eventSummaryReportTable', {
            pageLength: 25,
            order: []
        });
    @endif
});
</script>
@endpush
