<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('procurement.purchase_requisitions_report_title') }}</title>

    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap-icons.min.css') }}">

    <style>
        body {
            background-color: #fff;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #212529;
            margin: 0;
            padding: 24px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .table-light { background-color: #f8f9fa !important; }
        }
        .header-box {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print / Close Controls -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-printer me-1"></i> {{ __('procurement.print_report') }}
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary rounded-pill px-3">
                {{ __('common.close') }}
            </button>
        </div>

        <!-- Report Header -->
        <div class="header-box d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1">{{ __('procurement.purchase_requisitions_report_title') }}</h3>
                <div class="text-muted small">
                    {{ __('procurement.procurement_reports') }} &bull; {{ now()->format('d M Y, h:i A') }}
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-light text-dark border px-3 py-2 font-monospace">
                    {{ $summary['total_count'] }} Records &bull; {{ $summary['total_amount_formatted'] }}
                </span>
            </div>
        </div>

        <!-- Metrics Bar -->
        <div class="row g-2 mb-4 text-center">
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.total_requisitions') }}</small>
                <strong>{{ $summary['total_count'] }}</strong> ({{ $summary['total_amount_formatted'] }})
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.draft_requisitions') }}</small>
                <strong>{{ $summary['draft_count'] }}</strong>
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.pending_approval_count') }}</small>
                <strong>{{ $summary['pending_count'] }}</strong>
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.approved_requisitions') }}</small>
                <strong>{{ $summary['approved_count'] }}</strong>
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.rejected_or_cancelled') }}</small>
                <strong>{{ $summary['rejected_or_cancelled_count'] }}</strong>
            </div>
        </div>

        <!-- Data Table (No Serial # Column) -->
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>{{ __('procurement.purpose_short') }}</th>
                    <th>{{ __('procurement.requested_by_short') }}</th>
                    <th>{{ __('procurement.request_date_short') }}</th>
                    <th>{{ __('procurement.required_date_short') }}</th>
                    <th class="text-end">{{ __('procurement.estimated_amount_short') }}</th>
                    <th class="text-center">{{ __('procurement.status') }}</th>
                    <th>{{ __('procurement.approved_by_short') }}</th>
                    <th>{{ __('procurement.approval_date_short') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $pr)
                    <tr>
                        <td>
                            <div class="font-monospace fw-bold text-primary">{{ $pr->request_number }}</div>
                            <strong>{{ $pr->title }}</strong>
                            @if($pr->justification)
                                <br><small class="text-muted">{{ $pr->justification }}</small>
                            @endif
                        </td>
                        <td>{{ $pr->requester?->name ?? '—' }}</td>
                        <td>{{ $pr->request_date ? $pr->request_date->format('d M Y') : '—' }}</td>
                        <td>{{ $pr->required_date ? $pr->required_date->format('d M Y') : '—' }}</td>
                        <td class="text-end fw-bold">৳{{ number_format((float) $pr->total_estimated_amount, 2) }}</td>
                        <td class="text-center">
                            {{ ucfirst($pr->status) }}
                        </td>
                        <td>{{ $pr->approver?->name ?? '—' }}</td>
                        <td>{{ $pr->approved_at ? $pr->approved_at->format('d M Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No purchase requisitions match criteria.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end">{{ __('common.total') }}:</td>
                    <td class="text-end">৳{{ number_format((float) $summary['total_amount'], 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
