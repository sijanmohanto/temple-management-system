@extends('layouts.app')

@section('title', __('portal.payments.title'))

@section('content')
<div class="container-fluid py-3">
    {{-- Breadcrumb & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('portal.payments.title') }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ __('portal.payments.title') }}</h1>
            <p class="text-muted small mb-0">{{ __('portal.payment_verification_subtitle') }}</p>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('common.status') }}</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">{{ __('portal.payments.all_statuses') }}</option>
                        <option value="pending_verification" selected>{{ __('donations.status_pending') }}</option>
                        <option value="verified">{{ __('donations.status_verified') }}</option>
                        <option value="rejected">{{ __('donations.status_rejected') }}</option>
                        <option value="reversed">{{ __('donations.status_reversed') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('portal.payments.event') }}</label>
                    <select id="filterEvent" class="form-select select2-enable">
                        <option value="">{{ __('portal.all_events') }}</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->name }} ({{ $event->event_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('portal.payments.receiver_account') }}</label>
                    <select id="filterReceiver" class="form-select select2-enable">
                        <option value="">{{ __('portal.payments.all_receivers') }}</option>
                        @foreach($receiverAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->account_name }} ({{ $account->account_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('portal.payments.method') }}</label>
                    <select id="filterMethod" class="form-select">
                        <option value="">{{ __('portal.receivers.all_methods') }}</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="rocket">Rocket</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary w-100" title="{{ __('portal.actions.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="publicPaymentsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('portal.payments.donor') }}</th>
                            <th>{{ __('portal.payments.event') }}</th>
                            <th>{{ __('portal.payments.amount') }}</th>
                            <th>{{ __('portal.payments.trx_id') }}</th>
                            <th>{{ __('portal.payments.payment_proof') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('portal.payments.submission_time') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Verify Modal --}}
<div class="modal fade" id="verifyPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="verifyPaymentForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-check-circle me-1"></i> {{ __('portal.payments.verify_and_post') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 px-3 small d-flex align-items-center mb-3">
                        <i class="bi bi-shield-exclamation me-2 fs-5"></i>
                        <span>{{ __('portal.payments.trx_statement_warning') }}</span>
                    </div>

                    {{-- Submission Summary --}}
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="row g-2 small">
                            <div class="col-md-6">
                                <span class="text-muted d-block">{{ __('portal.payments.donor') }}:</span>
                                <span class="fw-bold" id="modalVerifyDonor">—</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted d-block">{{ __('portal.payments.event') }}:</span>
                                <span class="fw-bold" id="modalVerifyEvent">—</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block">Payment Code:</span>
                                <code class="fw-bold" id="modalVerifyCode">—</code>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block">{{ __('portal.payments.amount') }}:</span>
                                <span class="fw-bold text-success fs-6" id="modalVerifyAmount">—</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block">{{ __('portal.payments.method') }}:</span>
                                <span class="badge bg-dark" id="modalVerifyMethod">—</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted d-block">{{ __('portal.payments.trx_id') }}:</span>
                                <code class="fw-bold" id="modalVerifyTrx">—</code>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted d-block">{{ __('portal.payments.payment_proof') }}:</span>
                                <div id="modalVerifyProofBox" class="d-none mt-1">
                                    <a href="#" target="_blank" id="modalVerifyProofLink" class="text-decoration-none">
                                        <img src="" alt="Proof" id="modalVerifyProofImg" class="rounded border me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <span class="small btn btn-xs btn-outline-secondary py-0 px-2"><i class="bi bi-arrows-fullscreen me-1"></i>{{ __('portal.actions.preview') }}</span>
                                    </a>
                                </div>
                                <div id="modalVerifyNoProofBox" class="text-muted fst-italic">
                                    <span class="badge bg-light text-muted border">{{ __('portal.payments.no_proof') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Allocation Details: Pledge vs Voluntary --}}
                    <div id="modalVerifyPledgeBox" class="p-3 bg-light-subtle rounded border border-primary-subtle mb-3 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-primary mb-0 small text-uppercase">
                                <i class="bi bi-bookmark-check-fill me-1"></i> {{ __('portal.payments.pledge_details') }}
                            </h6>
                            <span class="badge bg-light text-primary border border-primary-subtle font-monospace" id="modalVerifyPledgeCode">—</span>
                        </div>
                        <div class="row g-2 text-center small">
                            <div class="col-4">
                                <div class="bg-white p-2 rounded border">
                                    <div class="text-muted">{{ __('portal.payments.original_pledged_amount') }}</div>
                                    <div class="fw-bold text-dark" id="modalVerifyPledged">৳ 0.00</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-white p-2 rounded border">
                                    <div class="text-muted">{{ __('portal.payments.total_verified_paid') }}</div>
                                    <div class="fw-bold text-primary" id="modalVerifyPaid">৳ 0.00</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-white p-2 rounded border">
                                    <div class="text-muted">{{ __('portal.payments.remaining_pledge_due') }}</div>
                                    <div class="fw-bold text-danger" id="modalVerifyDue">৳ 0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="modalVerifyVoluntaryBox" class="p-3 bg-success-subtle rounded border border-success-subtle mb-3 d-none">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-heart-fill text-success me-2"></i>
                            <h6 class="fw-bold text-success mb-0 small text-uppercase" id="modalVerifyVoluntaryTitle">
                                {{ __('portal.payments.voluntary_event_donation') }}
                            </h6>
                        </div>
                        <p class="text-muted small mb-0" id="modalVerifyVoluntaryHelp">
                            {{ __('portal.payments.voluntary_verification_info') }}
                        </p>
                    </div>

                    <div class="mb-3">
                        <label for="verification_note" class="form-label small fw-bold">
                            {{ __('portal.payments.verification_note') }}
                        </label>
                        <textarea name="verification_note" id="verification_note" rows="2" class="form-control" placeholder="Optional audit note..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('portal.actions.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-success" id="btnSubmitVerify">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSubmitVerifySpinner" role="status" aria-hidden="true"></span>
                        <i class="bi bi-check-lg me-1" id="btnSubmitVerifyIcon"></i> {{ __('portal.payments.verify_and_post') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="rejectPaymentForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-x-circle me-1"></i> {{ __('portal.payments.reject') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted d-block">Payment Code:</span>
                                <code class="fw-bold" id="modalRejectCode">—</code>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">{{ __('portal.payments.amount') }}:</span>
                                <span class="fw-bold text-danger" id="modalRejectAmount">—</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">{{ __('portal.payments.donor') }}:</span>
                                <span class="fw-semibold" id="modalRejectDonor">—</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">{{ __('portal.payments.event') }}:</span>
                                <span class="fw-semibold" id="modalRejectEvent">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label small fw-bold">
                            {{ __('portal.payments.rejection_reason') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="3" class="form-control" required placeholder="State why this submission is rejected (e.g. Transaction ID not found in MFS statement)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('portal.actions.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-danger" id="btnSubmitReject">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSubmitRejectSpinner" role="status" aria-hidden="true"></span>
                        <i class="bi bi-x-lg me-1" id="btnSubmitRejectIcon"></i> {{ __('portal.payments.reject') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
{{-- Reverse Payment Modal --}}
<div class="modal fade" id="reversePaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="reversePaymentForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('donations.reverse_payment') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                        <div>
                            <strong>{{ __('portal.payments.reversal_warning_title') }}</strong>:
                            {{ __('portal.payments.reversal_warning_body') }}
                        </div>
                    </div>

                    {{-- Payment Details Summary --}}
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="row g-2 small">
                            <div class="col-md-6">
                                <span class="text-muted d-block">{{ __('portal.payments.donor') }}:</span>
                                <span class="fw-bold" id="modalReverseDonor">—</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted d-block">{{ __('portal.payments.event') }}:</span>
                                <span class="fw-bold" id="modalReverseEvent">—</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block">Payment Code:</span>
                                <code class="fw-bold" id="modalReverseCode">—</code>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block">{{ __('portal.payments.amount') }}:</span>
                                <span class="fw-bold text-danger fs-6" id="modalReverseAmount">—</span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block">{{ __('portal.payments.method') }}:</span>
                                <span class="badge bg-dark" id="modalReverseMethod">—</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted d-block">{{ __('portal.payments.trx_id') }}:</span>
                                <code class="fw-bold" id="modalReverseTrx">—</code>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted d-block">{{ __('donations.receipts') }}:</span>
                                <span class="badge bg-secondary font-monospace" id="modalReverseReceipt">—</span>
                            </div>
                        </div>
                    </div>

                    {{-- Allocation Impact Summary --}}
                    <div id="modalReversePledgeBox" class="p-3 bg-light-subtle rounded border border-primary-subtle mb-3 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="fw-bold text-primary mb-0 small text-uppercase">
                                <i class="bi bi-bookmark-check-fill me-1"></i> {{ __('portal.payments.pledge_details') }}
                            </h6>
                            <span class="badge bg-light text-primary border border-primary-subtle font-monospace" id="modalReversePledgeCode">—</span>
                        </div>
                        <p class="text-muted small mb-0">
                            {{ __('portal.payments.reversal_pledge_restoration_help') }}
                        </p>
                    </div>

                    <div id="modalReverseVoluntaryBox" class="p-3 bg-success-subtle rounded border border-success-subtle mb-3 d-none">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-heart-fill text-success me-2"></i>
                            <h6 class="fw-bold text-success mb-0 small text-uppercase" id="modalReverseVoluntaryTitle">
                                {{ __('portal.payments.voluntary_event_donation') }}
                            </h6>
                        </div>
                        <p class="text-muted small mb-0">
                            {{ __('portal.payments.reversal_voluntary_help') }}
                        </p>
                    </div>

                    <div class="mb-3">
                        <label for="modal_reversal_reason" class="form-label small fw-bold">
                            {{ __('donations.reversal_reason') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="reversal_reason" id="modal_reversal_reason" rows="3" class="form-control" required placeholder="State why this verified payment is being reversed..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('portal.actions.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-danger" id="btnSubmitReverse">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSubmitReverseSpinner" role="status" aria-hidden="true"></span>
                        <i class="bi bi-arrow-counterclockwise me-1" id="btnSubmitReverseIcon"></i> {{ __('donations.reverse_payment') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const table = $('#publicPaymentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('portal.payments.index') }}",
            data: function (d) {
                d.status = $('#filterStatus').val();
                d.event_id = $('#filterEvent').val();
                d.receiver_account_id = $('#filterReceiver').val();
                d.payment_method = $('#filterMethod').val();
            }
        },
        columns: [
            { data: 'donor_info', name: 'public_donor_name' },
            { data: 'event_details', name: 'event_id' },
            { data: 'amount_details', name: 'amount' },
            { data: 'transaction_details', name: 'transaction_reference' },
            { data: 'proof', name: 'payment_proof_path', orderable: false, searchable: false, className: 'text-center' },
            { data: 'status', name: 'status', className: 'text-center' },
            { data: 'submitted_at', name: 'payment_date' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        order: [[6, 'desc']],
        pageLength: 15,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "{{ __('common.search') }}...",
            processing: '<div class="spinner-border text-primary spinner-border-sm" role="status"></div>'
        }
    });

    $('#filterStatus, #filterEvent, #filterReceiver, #filterMethod').on('change', function () {
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function () {
        $('#filterStatus').val('');
        $('#filterEvent').val('').trigger('change');
        $('#filterReceiver').val('').trigger('change');
        $('#filterMethod').val('');
        table.ajax.reload();
    });

    // Open Verify Modal
    $(document).on('click', '.btn-verify-payment', function () {
        const $btn = $(this);
        const url = $btn.data('url');
        const code = $btn.data('code');
        const amount = $btn.data('amount');
        const donor = $btn.data('donor');
        const event = $btn.data('event');
        const method = $btn.data('method');
        const trx = $btn.data('trx');
        const proof = $btn.data('proof');
        const type = $btn.data('type');
        const pledgeCode = $btn.data('pledge-code');
        const pledged = $btn.data('pledged');
        const paid = $btn.data('paid');
        const due = $btn.data('due');

        $('#modalVerifyCode').text(code);
        $('#modalVerifyAmount').text('৳ ' + amount);
        $('#modalVerifyDonor').text(donor || '—');
        $('#modalVerifyEvent').text(event || '—');
        $('#modalVerifyMethod').text(method || '—');
        $('#modalVerifyTrx').text(trx || '—');
        $('#verifyPaymentForm').attr('action', url);
        $('#verification_note').val('');

        // Proof preview
        if (proof) {
            $('#modalVerifyProofImg').attr('src', proof);
            $('#modalVerifyProofLink').attr('href', proof);
            $('#modalVerifyProofBox').removeClass('d-none');
            $('#modalVerifyNoProofBox').addClass('d-none');
        } else {
            $('#modalVerifyProofBox').addClass('d-none');
            $('#modalVerifyNoProofBox').removeClass('d-none');
        }

        // Allocation: Pledge vs Voluntary
        if (pledgeCode) {
            $('#modalVerifyPledgeCode').text(pledgeCode);
            $('#modalVerifyPledged').text('৳ ' + pledged);
            $('#modalVerifyPaid').text('৳ ' + paid);
            $('#modalVerifyDue').text('৳ ' + due);
            $('#modalVerifyPledgeBox').removeClass('d-none');
            $('#modalVerifyVoluntaryBox').addClass('d-none');
        } else {
            $('#modalVerifyPledgeBox').addClass('d-none');
            $('#modalVerifyVoluntaryBox').removeClass('d-none');
            if (type === 'voluntary_general') {
                $('#modalVerifyVoluntaryTitle').text("{{ __('portal.payments.voluntary_general_donation') }}");
                $('#modalVerifyVoluntaryHelp').text("{{ __('portal.payments.voluntary_general_donation_help') }}");
            } else {
                $('#modalVerifyVoluntaryTitle').text("{{ __('portal.payments.voluntary_event_donation') }}");
                $('#modalVerifyVoluntaryHelp').text("{{ __('portal.payments.voluntary_verification_info') }}");
            }
        }

        // Reset submit button state
        $('#btnSubmitVerify').prop('disabled', false);
        $('#btnSubmitVerifySpinner').addClass('d-none');
        $('#btnSubmitVerifyIcon').removeClass('d-none');

        const modal = new bootstrap.Modal(document.getElementById('verifyPaymentModal'));
        modal.show();
    });

    // Submit Verify Form via AJAX
    $('#verifyPaymentForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const $btn = $('#btnSubmitVerify');
        const $spinner = $('#btnSubmitVerifySpinner');
        const $icon = $('#btnSubmitVerifyIcon');

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');
        $icon.addClass('d-none');

        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('verifyPaymentModal')).hide();
                toastr.success(res.message);
                table.ajax.reload(null, false);
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
                $icon.removeClass('d-none');
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "{{ __('common.error_occurred') }}";
                toastr.error(msg);
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
                $icon.removeClass('d-none');
            }
        });
    });

    // Open Reject Modal
    $(document).on('click', '.btn-reject-payment', function () {
        const $btn = $(this);
        const url = $btn.data('url');
        const code = $btn.data('code');
        const donor = $btn.data('donor');
        const event = $btn.data('event');
        const amount = $btn.data('amount');

        $('#modalRejectCode').text(code);
        $('#modalRejectDonor').text(donor || '—');
        $('#modalRejectEvent').text(event || '—');
        $('#modalRejectAmount').text('৳ ' + amount);
        $('#rejectPaymentForm').attr('action', url);
        $('#rejection_reason').val('');

        // Reset button state
        $('#btnSubmitReject').prop('disabled', false);
        $('#btnSubmitRejectSpinner').addClass('d-none');
        $('#btnSubmitRejectIcon').removeClass('d-none');

        const modal = new bootstrap.Modal(document.getElementById('rejectPaymentModal'));
        modal.show();
    });

    // Submit Reject Form via AJAX
    $('#rejectPaymentForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const $btn = $('#btnSubmitReject');
        const $spinner = $('#btnSubmitRejectSpinner');
        const $icon = $('#btnSubmitRejectIcon');

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');
        $icon.addClass('d-none');

        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('rejectPaymentModal')).hide();
                toastr.success(res.message);
                table.ajax.reload(null, false);
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
                $icon.removeClass('d-none');
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "{{ __('common.error_occurred') }}";
                toastr.error(msg);
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
                $icon.removeClass('d-none');
            }
        });
    });

    // Open Reverse Modal
    $(document).on('click', '.btn-reverse-payment', function () {
        const $btn = $(this);
        const url = $btn.data('url');
        const code = $btn.data('code');
        const amount = $btn.data('amount');
        const donor = $btn.data('donor');
        const event = $btn.data('event');
        const method = $btn.data('method');
        const trx = $btn.data('trx');
        const receipt = $btn.data('receipt');
        const type = $btn.data('type');
        const pledgeCode = $btn.data('pledge-code');

        $('#modalReverseCode').text(code);
        $('#modalReverseAmount').text('৳ ' + amount);
        $('#modalReverseDonor').text(donor || '—');
        $('#modalReverseEvent').text(event || '—');
        $('#modalReverseMethod').text(method || '—');
        $('#modalReverseTrx').text(trx || '—');
        $('#modalReverseReceipt').text(receipt || '—');
        $('#reversePaymentForm').attr('action', url);
        $('#modal_reversal_reason').val('');

        if (pledgeCode) {
            $('#modalReversePledgeCode').text(pledgeCode);
            $('#modalReversePledgeBox').removeClass('d-none');
            $('#modalReverseVoluntaryBox').addClass('d-none');
        } else {
            $('#modalReversePledgeBox').addClass('d-none');
            $('#modalReverseVoluntaryBox').removeClass('d-none');
            if (type === 'voluntary_general') {
                $('#modalReverseVoluntaryTitle').text("{{ __('portal.payments.voluntary_general_donation') }}");
            } else {
                $('#modalReverseVoluntaryTitle').text("{{ __('portal.payments.voluntary_event_donation') }}");
            }
        }

        // Reset submit button state
        $('#btnSubmitReverse').prop('disabled', false);
        $('#btnSubmitReverseSpinner').addClass('d-none');
        $('#btnSubmitReverseIcon').removeClass('d-none');

        const modal = new bootstrap.Modal(document.getElementById('reversePaymentModal'));
        modal.show();
    });

    // Submit Reverse Form via AJAX
    $('#reversePaymentForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const $btn = $('#btnSubmitReverse');
        const $spinner = $('#btnSubmitReverseSpinner');
        const $icon = $('#btnSubmitReverseIcon');

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');
        $icon.addClass('d-none');

        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('reversePaymentModal')).hide();
                toastr.success(res.message);
                table.ajax.reload(null, false);
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
                $icon.removeClass('d-none');
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "{{ __('common.error_occurred') }}";
                toastr.error(msg);
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
                $icon.removeClass('d-none');
            }
        });
    });
});
</script>
@endpush
