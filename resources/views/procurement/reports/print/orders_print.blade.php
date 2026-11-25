<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('procurement.purchase_orders_report_title') }}</title>

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
                <h3 class="fw-bold mb-1">{{ __('procurement.purchase_orders_report_title') }}</h3>
                <div class="text-muted small">
                    {{ __('procurement.procurement_reports') }} &bull; {{ now()->format('d M Y, h:i A') }}
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-light text-dark border px-3 py-2 font-monospace">
                    {{ $summary['total_count'] }} Records &bull; {{ $summary['total_historical_amount_formatted'] }}
                </span>
            </div>
        </div>

        <!-- Metrics Bar -->
        <div class="row g-2 mb-4 text-center">
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.total_orders') }}</small>
                <strong>{{ $summary['total_count'] }}</strong> ({{ $summary['total_historical_amount_formatted'] }})
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.active_committed_procurement') }}</small>
                <strong class="text-primary">{{ $summary['active_committed_count'] }}</strong> ({{ $summary['active_committed_amount_formatted'] }})
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.completed_orders') }}</small>
                <strong class="text-success">{{ $summary['completed_count'] }}</strong> ({{ $summary['completed_amount_formatted'] }})
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.draft_orders') }}</small>
                <strong>{{ $summary['draft_count'] }}</strong>
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.cancelled_orders') }}</small>
                <strong class="text-danger">{{ $summary['cancelled_count'] }}</strong> ({{ $summary['cancelled_amount_formatted'] }})
            </div>
        </div>

        <!-- Data Table (No Serial # Column) -->
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>{{ __('procurement.po_ref_short') }}</th>
                    <th>{{ __('procurement.vendor_name_short') }}</th>
                    <th>{{ __('procurement.req_ref_short') }}</th>
                    <th>{{ __('procurement.request_date_short') }}</th>
                    <th>{{ __('procurement.delivery_date_short') }}</th>
                    <th class="text-end">{{ __('procurement.order_amount_short') }}</th>
                    <th class="text-center">{{ __('procurement.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $po)
                    <tr>
                        <td class="font-monospace fw-bold">{{ $po->order_number }}</td>
                        <td>
                            <strong>{{ $po->vendor?->name ?? '—' }}</strong>
                            @if($po->vendor?->vendor_code)
                                <br><small class="text-muted font-monospace">{{ $po->vendor->vendor_code }}</small>
                            @endif
                        </td>
                        <td class="font-monospace">{{ $po->purchaseRequest?->request_number ?? '—' }}</td>
                        <td>{{ $po->order_date ? $po->order_date->format('d M Y') : '—' }}</td>
                        <td>{{ $po->expected_delivery_date ? $po->expected_delivery_date->format('d M Y') : '—' }}</td>
                        <td class="text-end fw-bold">৳{{ number_format((float) $po->total_amount, 2) }}</td>
                        <td class="text-center">
                            {{ ucfirst($po->status) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No purchase orders match criteria.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="5" class="text-end">{{ __('common.total') }}:</td>
                    <td class="text-end">৳{{ number_format((float) $summary['total_historical_amount'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
