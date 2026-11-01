<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('procurement.print_payment_voucher') }} — {{ $vendorPayment->payment_number }}</title>

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

        .voucher-card {
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

        .voucher-badge-title {
            background: #198754;
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
            color: #333333;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .voucher-card {
                border: 1.5px solid #222 !important;
                box-shadow: none !important;
                max-width: 100% !important;
                padding: 20px !important;
                margin: 0 !important;
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Top Action Toolbar -->
    <div class="container text-center mb-3 no-print" style="max-width: 860px;">
        <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded shadow-sm border">
            <div>
                <a href="{{ route('procurement.payments.show', $vendorPayment) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
                </a>
            </div>
            <div>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-primary">
                    <i class="bi bi-printer-fill me-1"></i> {{ __('common.print') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Printable Voucher Card -->
    <div class="voucher-card">
        @if(in_array($vendorPayment->status, ['cancelled', 'reversed']))
            <div class="watermark-cancelled">{{ $vendorPayment->status }}</div>
        @endif

        <!-- Temple Header -->
        <div class="text-center mb-3 pb-3 border-bottom">
            <h1 class="temple-title">শ্রী শ্রী রাধাকৃষ্ণ মন্দির</h1>
            <div class="temple-subtitle">SRI SRI RADHA KRISHNA TEMPLE</div>
            <div class="small text-muted">
                {{ __('procurement.budget_and_procurement') }} &bull; {{ __('procurement.vendor_payment_voucher') }}
            </div>
        </div>

        <!-- Voucher Title Banner -->
        <div class="text-center mb-4">
            <span class="voucher-badge-title">
                {{ __('procurement.vendor_payment_voucher') }}
            </span>
            <div class="small mt-1 text-muted">
                {{ __('procurement.status') }}: <strong class="text-uppercase">{{ $vendorPayment->status }}</strong>
            </div>
        </div>

        <!-- Voucher Metadata Grid -->
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="meta-box h-100">
                    <div class="small text-muted fw-bold text-uppercase mb-1">{{ __('procurement.vendor') }}</div>
                    <div class="fw-bold fs-6 text-dark">{{ $vendorPayment->vendor->name }}</div>
                    <div class="small text-muted font-monospace">{{ $vendorPayment->vendor->vendor_code }}</div>
                    @if($vendorPayment->vendor->contact_person)
                        <div class="small text-secondary">{{ __('procurement.contact_person') }}: {{ $vendorPayment->vendor->contact_person }}</div>
                    @endif
                    @if($vendorPayment->vendor->phone)
                        <div class="small text-secondary">{{ __('common.phone') }}: {{ $vendorPayment->vendor->phone }}</div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="meta-box h-100">
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="small text-muted fw-bold d-block">{{ __('procurement.payment_number') }}:</span>
                            <span class="fw-bold font-monospace text-success">{{ $vendorPayment->payment_number }}</span>
                        </div>
                        <div class="col-6">
                            <span class="small text-muted fw-bold d-block">{{ __('procurement.payment_date') }}:</span>
                            <span class="fw-semibold">{{ $vendorPayment->payment_date?->format('d M Y') }}</span>
                        </div>
                        <div class="col-6">
                            <span class="small text-muted fw-bold d-block">{{ __('procurement.payment_method') }}:</span>
                            <span class="fw-semibold">
                                {{ \App\Models\VendorPayment::supportedPaymentMethods()[$vendorPayment->payment_method] ?? ucfirst($vendorPayment->payment_method) }}
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="small text-muted fw-bold d-block">{{ __('procurement.reference_number') }}:</span>
                            <span class="font-monospace fw-semibold">{{ $vendorPayment->reference_number ?: '-' }}</span>
                        </div>
                        <div class="col-12 border-top pt-1 mt-1">
                            <span class="small text-muted fw-bold d-block">{{ __('procurement.disbursed_account') }}:</span>
                            <span class="small text-dark font-monospace">
                                {{ $vendorPayment->financialAccount?->account_code }} -
                                {{ app()->getLocale() === 'bn' ? ($vendorPayment->financialAccount?->account_name_bn ?: $vendorPayment->financialAccount?->account_name_en) : $vendorPayment->financialAccount?->account_name_en }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bill Settlement Allocations Table -->
        <table class="table table-bordered table-items mb-3">
            <thead>
                <tr>
                    <th style="width: 25%;">{{ __('procurement.bill_number') }}</th>
                    <th style="width: 25%;">{{ __('procurement.invoice_reference') }}</th>
                    <th style="width: 20%;">{{ __('procurement.bill_date') }}</th>
                    <th style="width: 30%;" class="text-end">{{ __('procurement.allocated_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vendorPayment->allocations as $alloc)
                    <tr>
                        <td class="font-monospace fw-bold text-dark">{{ $alloc->bill?->bill_number ?? '-' }}</td>
                        <td class="font-monospace">{{ $alloc->bill?->vendor_invoice_number ?? '-' }}</td>
                        <td>{{ $alloc->bill?->bill_date?->format('d M Y') ?? '-' }}</td>
                        <td class="text-end font-monospace fw-bold">৳{{ number_format((float) $alloc->allocated_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <th colspan="3" class="text-end fw-bold fs-6">{{ __('procurement.payment_amount') }}:</th>
                    <th class="text-end font-monospace fw-bold fs-5 text-success">৳{{ number_format((float) $vendorPayment->amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>

        <!-- Notes / Remarks -->
        @if($vendorPayment->notes)
            <div class="mb-4 p-2 bg-light rounded border">
                <small class="fw-bold text-muted d-block">{{ __('common.notes') }}:</small>
                <div class="small text-dark">{{ $vendorPayment->notes }}</div>
            </div>
        @endif

        <!-- Signatures Block -->
        <div class="row pt-5 mt-4 g-3">
            <div class="col-3">
                <div class="signature-box">
                    <div class="text-dark fw-bold">{{ $vendorPayment->creator?->name ?? 'Staff' }}</div>
                    <div class="text-muted small">{{ __('procurement.prepared_by') }}</div>
                </div>
            </div>
            <div class="col-3">
                <div class="signature-box">
                    <div class="text-dark fw-bold">{{ $vendorPayment->verifier?->name ?? 'Verified' }}</div>
                    <div class="text-muted small">{{ __('procurement.verified_by') }}</div>
                </div>
            </div>
            <div class="col-3">
                <div class="signature-box">
                    <div class="text-dark fw-bold">{{ $vendorPayment->approver?->name ?? 'Treasurer' }}</div>
                    <div class="text-muted small">{{ __('procurement.treasurer') }}</div>
                </div>
            </div>
            <div class="col-3">
                <div class="signature-box">
                    <div class="text-dark fw-bold">&nbsp;</div>
                    <div class="text-muted small">{{ __('procurement.receiver_signature') }}</div>
                </div>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="text-center mt-4 pt-3 border-top text-muted small">
            <em>{{ __('procurement.vendor_payment_voucher') }} &bull; {{ config('app.name', 'Temple Management System') }} &bull; {{ now()->format('d M Y, h:i A') }}</em>
        </div>
    </div>
</body>
</html>
