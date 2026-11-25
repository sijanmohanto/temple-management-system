<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('procurement.outstanding_bills_report_title') }}</title>

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
                <h3 class="fw-bold mb-1">{{ __('procurement.outstanding_bills_report_title') }}</h3>
                <div class="text-muted small">
                    {{ __('procurement.procurement_reports') }} &bull; {{ now()->format('d M Y, h:i A') }}
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-light text-dark border px-3 py-2 font-monospace">
                    {{ $summary['total_bills_count'] }} Bills &bull; Outstanding: {{ $summary['total_outstanding_amount_formatted'] }}
                </span>
            </div>
        </div>

        <!-- Metrics Bar -->
        <div class="row g-2 mb-4 text-center">
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.approved_bill_total') }}</small>
                <strong>{{ $summary['total_bills_count'] }}</strong> ({{ $summary['total_approved_amount_formatted'] }})
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.total_paid_amount') }}</small>
                <strong class="text-success">{{ $summary['total_paid_amount_formatted'] }}</strong>
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.total_outstanding_amount') }}</small>
                <strong class="text-danger">{{ $summary['total_outstanding_amount_formatted'] }}</strong>
            </div>
            <div class="col border rounded p-2 bg-light">
                <small class="text-muted d-block">{{ __('procurement.unpaid_bills') }} / Partial</small>
                <strong>{{ $summary['unpaid_bills_count'] }}</strong> / {{ $summary['partially_paid_bills_count'] }}
            </div>
            <div class="col border rounded p-2 bg-danger bg-opacity-10 border-danger">
                <small class="text-danger d-block fw-bold">{{ __('procurement.overdue_bills') }}</small>
                <strong class="text-danger">{{ $summary['overdue_bills_count'] }}</strong> ({{ $summary['overdue_amount_formatted'] }})
            </div>
        </div>

        <!-- Data Table (No Serial # Column) -->
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>{{ __('procurement.bill_ref_short') }}</th>
                    <th>{{ __('procurement.vendor_name_short') }}</th>
                    <th>{{ __('procurement.po_ref_short') }}</th>
                    <th>{{ __('procurement.bill_date_short') }}</th>
                    <th>{{ __('procurement.due_date_short') }}</th>
                    <th class="text-end">{{ __('procurement.bill_amount_short') }}</th>
                    <th class="text-end">{{ __('procurement.paid_amount_short') }}</th>
                    <th class="text-end">{{ __('procurement.outstanding_short') }}</th>
                    <th class="text-center">{{ __('procurement.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $today = now()->toDateString();
                @endphp
                @forelse($bills as $bill)
                    @php
                        $billTotal = (float) $bill->total_amount;
                        $postedPaid = (float) ($bill->authoritative_posted_paid ?? 0.00);
                        $outstanding = max(0.00, round($billTotal - $postedPaid, 2));
                        $isOverdue = ($outstanding > 0.00 && $bill->due_date && $bill->due_date->toDateString() < $today);
                        $status = 'Unpaid';
                        if ($outstanding <= 0.00) {
                            $status = 'Paid';
                        } elseif ($isOverdue) {
                            $status = 'Overdue';
                        } elseif ($postedPaid > 0.00) {
                            $status = 'Partial';
                        }
                    @endphp
                    <tr>
                        <td class="font-monospace fw-bold">
                            {{ $bill->bill_number }}
                            @if($bill->vendor_invoice_number)
                                <br><small class="text-muted">Inv: {{ $bill->vendor_invoice_number }}</small>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $bill->vendor?->name ?? '—' }}</strong>
                            @if($bill->vendor?->vendor_code)
                                <br><small class="text-muted font-monospace">{{ $bill->vendor->vendor_code }}</small>
                            @endif
                        </td>
                        <td class="font-monospace">{{ $bill->purchaseOrder?->order_number ?? '—' }}</td>
                        <td>{{ $bill->bill_date ? $bill->bill_date->format('d M Y') : '—' }}</td>
                        <td class="{{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                            {{ $bill->due_date ? $bill->due_date->format('d M Y') : '—' }}
                        </td>
                        <td class="text-end fw-bold">৳{{ number_format($billTotal, 2) }}</td>
                        <td class="text-end text-success fw-bold">৳{{ number_format($postedPaid, 2) }}</td>
                        <td class="text-end fw-bold {{ $outstanding > 0 ? 'text-danger' : 'text-muted' }}">
                            ৳{{ number_format($outstanding, 2) }}
                        </td>
                        <td class="text-center">
                            {{ $status }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No vendor bills match criteria.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="5" class="text-end">{{ __('common.total') }}:</td>
                    <td class="text-end">৳{{ number_format((float) $summary['total_approved_amount'], 2) }}</td>
                    <td class="text-end text-success">৳{{ number_format((float) $summary['total_paid_amount'], 2) }}</td>
                    <td class="text-end text-danger">৳{{ number_format((float) $summary['total_outstanding_amount'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
