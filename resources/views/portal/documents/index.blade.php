@extends('layouts.app')

@section('title', __('portal.event_documents'))

@section('content')
<div class="container-fluid py-3">
    {{-- Breadcrumb & Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('portal.event_documents') }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ __('portal.event_documents') }}</h1>
            <p class="text-muted small mb-0">{{ __('portal.event_documents_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()->can('manage_event_documents') || auth()->user()->can('manage_public_documents'))
                <a href="{{ route('portal.documents.create') }}" class="btn btn-primary">
                    <i class="bi bi-cloud-arrow-up me-1"></i> {{ __('portal.documents.upload_document') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('portal.documents.event') }}</label>
                    <select id="filterEvent" class="form-select select2-enable">
                        <option value="">{{ __('portal.all_events') }}</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" {{ $selectedEventId == $event->id ? 'selected' : '' }}>
                                {{ $event->name }} ({{ $event->event_code }} - {{ $event->event_year }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('portal.documents.type') }}</label>
                    <select id="filterType" class="form-select select2-enable">
                        <option value="">{{ __('portal.all_types') }}</option>
                        <option value="financial_report">{{ __('portal.document_types.financial_report') }}</option>
                        <option value="audit_report">{{ __('portal.document_types.audit_report') }}</option>
                        <option value="supporting_document">{{ __('portal.document_types.supporting_document') }}</option>
                        <option value="other">{{ __('portal.document_types.other') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('portal.publication_status') }}</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">{{ __('portal.all_publication_statuses') }}</option>
                        <option value="published">{{ __('portal.status.published') }}</option>
                        <option value="unpublished">{{ __('portal.status.unpublished') }}</option>
                        <option value="draft">{{ __('portal.status.draft') }}</option>
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
                <table id="documentsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('portal.documents.title') }}</th>
                            <th>{{ __('portal.documents.event') }}</th>
                            <th>{{ __('portal.documents.type') }}</th>
                            <th>{{ __('portal.documents.file_info') }}</th>
                            <th>{{ __('portal.publication_status') }}</th>
                            <th>{{ __('portal.published_at') }}</th>
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
    const table = $('#documentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('portal.documents.index') }}",
            data: function (d) {
                d.event_id = $('#filterEvent').val();
                d.document_type = $('#filterType').val();
                d.publication_status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'document_title', name: 'title_en' },
            { data: 'event_name', name: 'event_id' },
            { data: 'document_type', name: 'document_type' },
            { data: 'file_info', name: 'file_size' },
            { data: 'publication_status', name: 'publication_status', className: 'text-center' },
            { data: 'published_at', name: 'published_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
        ],
        order: [[5, 'desc']],
        pageLength: 15,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "{{ __('common.search') }}...",
            processing: '<div class="spinner-border text-primary spinner-border-sm" role="status"></div>'
        }
    });

    $('#filterEvent, #filterType, #filterStatus').on('change', function () {
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function () {
        $('#filterEvent').val('').trigger('change');
        $('#filterType').val('').trigger('change');
        $('#filterStatus').val('');
        table.ajax.reload();
    });

    // Toggle Publish Status
    $(document).on('click', '.btn-publish, .btn-unpublish', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const url = $btn.data('url');
        const isPublish = $btn.hasClass('btn-publish');
        const confirmMsg = isPublish
            ? "{{ __('portal.documents.publish_confirm') }}"
            : "{{ __('portal.documents.unpublish_confirm') }}";

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

    // Delete Document
    $(document).on('click', '.btn-delete-doc', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const url = $btn.data('url');

        if (!confirm("{{ __('portal.documents.delete_confirm') }}")) {
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
