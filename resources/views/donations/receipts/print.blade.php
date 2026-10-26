<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('donations.official_receipt') }} — {{ $receipt->receipt_number }}</title>

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

        .receipt-card {
            background: #ffffff;
            max-width: 820px;
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

        .receipt-badge-title {
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

        .receipt-meta-box {
            border: 1px dashed #777777;
            padding: 8px 14px;
            border-radius: 6px;
            background-color: #fdfdfd;
        }

        .watermark-void {
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
            width: 200px;
            margin-left: auto;
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

            .receipt-card {
                border: 2px solid #000000;
                box-shadow: none;
                margin: 0;
                max-width: 100%;
                padding: 25px 30px;
            }

            .d-print-none {
                display: none !important;
            }

            .receipt-badge-title {
                background: #000000 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .watermark-void {
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
                <i class="bi bi-printer me-2"></i> {{ __('donations.print_receipt') }}
            </button>
            <button id="downloadPdfPrintBtn" class="btn btn-danger px-3 py-2 fw-semibold">
                <i class="bi bi-file-earmark-pdf me-1"></i> {{ __('donations.download_receipt_pdf') }}
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary px-3 py-2">
                <i class="bi bi-x-circle me-1"></i> Close
            </button>
        </div>
    </div>

    <!-- Printable Receipt Container -->
    <div class="receipt-card" id="receiptPrintCard">
        @if($receipt->isVoided())
            <div class="watermark-void">VOID</div>
        @endif

        <!-- Header -->
        <div class="text-center pb-3 border-bottom border-2 border-dark">
            <div class="temple-title">{{ config('app.name', 'Temple Management System') }}</div>
            <div class="temple-subtitle">শ্রী শ্রী রাধা গোবিন্দ মন্দির ও ধর্মীয় কল্যাণ ট্রাস্ট</div>
            <div class="small text-muted mt-1">Community Temple Complex, Sector 4, Temple Road &bull; Reg. No: TR-892410</div>
            <div class="mt-3">
                <span class="receipt-badge-title">{{ __('donations.official_receipt') }}</span>
            </div>
        </div>

        <!-- Meta Section -->
        <div class="row align-items-center mt-3 pb-3 border-bottom">
            <div class="col-6">
                <div class="receipt-meta-box">
                    <span class="text-muted small d-block">{{ __('donations.receipt_number') }}:</span>
                    <strong class="fs-5 font-monospace text-dark">{{ $receipt->receipt_number }}</strong>
                </div>
            </div>
            <div class="col-6 text-end">
                <div class="small text-muted mb-1">{{ __('donations.payment_date') }}: <strong>{{ $receipt->payment?->payment_date?->format('d/m/Y') }}</strong></div>
                <div class="small text-muted">{{ __('donations.payment_code') }}: <strong class="font-monospace">{{ $receipt->payment?->payment_code }}</strong></div>
            </div>
        </div>

        <!-- Payer Info -->
        <div class="mt-3 pb-3 border-bottom">
            <div class="row">
                <div class="col-8">
                    <span class="text-muted small d-block">Received with thanks from / গ্রহণ করা হলো:</span>
                    <h5 class="fw-bold text-dark mt-1 mb-1">{{ $receipt->payment?->payer_display_name }}</h5>
                    @if($receipt->payment?->payerPerson?->phone)
                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $receipt->payment->payerPerson->phone }}</div>
                    @endif
                    @if($receipt->payment?->payerPerson?->address)
                        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $receipt->payment->payerPerson->address }}</div>
                    @endif
                </div>
                <div class="col-4 text-end">
                    <span class="text-muted small d-block">{{ __('donations.payment_method') }}:</span>
                    <strong class="text-dark">{{ __('donations.method_'.$receipt->payment?->payment_method) }}</strong>
                    @if($receipt->payment?->is_direct_cash_entry)
                        <div class="small text-muted">({{ __('donations.direct_cash_entry') }})</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Allocation Table -->
        <div class="mt-3">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light border-dark">
                    <tr>
                        <th style="width: 50px;" class="text-center">#</th>
                        <th>{{ __('donations.type') }} &amp; {{ __('common.details') }}</th>
                        <th style="width: 160px;" class="text-end">{{ __('donations.amount') }} (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipt->payment?->allocations ?? [] as $idx => $alloc)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>
                                @if($alloc->pledge)
                                    <strong>{{ __('donations.pledges') }}:</strong> {{ $alloc->pledge->pledge_code }}
                                    @if($alloc->event) - {{ $alloc->event->name }} @endif
                                @elseif($alloc->monthlyContribution)
                                    <strong>{{ __('donations.committee_contributions') }}:</strong> {{ $alloc->monthlyContribution->month_name }} ({{ $alloc->monthlyContribution->contribution_code }})
                                @elseif($alloc->event)
                                    <strong>{{ __('donations.type_event_donation') }}:</strong> {{ $alloc->event->name }} ({{ $alloc->event->event_year }})
                                @else
                                    <strong>{{ __('donations.type_general_donation') }}</strong>
                                @endif
                            </td>
                            <td class="text-end fw-bold font-monospace">৳{{ number_format((float) $alloc->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center">1</td>
                            <td>{{ __('donations.type_general_donation') }}</td>
                            <td class="text-end fw-bold font-monospace">৳{{ number_format((float) $receipt->payment?->amount, 2) }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-dark">
                    <tr>
                        <th colspan="2" class="text-end text-uppercase">{{ __('donations.amount') }}:</th>
                        <th class="text-end fs-5 font-monospace">৳{{ number_format((float) $receipt->payment?->amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- In Words -->
        <div class="p-2 border rounded-2 mt-3 bg-light">
            <span class="small text-muted fw-semibold">{{ __('donations.amount_in_words') }}:</span>
            <span class="fw-bold text-dark ps-2">{{ $amountInWords }}</span>
        </div>

        @if($receipt->payment?->notes)
            <div class="small text-muted mt-2">
                <em>{{ __('donations.notes') }}: {{ $receipt->payment->notes }}</em>
            </div>
        @endif

        @if($receipt->isVoided())
            <div class="alert alert-danger p-2 rounded-2 mt-3 mb-0 small">
                <strong>{{ __('donations.receipt_status_voided') }}:</strong> Voided on {{ $receipt->voided_at?->format('d/m/Y') }} by {{ $receipt->voider?->name ?? 'System' }}. Reason: {{ $receipt->void_reason }}
            </div>
        @endif

        <!-- Footer Signatures -->
        <div class="row align-items-end mt-4 pt-3">
            <div class="col-6">
                <div class="small text-muted">{{ __('donations.verified_by') }}:</div>
                <div class="fw-semibold text-dark">{{ $receipt->issuer?->name ?? 'Authorized Official' }}</div>
                <div class="small text-muted">{{ $receipt->issued_at?->format('d/m/Y h:i A') }}</div>
            </div>
            <div class="col-6 text-end">
                <div class="signature-line">
                    {{ __('donations.authorized_signatory') }}
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>
    <script>
        function downloadReceiptPdf() {
            var element = document.getElementById('receiptPrintCard');
            var opt = {
                margin:       0.2,
                filename:     'Receipt_{{ $receipt->receipt_number }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            return html2pdf().set(opt).from(element).save();
        }

        document.getElementById('downloadPdfPrintBtn').addEventListener('click', function() {
            this.disabled = true;
            this.innerText = 'Downloading...';
            var btn = this;
            downloadReceiptPdf().then(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-file-earmark-pdf me-1"></i> {{ __('donations.download_receipt_pdf') }}';
            });
        });

        @if($isDownloadMode ?? false)
            window.addEventListener('DOMContentLoaded', function() {
                setTimeout(downloadReceiptPdf, 400);
            });
        @endif
    </script>
</body>
</html>
