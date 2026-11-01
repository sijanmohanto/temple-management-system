<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('procurement.print_bill') }} — {{ $vendorBill->bill_number }}</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap-icons.min.css') }}">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #212529;
            margin: 0;
            padding: 20px;
        }

        .bill-card {
            background: #ffffff;
            max-width: 860px;
            margin: 0 auto;
            border: 2px solid #333333;
            border-radius: 8px;
            padding: 35px 40px;
            position: relative;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }

        .temple-title {
            color: #7b1113;
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .temple-subtitle {
            font-size: 0.95rem;
            color: #555555;
            font-weight: 600;
        }

        .bill-badge-title {
            background: #7b1113;
            color: #ffffff;
            padding: 5px 22px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .meta-box {
            border: 1px dashed #777777;
            padding: 8px 14px;
            border-radius: 6px;
            background-color: #fdfdfd;
        }

        .watermark-cancelled {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 7.5rem;
            font-weight: 900;
            color: rgba(220, 53, 69, 0.22);
            border: 10px dashed rgba(220, 53, 69, 0.35);
            padding: 10px 40px;
            border-radius: 16px;
            text-transform: uppercase;
            pointer-events: none;
            letter-spacing: 12px;
            z-index: 100;
        }

        .table-items th {
            background-color: #f1f3f5 !important;
            border-bottom: 2px solid #495057;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-items td {
            font-size: 0.92rem;
            vertical-align: middle;
        }

        .signature-box {
            border-top: 1.5px solid #333333;
            padding-top: 6px;
            text-align: center;
            font-size: 0.82rem;
            font-weight: 600;
            color: #495057;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .bill-card {
                border: none;
                box-shadow: none;
                padding: 15px 20px;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Action toolbar (hidden during print) -->
<div class="container-fluid no-print mb-4" style="max-width: 860px;">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border">
        <div>
            <a href="{{ route('procurement.bills.show', $vendorBill) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-printer me-1"></i> {{ __('common.print') }}
            </button>
        </div>
    </div>
</div>

<div class="bill-card">
    @if($vendorBill->status === 'cancelled')
        <div class="watermark-cancelled">{{ __('procurement.status_cancelled') }}</div>
    @endif

    <!-- Temple Header -->
    <div class="text-center pb-3 border-bottom border-2 border-dark mb-4">
        <div class="temple-title">{{ config('app.name', 'Sri Sri Radha Krishna Temple') }}</div>
        <div class="temple-subtitle">Central Mandir Complex & Community Trust</div>
        <div class="small text-muted">Accounts & Procurement Department — Vendor Bill Voucher</div>
        <div class="mt-2">
            <span class="bill-badge-title">{{ __('procurement.bill_voucher') }}</span>
        </div>
    </div>

    <!-- Meta Information Row -->
    <div class="row g-3 mb-4">
        <!-- Left: Vendor Info -->
        <div class="col-6">
            <div class="meta-box h-100">
                <div class="small fw-bold text-uppercase text-secondary mb-1">{{ __('procurement.vendor_details') }}</div>
                <div class="fw-bold fs-6 text-dark">{{ $vendorBill->vendor->name }}</div>
                @if($vendorBill->vendor->contact_person)
                    <div class="small text-muted">{{ __('procurement.contact_person') }}: {{ $vendorBill->vendor->contact_person }}</div>
                @endif
                @if($vendorBill->vendor->phone)
                    <div class="small text-muted">{{ __('procurement.phone') }}: {{ $vendorBill->vendor->phone }}</div>
                @endif
                @if($vendorBill->vendor->address)
                    <div class="small text-muted">{{ $vendorBill->vendor->address }}</div>
                @endif
                @if($vendorBill->vendor->tax_id)
                    <div class="small text-muted">{{ __('procurement.tax_id') }}: {{ $vendorBill->vendor->tax_id }}</div>
                @endif
            </div>
        </div>

        <!-- Right: Bill Info -->
        <div class="col-6">
            <div class="meta-box h-100">
                <div class="d-flex justify-content-between mb-1">
                    <span class="small text-muted">{{ __('procurement.bill_number') }}:</span>
                    <span class="fw-bold font-monospace text-primary">{{ $vendorBill->bill_number }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="small text-muted">{{ __('procurement.invoice_reference') }}:</span>
                    <span class="fw-bold font-monospace">{{ $vendorBill->vendor_invoice_number }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="small text-muted">{{ __('procurement.bill_date') }}:</span>
                    <span class="fw-medium">{{ $vendorBill->bill_date?->format('d M Y') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="small text-muted">{{ __('procurement.due_date') }}:</span>
                    <span class="fw-medium">{{ $vendorBill->due_date ? $vendorBill->due_date->format('d M Y') : '-' }}</span>
                </div>
                @if($vendorBill->purchaseOrder)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">{{ __('procurement.purchase_orders') }}:</span>
                        <span class="fw-bold font-monospace text-info">{{ $vendorBill->purchaseOrder->order_number }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between">
                    <span class="small text-muted">{{ __('common.status') }}:</span>
                    <span class="fw-bold text-uppercase small">{{ ucfirst($vendorBill->status) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bill Items Table -->
    <table class="table table-bordered table-items mb-3">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 40%;">{{ __('procurement.line_item_description') }}</th>
                <th style="width: 25%;">{{ __('procurement.expense_category') }}</th>
                <th style="width: 10%;" class="text-end">{{ __('procurement.quantity') }}</th>
                <th style="width: 10%;" class="text-end">{{ __('procurement.rate') }}</th>
                <th style="width: 15%;" class="text-end">{{ __('procurement.item_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vendorBill->items as $idx => $item)
                <tr>
                    <td class="text-center text-muted font-monospace">{{ $idx + 1 }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $item->item_description }}</div>
                    </td>
                    <td>
                        @if($item->expenseAccount)
                            <small class="text-muted">{{ $item->expenseAccount->account_code }} - {{ $item->expenseAccount->name_en }}</small>
                        @else
                            <small class="text-muted">-</small>
                        @endif
                    </td>
                    <td class="text-end font-monospace">{{ number_format((float) $item->quantity, 2) }} {{ $item->unit ?: '' }}</td>
                    <td class="text-end font-monospace">৳{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="text-end font-monospace fw-bold">৳{{ number_format((float) $item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-end fw-bold">{{ __('procurement.subtotal') }}:</td>
                <td class="text-end font-monospace fw-bold">৳{{ number_format((float) $vendorBill->subtotal, 2) }}</td>
            </tr>
            @if($vendorBill->tax_amount > 0)
                <tr>
                    <td colspan="5" class="text-end text-muted">{{ __('procurement.tax_vat_amount') }}:</td>
                    <td class="text-end font-monospace text-muted">+৳{{ number_format((float) $vendorBill->tax_amount, 2) }}</td>
                </tr>
            @endif
            @if($vendorBill->discount_amount > 0)
                <tr>
                    <td colspan="5" class="text-end text-muted">{{ __('procurement.discount_amount') }}:</td>
                    <td class="text-end font-monospace text-muted">-৳{{ number_format((float) $vendorBill->discount_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="table-light">
                <td colspan="5" class="text-end fw-bold fs-6">{{ __('procurement.net_payable') }}:</td>
                <td class="text-end font-monospace fw-bold fs-6 text-dark">৳{{ number_format((float) $vendorBill->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Accounting Ledger Posting Info -->
    @if($vendorBill->journal_entry_id)
        <div class="meta-box mb-4 bg-light">
            <div class="row align-items-center">
                <div class="col-8">
                    <span class="small fw-bold text-success text-uppercase"><i class="bi bi-journal-check me-1"></i> {{ __('procurement.journal_entry') }}:</span>
                    <span class="font-monospace fw-bold ms-1">Entry #{{ $vendorBill->journal_entry_id }}</span>
                    <span class="small text-muted ms-2">(Posted to General Ledger: Accounts Payable GL 2010)</span>
                </div>
                <div class="col-4 text-end">
                    <span class="badge bg-success text-white">GL POSTED</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Notes if any -->
    @if($vendorBill->notes)
        <div class="meta-box mb-4">
            <div class="small fw-bold text-secondary text-uppercase mb-1">{{ __('procurement.notes') }}:</div>
            <div class="small text-muted">{{ $vendorBill->notes }}</div>
        </div>
    @endif

    <!-- Signatures -->
    <div class="row mt-5 pt-4">
        <div class="col-3">
            <div class="signature-box">
                <div>{{ $vendorBill->creator?->name ?? 'Staff' }}</div>
                <div class="text-muted fw-normal small">{{ __('common.created_by') }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="signature-box">
                <div>{{ $vendorBill->verifier?->name ?? 'Accountant' }}</div>
                <div class="text-muted fw-normal small">{{ __('procurement.verified_by') }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="signature-box">
                <div>{{ $vendorBill->approver?->name ?? 'Treasurer / President' }}</div>
                <div class="text-muted fw-normal small">{{ __('procurement.approved_by') }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="signature-box">
                <div>{{ $vendorBill->vendor->name }}</div>
                <div class="text-muted fw-normal small">{{ __('procurement.vendor_signature') }}</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
