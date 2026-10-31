@extends('layouts.app')

@section('title', __('procurement.vendor_list'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-shop text-primary me-2"></i>{{ __('procurement.vendor_list') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('procurement.procurement_preview_desc') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if(Auth::user()->hasPermission('manage_vendors'))
                <a href="{{ route('procurement.vendors.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('procurement.add_vendor') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Summary Performance Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.total_vendors') }}</div>
                    <div class="h3 mb-0 fw-bold text-primary">{{ $summary['total_vendors'] }}</div>
                    <small class="text-muted">{{ __('procurement.vendors') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.active_vendors') }}</div>
                    <div class="h3 mb-0 fw-bold text-success">{{ $summary['active_vendors'] }}</div>
                    <small class="text-muted">{{ __('common.active') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-secondary h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.inactive_vendors') }}</div>
                    <div class="h3 mb-0 fw-bold text-secondary">{{ $summary['inactive_vendors'] }}</div>
                    <small class="text-muted">{{ __('common.inactive') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.vendor_expenditure_total') }}</div>
                    <div class="h3 mb-0 fw-bold text-info">{{ $summary['total_expenditure'] }}</div>
                    <small class="text-muted">{{ __('procurement.expenses') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-funnel me-1 text-primary"></i> {{ __('common.filter') }}
            </h6>
        </div>
        <div class="card-body">
            <form id="vendorFilterForm" class="row g-3">
                <!-- Status -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('common.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select form-select-sm">
                        <option value="">-- {{ __('common.all') }} --</option>
                        <option value="active">{{ __('common.active') }}</option>
                        <option value="inactive">{{ __('common.inactive') }}</option>
                    </select>
                </div>

                <!-- Vendor Type -->
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('procurement.vendor_type') }}</label>
                    <select name="vendor_type" id="filterType" class="form-select form-select-sm">
                        <option value="">-- {{ __('procurement.all_types') }} --</option>
                        @foreach($vendorTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input & Actions -->
                <div class="col-md-5 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1" id="btnApplyFilters">
                        <i class="bi bi-filter me-1"></i>{{ __('common.filter') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetFilters">
                        <i class="bi bi-arrow-counterclockwise"></i> {{ __('common.reset') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable Card (Strictly NO Row Number / # Column) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle w-100" id="vendorTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('procurement.vendor_code') }}</th>
                            <th>{{ __('procurement.vendor_name') }}</th>
                            <th>{{ __('procurement.phone') }}</th>
                            <th>{{ __('procurement.vendor_type') }}</th>
                            <th>{{ __('procurement.expenses') }}</th>
                            <th class="text-center">{{ __('procurement.is_active') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteVendorModal" tabindex="-1" aria-labelledby="deleteVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteVendorModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ __('common.confirm_delete') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">{{ __('procurement.confirm_delete_vendor') }}</p>
                <div class="mt-2 fw-bold text-dark" id="deleteVendorNameDisplay"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-1"></i> {{ __('common.delete') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = TempleUI.initDataTable('#vendorTable', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("procurement.vendors.index") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.vendor_type = $('#filterType').val();
            }
        },
        columns: [
            { data: 'vendor_code', name: 'vendor_code', orderable: true },
            { data: 'name', name: 'name', orderable: true },
            { data: 'contact', name: 'phone', orderable: false },
            { data: 'vendor_type', name: 'vendor_type', orderable: true },
            { data: 'transactions', name: 'expenses_count', orderable: true },
            { data: 'status', name: 'is_active', orderable: true, className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end pe-3' }
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
        dom: '<"row align-items-center mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row align-items-center mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "{{ __('common.search') }}...",
            paginate: {
                previous: "{{ __('datatables.paginate.previous') }}",
                next: "{{ __('datatables.paginate.next') }}"
            }
        }
    });

    $('#vendorFilterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function() {
        $('#vendorFilterForm')[0].reset();
        table.ajax.reload();
    });

    // Toggle Status Handler
    $(document).on('click', '.btn-toggle-status', function(e) {
        e.preventDefault();
        var btn = $(this);
        var url = btn.data('url');

        btn.prop('disabled', true);

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (window.toastr) {
                        toastr.success(response.message);
                    }
                    table.ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                if (window.toastr) {
                    toastr.error(xhr.responseJSON?.message || '{{ __('common.error') }}');
                }
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Delete Modal Handler
    var targetDeleteUrl = null;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteVendorModal'));

    $(document).on('click', '.btn-delete-vendor', function(e) {
        e.preventDefault();
        targetDeleteUrl = $(this).data('url');
        $('#deleteVendorNameDisplay').text($(this).data('vendor-name'));
        deleteModal.show();
    });

    $('#confirmDeleteBtn').on('click', function() {
        if (!targetDeleteUrl) return;

        var btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: targetDeleteUrl,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'DELETE'
            },
            success: function(response) {
                deleteModal.hide();
                if (window.toastr) {
                    toastr.success(response.message);
                }
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                deleteModal.hide();
                if (window.toastr) {
                    toastr.error(xhr.responseJSON?.message || '{{ __('common.error') }}');
                }
            },
            complete: function() {
                btn.prop('disabled', false);
                targetDeleteUrl = null;
            }
        });
    });
});
</script>
@endpush
