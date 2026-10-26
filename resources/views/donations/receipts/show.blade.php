@extends('layouts.app')

@section('title', __('donations.receipt_details') . ' - ' . $receipt->receipt_number)

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('receipts.index') }}" class="btn btn-sm btn-outline-secondary rounded-circle">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0 text-dark">{{ __('donations.receipt_details') }}</h1>
            </div>
            <p class="text-muted small mb-0 font-monospace">{{ $receipt->receipt_number }} &bull; {{ $receipt->issued_at?->format('d M Y, h:i A') }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('collections.show', $receipt->payment) }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-cash me-1"></i> {{ __('donations.payment_details') }}
            </a>
            <button type="button" class="btn btn-outline-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#shareReceiptModal">
                <i class="bi bi-share me-1"></i> {{ __('donations.share_receipt') }}
            </button>
            <button type="button" class="btn btn-outline-danger rounded-pill px-3 shadow-sm" id="downloadPdfBtn">
                <i class="bi bi-file-earmark-pdf me-1"></i> {{ __('donations.download_receipt_pdf') }}
            </button>
            @if(Auth::user()->hasPermission('print_receipts'))
                <a href="{{ route('receipts.print', $receipt) }}" target="_blank" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                    <i class="bi bi-printer me-1"></i> {{ __('donations.print_receipt') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Void Notice if Voided -->
    @if($receipt->isVoided())
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 p-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-slash-circle-fill fs-1 text-danger"></i>
                <div>
                    <h5 class="fw-bold mb-1">{{ __('donations.receipt_status_voided') }}</h5>
                    <p class="mb-1 text-danger-emphasis">
                        This receipt was voided on <strong>{{ $receipt->voided_at?->format('d M Y, h:i A') }}</strong> by <strong>{{ $receipt->voider?->name ?? 'System' }}</strong> following payment reversal.
                    </p>
                    <div class="small text-muted">
                        <strong>{{ __('donations.reversal_reason') }}:</strong> {{ $receipt->void_reason ?? 'Payment reversed by authorized authority.' }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Voucher Display -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative" id="receiptVoucherCard">
                @if($receipt->isVoided())
                    <div class="position-absolute top-50 start-50 translate-middle text-danger opacity-25 fw-bold text-uppercase pointer-events-none" style="font-size: 6rem; transform: translate(-50%, -50%) rotate(-30deg); z-index: 10; letter-spacing: 0.5rem; user-select: none;">
                        VOID
                    </div>
                @endif

                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4 class="fw-bold text-dark mb-1">{{ config('app.name', 'Temple Management System') }}</h4>
                            <p class="text-muted small mb-0">{{ __('donations.official_receipt') }}</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2">{{ $receipt->receipt_number }}</span>
                            <div class="mt-2">
                                @if($receipt->isValid())
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1">
                                        <i class="bi bi-check-circle me-1"></i>{{ __('donations.receipt_status_valid') }}
                                    </span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-1">
                                        <i class="bi bi-slash-circle me-1"></i>{{ __('donations.receipt_status_voided') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">{{ __('donations.payer') }}</span>
                            <div class="fw-bold fs-5 text-dark mt-1">{{ $receipt->payment?->payer_display_name }}</div>
                            @if($receipt->payment?->payerPerson?->phone)
                                <div class="small text-muted mt-1"><i class="bi bi-telephone me-1"></i>{{ $receipt->payment->payerPerson->phone }}</div>
                            @endif
                            @if($receipt->payment?->payerPerson?->address)
                                <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $receipt->payment->payerPerson->address }}</div>
                            @endif
                        </div>

                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted small d-block">{{ __('donations.payment_date') }}</span>
                            <div class="fw-semibold text-dark mt-1">{{ $receipt->payment?->payment_date?->format('d F Y') }}</div>
                            <div class="small text-muted mt-1">
                                {{ __('donations.payment_code') }}: <span class="font-monospace text-dark fw-bold">{{ $receipt->payment?->payment_code }}</span>
                            </div>
                            <div class="small text-muted mt-1">
                                {{ __('donations.payment_method') }}: <span class="fw-bold text-dark">{{ __('donations.method_'.$receipt->payment?->payment_method) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Allocations Table -->
                    <h6 class="fw-bold text-dark mb-3">{{ __('donations.allocations') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('donations.type') }}</th>
                                    <th>{{ __('common.details') }}</th>
                                    <th class="text-end">{{ __('donations.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receipt->payment?->allocations ?? [] as $allocation)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                                {{ __('donations.type_'.$receipt->payment?->collection_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($allocation->pledge)
                                                <span class="fw-semibold text-dark">{{ __('donations.pledges') }}:</span>
                                                <span class="font-monospace text-primary">{{ $allocation->pledge->pledge_code }}</span>
                                                @if($allocation->event)
                                                    <span class="text-muted">({{ $allocation->event->name }})</span>
                                                @endif
                                            @elseif($allocation->monthlyContribution)
                                                <span class="fw-semibold text-dark">{{ __('donations.committee_contributions') }}:</span>
                                                <span class="font-monospace text-primary">{{ $allocation->monthlyContribution->contribution_code }}</span>
                                                <span class="text-muted">({{ $allocation->monthlyContribution->month_name }})</span>
                                            @elseif($allocation->event)
                                                <span class="fw-semibold text-dark">{{ $allocation->event->name }}</span>
                                                <span class="text-muted">({{ $allocation->event->event_year }})</span>
                                            @else
                                                <span class="text-muted">{{ __('donations.type_general_donation') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-dark">৳{{ number_format((float) $allocation->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted text-center py-2">{{ __('donations.type_general_donation') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">{{ __('donations.amount') }}:</th>
                                    <th class="text-end text-success fs-5">৳{{ number_format((float) $receipt->payment?->amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Amount in words -->
                    <div class="p-3 bg-light rounded-3 mb-4">
                        <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.amount_in_words') }}:</span>
                        <div class="fw-bold text-dark fs-6">{{ $amountInWords }}</div>
                    </div>

                    @if($receipt->payment?->notes)
                        <div class="mb-4">
                            <span class="text-muted small fw-semibold d-block mb-1">{{ __('donations.payment_notes') }}:</span>
                            <div class="small text-secondary">{{ $receipt->payment->notes }}</div>
                        </div>
                    @endif

                    <!-- Signatures / Footer -->
                    <div class="row pt-4 mt-4 border-top">
                        <div class="col-6">
                            <span class="text-muted small d-block">{{ __('donations.verified_by') }}</span>
                            <div class="fw-semibold text-dark mt-1">{{ $receipt->issuer?->name ?? 'Authorized Official' }}</div>
                            <div class="small text-muted">{{ $receipt->issued_at?->format('d M Y') }}</div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="border-top border-dark d-inline-block pt-1 px-4 mt-3">
                                <span class="small fw-semibold text-dark">{{ __('donations.authorized_signatory') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Meta -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold text-dark mb-0">{{ __('common.details') }}</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="text-muted small d-block">{{ __('donations.receipt_number') }}</span>
                        <span class="fw-bold font-monospace text-dark">{{ $receipt->receipt_number }}</span>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">{{ __('donations.submitted_by') }}</span>
                        <span class="fw-semibold text-dark">{{ $receipt->payment?->submitter?->name ?? '—' }}</span>
                        <div class="small text-muted">{{ $receipt->payment?->created_at?->format('d M Y, h:i A') }}</div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted small d-block">{{ __('donations.verified_by') }}</span>
                        <span class="fw-semibold text-dark">{{ $receipt->issuer?->name ?? '—' }}</span>
                        <div class="small text-muted">{{ $receipt->issued_at?->format('d M Y, h:i A') }}</div>
                    </div>

                    @if($receipt->isVoided())
                        <div class="mb-3">
                            <span class="text-muted small d-block">{{ __('donations.reversed_by') }}</span>
                            <span class="fw-semibold text-danger">{{ $receipt->voider?->name ?? '—' }}</span>
                            <div class="small text-muted">{{ $receipt->voided_at?->format('d M Y, h:i A') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Receipt Modal -->
<div class="modal fade" id="shareReceiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-share text-primary me-2"></i>{{ __('donations.share_receipt') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-4 text-center">
                    <span class="badge bg-primary px-3 py-1 font-monospace mb-2">{{ $receipt->receipt_number }}</span>
                    <h5 class="fw-bold text-dark mb-1">{{ $receipt->payment?->payer_display_name }}</h5>
                    <div class="text-success fw-bold fs-5">৳{{ number_format((float) $receipt->payment?->amount, 2) }}</div>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary rounded-pill py-2 shadow-sm" id="nativeShareBtn" style="display: none;">
                        <i class="bi bi-phone me-2"></i>{{ __('donations.share_via_device') }}
                    </button>

                    <a href="https://wa.me/?text={{ urlencode(__('donations.official_receipt') . ': ' . $receipt->receipt_number . "\n" . __('donations.payer') . ': ' . $receipt->payment?->payer_display_name . "\n" . __('donations.amount') . ': ৳' . number_format((float) $receipt->payment?->amount, 2) . "\n" . route('receipts.show', $receipt)) }}" target="_blank" class="btn btn-success rounded-pill py-2 shadow-sm text-white">
                        <i class="bi bi-whatsapp me-2"></i>{{ __('donations.share_via_whatsapp') }}
                    </a>

                    <a href="https://www.facebook.com/dialog/send?link={{ urlencode(route('receipts.show', $receipt)) }}&app_id=1000000000000&redirect_uri={{ urlencode(route('receipts.show', $receipt)) }}" target="_blank" class="btn btn-outline-primary rounded-pill py-2">
                        <i class="bi bi-messenger me-2"></i>{{ __('donations.share_via_messenger') }}
                    </a>

                    <div class="input-group mt-2">
                        <input type="text" class="form-control" id="receiptShareUrl" value="{{ route('receipts.show', $receipt) }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copyShareUrlBtn">
                            <i class="bi bi-clipboard me-1"></i>{{ __('donations.copy_receipt_link') }}
                        </button>
                    </div>

                    <a href="{{ route('receipts.download', $receipt) }}" target="_blank" class="btn btn-outline-secondary rounded-pill py-2 mt-2">
                        <i class="bi bi-download me-2"></i>{{ __('donations.download_receipt_pdf') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>
<script>
    $(document).ready(function() {
        if (navigator.share) {
            $('#nativeShareBtn').show().on('click', function() {
                navigator.share({
                    title: '{{ __('donations.official_receipt') }} - {{ $receipt->receipt_number }}',
                    text: '{{ __('donations.official_receipt') }}: {{ $receipt->receipt_number }} (৳{{ number_format((float) $receipt->payment?->amount, 2) }})',
                    url: '{{ route('receipts.show', $receipt) }}'
                }).catch(function(err) {});
            });
        }

        $('#copyShareUrlBtn').on('click', function() {
            var url = document.getElementById('receiptShareUrl');
            url.select();
            url.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(url.value).then(function() {
                TempleUI.toast.success("{{ __('donations.receipt_link_copied') }}");
            });
        });

        $('#downloadPdfBtn').on('click', function() {
            var $btn = $(this);
            TempleUI.button.loading($btn);
            var element = document.getElementById('receiptVoucherCard');
            var opt = {
                margin:       0.2,
                filename:     'Receipt_{{ $receipt->receipt_number }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(function() {
                TempleUI.button.reset($btn);
                TempleUI.toast.success('Receipt PDF downloaded successfully.');
            }).catch(function() {
                TempleUI.button.reset($btn);
            });
        });
    });
</script>
@endpush
