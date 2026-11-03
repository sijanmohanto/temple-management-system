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
    <div class="modal-dialog">
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
                    <p class="text-muted small mb-3">
                        {{ __('portal.payments.confirm_verify') }}
                    </p>
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Payment Code:</span>
                            <span class="fw-bold" id="modalVerifyCode">—</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Amount:</span>
                            <span class="fw-bold text-success" id="modalVerifyAmount">—</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">TrxID:</span>
                            <code class="fw-bold" id="modalVerifyTrx">—</code>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="create_guest_person" id="create_guest_person" value="1" checked>
                        <label class="form-check-label small fw-semibold" for="create_guest_person">
                            {{ __('portal.payments.create_guest_person') }}
                        </label>
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
                        <i class="bi bi-check-lg me-1"></i> {{ __('portal.payments.verify_and_post') }}
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
                    <p class="text-muted small mb-3">
                        {{ __('portal.payments.confirm_reject') }}
                    </p>
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Payment Code:</span>
                            <span class="fw-bold" id="modalRejectCode">—</span>
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
                        <i class="bi bi-x-lg me-1"></i> {{ __('portal.payments.reject') }}
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
        const trx = $btn.data('trx');

        $('#modalVerifyCode').text(code);
        $('#modalVerifyAmount').text('৳ ' + amount);
        $('#modalVerifyTrx').text(trx || '—');
        $('#verifyPaymentForm').attr('action', url);
        $('#verification_note').val('');

        const modal = new bootstrap.Modal(document.getElementById('verifyPaymentModal'));
        modal.show();
    });

    // Submit Verify Form via AJAX
    $('#verifyPaymentForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const $btn = $('#btnSubmitVerify');

        $btn.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('verifyPaymentModal')).hide();
                toastr.success(res.message);
                table.ajax.reload(null, false);
                $btn.prop('disabled', false);
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "{{ __('common.error_occurred') }}";
                toastr.error(msg);
                $btn.prop('disabled', false);
            }
        });
    });

    // Open Reject Modal
    $(document).on('click', '.btn-reject-payment', function () {
        const $btn = $(this);
        const url = $btn.data('url');
        const code = $btn.data('code');

        $('#modalRejectCode').text(code);
        $('#rejectPaymentForm').attr('action', url);
        $('#rejection_reason').val('');

        const modal = new bootstrap.Modal(document.getElementById('rejectPaymentModal'));
        modal.show();
    });

    // Submit Reject Form via AJAX
    $('#rejectPaymentForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const $btn = $('#btnSubmitReject');

        $btn.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('rejectPaymentModal')).hide();
                toastr.success(res.message);
                table.ajax.reload(null, false);
                $btn.prop('disabled', false);
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "{{ __('common.error_occurred') }}";
                toastr.error(msg);
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
