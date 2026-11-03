@extends('layouts.app')

@section('title', __('portal.complaints'))

@section('content')
<div class="container-fluid py-3">
    {{-- Breadcrumb & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('portal.complaints') }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ __('portal.complaints') }}</h1>
            <p class="text-muted small mb-0">{{ __('portal.complaints_subtitle') }}</p>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('common.status') }}</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">{{ __('portal.payments.all_statuses') }}</option>
                        <option value="submitted">{{ __('portal.complaint_statuses.submitted') }}</option>
                        <option value="under_review">{{ __('portal.complaint_statuses.under_review') }}</option>
                        <option value="resolved">{{ __('portal.complaint_statuses.resolved') }}</option>
                        <option value="rejected">{{ __('portal.complaint_statuses.rejected') }}</option>
                        <option value="closed">{{ __('portal.complaint_statuses.closed') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('portal.complaints_module.category') }}</label>
                    <select id="filterCategory" class="form-select">
                        <option value="">{{ __('portal.complaints_module.all_categories') }}</option>
                        <option value="payment_issue">{{ __('portal.complaint_categories.payment_issue') }}</option>
                        <option value="event_management">{{ __('portal.complaint_categories.event_management') }}</option>
                        <option value="prasadam_service">{{ __('portal.complaint_categories.prasadam_service') }}</option>
                        <option value="general_inquiry">{{ __('portal.complaint_categories.general_inquiry') }}</option>
                        <option value="suggestion">{{ __('portal.complaint_categories.suggestion') }}</option>
                        <option value="other">{{ __('portal.complaint_categories.other') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('portal.complaints_module.priority') }}</label>
                    <select id="filterPriority" class="form-select">
                        <option value="">{{ __('portal.complaints_module.all_priorities') }}</option>
                        <option value="urgent">{{ __('portal.complaint_priorities.urgent') }}</option>
                        <option value="high">{{ __('portal.complaint_priorities.high') }}</option>
                        <option value="medium">{{ __('portal.complaint_priorities.medium') }}</option>
                        <option value="low">{{ __('portal.complaint_priorities.low') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('portal.complaints_module.assigned_to') }}</label>
                    <select id="filterAssignee" class="form-select">
                        <option value="">{{ __('portal.complaints_module.all_assignees') }}</option>
                        <option value="unassigned">{{ __('portal.complaints_module.unassigned') }}</option>
                        @foreach($staffUsers as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('portal.documents.event') }}</label>
                    <select id="filterEvent" class="form-select select2-enable">
                        <option value="">{{ __('portal.all_events') }}</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->name }}</option>
                        @endforeach
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
                <table id="complaintsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('portal.complaints_module.tracking_number') }}</th>
                            <th>{{ __('portal.complaints_module.complainant') }}</th>
                            <th>{{ __('portal.complaints_module.category') }}</th>
                            <th>Related To</th>
                            <th>{{ __('portal.complaints_module.assigned_to') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>Submitted At</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Quick Respond Modal --}}
<div class="modal fade" id="respondComplaintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="respondComplaintForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-reply me-1"></i> {{ __('portal.complaints_module.respond') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <span class="text-muted small">Tracking Number:</span>
                        <code class="fw-bold fs-6" id="modalRespondTracking">—</code>
                    </div>
                    <div class="mb-3">
                        <label for="admin_response_text" class="form-label small fw-bold">
                            {{ __('portal.complaints_module.admin_response') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="admin_response" id="admin_response_text" rows="4" class="form-control" required placeholder="Type the official message that the complainant will see..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="update_status" class="form-label small fw-bold">Update Status</label>
                        <select name="update_status" id="update_status" class="form-select">
                            <option value="under_review">{{ __('portal.complaint_statuses.under_review') }}</option>
                            <option value="resolved">{{ __('portal.complaint_statuses.resolved') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('portal.actions.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitRespond">
                        <i class="bi bi-send me-1"></i> {{ __('portal.complaints_module.respond') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Quick Resolve Modal --}}
<div class="modal fade" id="resolveComplaintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="resolveComplaintForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-check-circle me-1"></i> {{ __('portal.complaints_module.resolve') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <span class="text-muted small">Tracking Number:</span>
                        <code class="fw-bold fs-6" id="modalResolveTracking">—</code>
                    </div>
                    <div class="mb-3">
                        <label for="resolve_note_text" class="form-label small fw-bold">
                            Resolution Note & Response <span class="text-danger">*</span>
                        </label>
                        <textarea name="admin_response" id="resolve_note_text" rows="4" class="form-control" required placeholder="State how this complaint or inquiry was successfully resolved..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        {{ __('portal.actions.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-success" id="btnSubmitResolve">
                        <i class="bi bi-check-lg me-1"></i> {{ __('portal.complaints_module.resolve') }}
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
    const table = $('#complaintsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('portal.complaints.index') }}",
            data: function (d) {
                d.status = $('#filterStatus').val();
                d.category = $('#filterCategory').val();
                d.priority = $('#filterPriority').val();
                d.assigned_to = $('#filterAssignee').val();
                d.event_id = $('#filterEvent').val();
            }
        },
        columns: [
            { data: 'tracking_info', name: 'tracking_number' },
            { data: 'complainant', name: 'complainant_name' },
            { data: 'category_priority', name: 'category' },
            { data: 'related_to', name: 'event_id', orderable: false },
            { data: 'assigned_to', name: 'assigned_to' },
            { data: 'status', name: 'status', className: 'text-center' },
            { data: 'submitted_at', name: 'created_at' },
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

    $('#filterStatus, #filterCategory, #filterPriority, #filterAssignee, #filterEvent').on('change', function () {
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function () {
        $('#filterStatus').val('');
        $('#filterCategory').val('');
        $('#filterPriority').val('');
        $('#filterAssignee').val('');
        $('#filterEvent').val('').trigger('change');
        table.ajax.reload();
    });

    // Open Respond Modal
    $(document).on('click', '.btn-respond', function () {
        const $btn = $(this);
        const url = $btn.data('url');
        const tracking = $btn.data('tracking');

        $('#modalRespondTracking').text(tracking);
        $('#respondComplaintForm').attr('action', url);
        $('#admin_response_text').val('');

        const modal = new bootstrap.Modal(document.getElementById('respondComplaintModal'));
        modal.show();
    });

    $('#respondComplaintForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const $btn = $('#btnSubmitRespond');

        $btn.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('respondComplaintModal')).hide();
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

    // Open Resolve Modal
    $(document).on('click', '.btn-resolve', function () {
        const $btn = $(this);
        const url = $btn.data('url');
        const tracking = $btn.data('tracking');

        $('#modalResolveTracking').text(tracking);
        $('#resolveComplaintForm').attr('action', url);
        $('#resolve_note_text').val('');

        const modal = new bootstrap.Modal(document.getElementById('resolveComplaintModal'));
        modal.show();
    });

    $('#resolveComplaintForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url = $form.attr('action');
        const $btn = $('#btnSubmitResolve');

        $btn.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('resolveComplaintModal')).hide();
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
