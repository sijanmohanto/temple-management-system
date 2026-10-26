@extends('layouts.app')

@section('title', __('donations.committee_contributions_report'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('collections.reports.index') }}" class="btn btn-sm btn-outline-secondary rounded-circle">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0 text-dark">{{ __('donations.committee_contributions_report') }}</h1>
            </div>
            <p class="text-muted small mb-0">{{ __('donations.committee_contributions') }} &bull; Calendar Year {{ $year }}</p>
        </div>

        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                <i class="bi bi-printer me-1"></i> {{ __('common.print') ?? 'Print Report' }}
            </button>
            <a href="{{ route('collections.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('donations.dashboard') }}
            </a>
        </div>
    </div>

    <!-- Year Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('collections.reports.committee-contributions') }}" class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label class="form-label small fw-semibold text-muted">{{ __('donations.payment_year') }}</label>
                    <select name="year" class="form-select select2">
                        @for($y = (int) date('Y') + 1; $y >= (int) date('Y') - 4; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">
                        <i class="bi bi-funnel me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('collections.reports.committee-contributions') }}" class="btn btn-outline-secondary rounded-pill" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Metrics -->
    @php
        $sumExpected = collect($months)->sum('expected');
        $sumPaid = collect($months)->sum('paid');
        $sumOutstanding = collect($months)->sum('outstanding');
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4">
                    <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.expected_amount') }} ({{ $year }})</span>
                    <h3 class="fw-bold text-dark mb-0">৳{{ number_format($sumExpected, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4">
                    <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.paid_amount') }} ({{ $year }})</span>
                    <h3 class="fw-bold text-success mb-0">৳{{ number_format($sumPaid, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4">
                    <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.outstanding_amount') }} ({{ $year }})</span>
                    <h3 class="fw-bold {{ $sumOutstanding > 0 ? 'text-danger' : 'text-muted' }} mb-0">৳{{ number_format($sumOutstanding, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- 12-Month Summary Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="committeeMonthlyTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('donations.contribution_month') }}</th>
                            <th>{{ __('people.person') ?? 'Members' }}</th>
                            <th>{{ __('donations.expected_amount') }}</th>
                            <th>{{ __('donations.paid_amount') }}</th>
                            <th>{{ __('donations.outstanding_amount') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end pe-3 d-print-none">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($months as $m)
                            @php
                                $percent = $m['expected'] > 0 ? min(100, round(($m['paid'] / $m['expected']) * 100)) : 0;
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold text-dark">
                                    {{ $m['month_name'] }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $m['count'] }} Members</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">৳{{ number_format($m['expected'], 2) }}</div>
                                </td>
                                <td>
                                    <div class="text-success fw-semibold">৳{{ number_format($m['paid'], 2) }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold {{ $m['outstanding'] > 0 ? 'text-danger' : 'text-muted' }}">
                                        ৳{{ number_format($m['outstanding'], 2) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px; width: 80px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="small text-muted">{{ $percent }}%</span>
                                    </div>
                                </td>
                                <td class="text-end pe-3 d-print-none">
                                    <a href="{{ route('contributions.index', ['month' => substr($m['month_date'], 0, 7)]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('common.view') }}">
                                        <i class="bi bi-eye me-1"></i> {{ __('common.view') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="ps-3" colspan="2">{{ __('common.total') ?? 'Year Total' }}</td>
                            <td>৳{{ number_format($sumExpected, 2) }}</td>
                            <td class="text-success">৳{{ number_format($sumPaid, 2) }}</td>
                            <td class="text-danger">৳{{ number_format($sumOutstanding, 2) }}</td>
                            <td colspan="2" class="d-print-none"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        TempleUI.initDataTable('#committeeMonthlyTable', {
            pageLength: 12,
            columns: [
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: false }
            ]
        });
    });
</script>
@endpush
