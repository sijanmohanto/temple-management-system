<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('procurement.print_order') }} — {{ $purchaseOrder->order_number }}</title>

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

        .po-card {
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

        .po-badge-title {
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
            user-select: none;
        }

        .signature-line {
            border-top: 1.5px solid #222222;
            width: 180px;
            margin-top: 55px;
            padding-top: 6px;
            text-align: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
                margin: 0;
            }

            .po-card {
                border: 2px solid #000000;
                box-shadow: none;
                margin: 0;
                max-width: 100%;
                padding: 25px 30px;
            }

            .d-print-none {
                display: none !important;
            }

            .po-badge-title {
                background: #000000 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .watermark-cancelled {
                color: rgba(220, 53, 69, 0.25) !important;
                border-color: rgba(220, 53, 69, 0.4) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Bar for Web View -->
    <div class="d-print-none text-center mb-4">
        <div class="btn-group shadow-sm">
            <button onclick="window.print()" class="btn btn-primary px-4 py-2 fw-semibold" style="background-color: #7b1113; border-color: #7b1113;">
                <i class="bi bi-printer me-2"></i> {{ __('procurement.print_order') }}
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary px-3 py-2">
                <i class="bi bi-x-circle me-1"></i> {{ __('common.cancel') }}
            </button>
        </div>
    </div>

    <div class="po-card">
        @if($purchaseOrder->status === 'cancelled')
            <div class="watermark-cancelled">
                {{ __('procurement.status_cancelled') }}
            </div>
        @endif

        <!-- Header -->
        <div class="row align-items-center mb-4 pb-3 border-bottom border-2">
            <div class="col-7">
                <div class="temple-title">শ্রী শ্রী জগন্নাথ দেবের মন্দির</div>
                <div class="temple-subtitle">Sri Sri Jagannath Temple Management System</div>
                <div class="text-muted small mt-1">
                    <i class="bi bi-geo-alt me-1"></i> Temple Complex, Joydebpur, Gazipur, Bangladesh
                </div>
            </div>
            <div class="col-5 text-end">
                <div class="po-badge-title mb-2">
                    {{ __('procurement.purchase_orders') }}
                </div>
                <div class="fw-bold font-monospace fs-5 text-dark">{{ $purchaseOrder->order_number }}</div>
                <div class="text-muted small">
                    {{ __('procurement.order_date') }}: <strong>{{ $purchaseOrder->order_date?->format('d M Y') }}</strong>
                </div>
            </div>
        </div>

        <!-- Meta info & Vendor Details -->
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="meta-box h-100">
                    <div class="text-muted small fw-bold text-uppercase border-bottom pb-1 mb-2">
                        <i class="bi bi-building me-1"></i> {{ __('procurement.vendor_details') }}
                    </div>
                    <div class="fw-bold fs-6 text-dark">{{ $purchaseOrder->vendor->name }}</div>
                    <div class="small text-muted font-monospace">{{ $purchaseOrder->vendor->vendor_code }}</div>
                    @if($purchaseOrder->vendor->contact_person)
                        <div class="small mt-1"><strong>{{ __('procurement.contact_person') }}:</strong> {{ $purchaseOrder->vendor->contact_person }}</div>
                    @endif
                    @if($purchaseOrder->vendor->phone)
                        <div class="small"><strong>{{ __('procurement.phone') }}:</strong> {{ $purchaseOrder->vendor->phone }}</div>
                    @endif
                    @if($purchaseOrder->vendor->address)
                        <div class="small mt-1 text-muted">{{ $purchaseOrder->vendor->address }}</div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="meta-box h-100">
                    <div class="text-muted small fw-bold text-uppercase border-bottom pb-1 mb-2">
                        <i class="bi bi-info-circle me-1"></i> {{ __('procurement.order_details') }}
                    </div>
                    <div class="small">
                        <strong>{{ __('procurement.status') }}:</strong>
                        <span class="text-uppercase fw-bold">{{ $purchaseOrder->status }}</span>
                    </div>
                    @if($purchaseOrder->expected_delivery_date)
                        <div class="small mt-1">
                            <strong>{{ __('procurement.expected_delivery_date') }}:</strong>
                            {{ $purchaseOrder->expected_delivery_date->format('d M Y') }}
                        </div>
                    @endif
                    <div class="small mt-1">
                        <strong>{{ __('procurement.scope') }}:</strong>
                        {{ $purchaseOrder->event ? $purchaseOrder->event->title_en : __('procurement.type_general_fund') }}
                    </div>
                    @if($purchaseOrder->purchaseRequest)
                        <div class="small mt-1">
                            <strong>{{ __('procurement.source_request') }}:</strong>
                            <span class="font-monospace">{{ $purchaseOrder->purchaseRequest->request_number }}</span>
                        </div>
                    @endif
                    @if($purchaseOrder->delivery_address)
                        <div class="small mt-1 text-muted">
                            <strong>{{ __('procurement.delivery_address') }}:</strong> {{ $purchaseOrder->delivery_address }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead style="background-color: #f1f3f5;">
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 45%;">{{ __('procurement.item_description') }}</th>
                        <th style="width: 20%;">{{ __('procurement.select_expense_account') }}</th>
                        <th style="width: 10%;" class="text-center">{{ __('procurement.quantity') }}</th>
                        <th style="width: 10%;" class="text-end">{{ __('procurement.unit_price') }}</th>
                        <th style="width: 10%;" class="text-end">{{ __('procurement.total_price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $index => $item)
                        <tr>
                            <td class="text-center small">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $item->description }}</div>
                                @if($item->notes)
                                    <small class="text-muted d-block">{{ $item->notes }}</small>
                                @endif
                            </td>
                            <td class="small">
                                <span class="font-monospace text-muted">{{ $item->expenseAccount?->account_code }}</span>
                                <div>{{ $item->expenseAccount?->account_name_en }}</div>
                            </td>
                            <td class="text-center font-monospace">
                                {{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} {{ $item->unit }}
                            </td>
                            <td class="text-end font-monospace">
                                ৳{{ number_format((float) $item->unit_price, 2) }}
                            </td>
                            <td class="text-end fw-bold font-monospace">
                                ৳{{ number_format((float) $item->total_price, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">{{ __('procurement.subtotal') }}:</th>
                        <th class="text-end font-monospace">৳{{ number_format((float) $purchaseOrder->subtotal, 2) }}</th>
                    </tr>
                    @if((float) $purchaseOrder->discount_amount > 0)
                        <tr>
                            <th colspan="5" class="text-end text-danger">{{ __('procurement.discount_amount') }}:</th>
                            <th class="text-end font-monospace text-danger">-৳{{ number_format((float) $purchaseOrder->discount_amount, 2) }}</th>
                        </tr>
                    @endif
                    <tr style="background-color: #f1f3f5;">
                        <th colspan="5" class="text-end fs-6 fw-bold">{{ __('procurement.total_order_amount') }}:</th>
                        <th class="text-end fs-6 fw-bold font-monospace">৳{{ number_format((float) $purchaseOrder->total_amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($purchaseOrder->terms_conditions)
            <div class="mb-4">
                <div class="small fw-bold text-uppercase text-muted border-bottom pb-1 mb-1">
                    {{ __('procurement.terms_conditions') }}
                </div>
                <div class="small text-muted">
                    {{ $purchaseOrder->terms_conditions }}
                </div>
            </div>
        @endif

        <!-- Signatures -->
        <div class="row pt-4 mt-5">
            <div class="col-4">
                <div class="signature-line mx-auto">
                    <div>{{ $purchaseOrder->creator?->name ?? 'Staff' }}</div>
                    <div class="text-muted small fw-normal">{{ __('procurement.created_by') }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="signature-line mx-auto">
                    <div>{{ $purchaseOrder->approver?->name ?? 'Authorized Official' }}</div>
                    <div class="text-muted small fw-normal">{{ __('procurement.authorized_signature') }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="signature-line mx-auto">
                    <div>{{ $purchaseOrder->vendor->name }}</div>
                    <div class="text-muted small fw-normal">{{ __('procurement.vendor_signature') }}</div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
