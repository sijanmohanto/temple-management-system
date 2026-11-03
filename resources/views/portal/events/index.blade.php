@extends('layouts.app')

@section('title', __('portal.public_events'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-globe text-primary me-2"></i>{{ __('portal.public_events') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('portal.public_events_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-calendar-event me-1"></i> {{ __('nav.events_management') }}
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-funnel me-1 text-primary"></i> {{ __('common.filter') }}
            </h6>
        </div>
        <div class="card-body">
            <form id="filterPublicEventsForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('portal.publication_status') }}</label>
                    <select name="publication_status" id="filterPublicationStatus" class="form-select form-select-sm">
                        <option value="all">-- {{ __('portal.all_publication_statuses') }} --</option>
                        <option value="draft">{{ __('portal.draft') }}</option>
                        <option value="published">{{ __('portal.published') }}</option>
                        <option value="unpublished">{{ __('portal.unpublished') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('events.status') }}</label>
                    <select name="event_status" id="filterEventStatus" class="form-select form-select-sm">
                        <option value="all">-- {{ __('portal.all_event_statuses') }} --</option>
                        <option value="upcoming">{{ __('events.status_upcoming') }}</option>
                        <option value="active">{{ __('events.status_active') }}</option>
                        <option value="closing">{{ __('events.status_closing') }}</option>
                        <option value="closed">{{ __('events.status_closed') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('events.event_year') }}</label>
                    <select name="year" id="filterYear" class="form-select form-select-sm">
                        <option value="all">-- {{ __('portal.all_years') }} --</option>
                        @foreach($years as $yr)
                            <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilter">
                        <i class="bi bi-filter me-1"></i> {{ __('common.filter') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetFilter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card (STRICTLY NO # / SERIAL COLUMN) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle w-100" id="publicEventsTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('events.event_name') }}</th>
                            <th>{{ __('portal.slug') }}</th>
                            <th>{{ __('events.category') }} &bull; {{ __('events.event_year') }}</th>
                            <th>{{ __('events.dates') }}</th>
                            <th>{{ __('portal.liturgical_status') }}</th>
                            <th>{{ __('portal.publication_status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
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
$(document).ready(function() {
    var table = TempleUI.initDataTable('#publicEventsTable', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("portal.events.index") }}',
            data: function(d) {
                d.publication_status = $('#filterPublicationStatus').val();
                d.event_status = $('#filterEventStatus').val();
                d.year = $('#filterYear').val();
            }
        },
        columns: [
            { data: 'event_details', name: 'name_en', orderable: true },
            { data: 'slug', name: 'slug', orderable: true },
            { data: 'category_year', name: 'event_year', orderable: true },
            { data: 'dates', name: 'start_date', orderable: true },
            { data: 'event_status', name: 'status', orderable: true },
            { data: 'publication_status', name: 'publication_status', orderable: true },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end pe-3' }
        ],
        order: [[3, 'desc']],
        pageLength: 15
    });

    $('#filterPublicEventsForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#btnResetFilter').on('click', function() {
        $('#filterPublicEventsForm')[0].reset();
        table.ajax.reload();
    });

    // Publish / Unpublish Toggle Handler
    $(document).on('click', '.btn-toggle-publish', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var action = $btn.data('action');
        var url = $btn.data('url');
        var confirmMsg = action === 'publish'
            ? '{{ __("portal.publish_confirmation") }}'
            : '{{ __("portal.unpublish_confirmation") }}';

        if (!confirm(confirmMsg)) {
            return;
        }

        TempleUI.button.loading($btn);

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(res) {
                TempleUI.button.reset($btn);
                if (window.TempleUI && window.TempleUI.toast) {
                    TempleUI.toast.success(res.message);
                }
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                TempleUI.button.reset($btn);
                var err = 'Action failed.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    err = xhr.responseJSON.message;
                }
                if (window.TempleUI && window.TempleUI.toast) {
                    TempleUI.toast.error(err);
                } else {
                    alert(err);
                }
            }
        });
    });
});
</script>
@endpush
