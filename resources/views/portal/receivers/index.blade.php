@extends('layouts.app')

@section('title', __('portal.receiver_accounts'))

@section('content')
<div class="container-fluid py-3">
    {{-- Breadcrumb & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('portal.receiver_accounts') }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ __('portal.receiver_accounts') }}</h1>
            <p class="text-muted small mb-0">{{ __('portal.receiver_accounts_subtitle') }}</p>
        </div>
        <div>
            @if(auth()->user()->can('manage_receiver_accounts'))
                <a href="{{ route('portal.receivers.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('portal.receivers.add_account') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('portal.receivers.payment_method') }}</label>
                    <select id="filterMethod" class="form-select">
                        <option value="">{{ __('portal.receivers.all_methods') }}</option>
                        <option value="bkash">{{ __('portal.payment_methods.bkash') }}</option>
                        <option value="nagad">{{ __('portal.payment_methods.nagad') }}</option>
                        <option value="rocket">{{ __('portal.payment_methods.rocket') }}</option>
                        <option value="bank_transfer">{{ __('portal.payment_methods.bank_transfer') }}</option>
                        <option value="other">{{ __('portal.payment_methods.other') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('common.status') }}</label>
                    <select id="filterActive" class="form-select">
                        <option value="">{{ __('portal.receivers.all_active_statuses') }}</option>
                        <option value="1">{{ __('common.active') }}</option>
                        <option value="0">{{ __('common.inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('portal.receivers.verified') }}</label>
                    <select id="filterVerified" class="form-select">
                        <option value="">{{ __('portal.receivers.all_verification_statuses') }}</option>
                        <option value="1">{{ __('portal.receivers.verified') }}</option>
                        <option value="0">{{ __('portal.receivers.unverified') }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('portal.actions.reset') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="receiversTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('portal.receivers.payment_method') }}</th>
                            <th>{{ __('portal.receivers.account_name') }}</th>
                            <th>{{ __('portal.receivers.account_details') }}</th>
                            <th>{{ __('portal.receivers.qr_code') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('portal.receivers.display_order') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const table = $('#receiversTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('portal.receivers.index') }}",
            data: function (d) {
                d.payment_method = $('#filterMethod').val();
                d.is_active = $('#filterActive').val();
                d.is_verified = $('#filterVerified').val();
            }
        },
        columns: [
            { data: 'payment_method', name: 'payment_method' },
            { data: 'account_name', name: 'account_name' },
            { data: 'account_details', name: 'account_number' },
            { data: 'qr_code', name: 'qr_code', orderable: false, searchable: false, className: 'text-center' },
            { data: 'status', name: 'is_active', className: 'text-center' },
            { data: 'display_order', name: 'display_order', className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        order: [[5, 'asc']],
        pageLength: 15,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "{{ __('common.search') }}...",
            processing: '<div class="spinner-border text-primary spinner-border-sm" role="status"></div>'
        }
    });

    $('#filterMethod, #filterActive, #filterVerified').on('change', function () {
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function () {
        $('#filterMethod').val('');
        $('#filterActive').val('');
        $('#filterVerified').val('');
        table.ajax.reload();
    });

    // Toggle Active/Inactive
    $(document).on('click', '.btn-toggle-active', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const url = $btn.data('url');

        $btn.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                toastr.success(res.message);
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "{{ __('common.error_occurred') }}";
                toastr.error(msg);
                $btn.prop('disabled', false);
            }
        });
    });

    // Toggle Verify/Unverify
    $(document).on('click', '.btn-verify-toggle', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const url = $btn.data('url');
        const isVerify = url.includes('verify') && !url.includes('unverify');
        const confirmMsg = isVerify
            ? "{{ __('portal.receivers.verify_confirm') }}"
            : "{{ __('portal.receivers.unverify_confirm') }}";

        if (!confirm(confirmMsg)) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                toastr.success(res.message);
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "{{ __('common.error_occurred') }}";
                toastr.error(msg);
                $btn.prop('disabled', false);
            }
        });
    });

    // Delete Receiver Account
    $(document).on('click', '.btn-delete-receiver', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const url = $btn.data('url');

        if (!confirm("{{ __('portal.receivers.delete_confirm') }}")) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                toastr.success(res.message);
                table.ajax.reload(null, false);
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
