@extends('layouts.app')

@section('title', __('procurement.order_list'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-box-seam text-primary me-2"></i>{{ __('procurement.order_list') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('procurement.procurement_preview_desc') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if(Auth::user()->hasPermission('manage_purchase_orders'))
                <a href="{{ route('procurement.orders.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('procurement.create_order') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Summary Performance Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.total_orders') }}</div>
                    <div class="h3 mb-0 fw-bold text-primary">{{ $summary['total_orders'] }}</div>
                    <small class="text-muted">{{ __('procurement.purchase_orders') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.approved_orders') }}</div>
                    <div class="h3 mb-0 fw-bold text-info">{{ $summary['approved_orders'] }}</div>
                    <small class="text-muted">{{ __('procurement.status_approved') }} / {{ __('procurement.status_sent') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.completed_orders') }}</div>
                    <div class="h3 mb-0 fw-bold text-success">{{ $summary['completed_orders'] }}</div>
                    <small class="text-muted">{{ __('procurement.status_completed') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.total_order_value') }}</div>
                    <div class="h3 mb-0 fw-bold text-warning">{{ $summary['total_order_value'] }}</div>
                    <small class="text-muted">{{ __('procurement.purchase_orders') }}</small>
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
            <form id="orderFilterForm" class="row g-3">
                <!-- Status -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('common.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select form-select-sm">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        <option value="draft">{{ __('procurement.status_draft') }}</option>
                        <option value="approved">{{ __('procurement.status_approved') }}</option>
                        <option value="sent">{{ __('procurement.status_sent') }}</option>
                        <option value="completed">{{ __('procurement.status_completed') }}</option>
                        <option value="cancelled">{{ __('procurement.status_cancelled') }}</option>
                    </select>
                </div>

                <!-- Vendor Filter -->
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('procurement.vendors') }}</label>
                    <select name="vendor_id" id="filterVendor" class="form-select form-select-sm">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        @foreach($vendors as $ven)
                            <option value="{{ $ven->id }}">{{ $ven->name }} ({{ $ven->vendor_code }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Actions -->
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
                <table class="table table-hover align-middle w-100" id="ordersTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('procurement.order_number') }}</th>
                            <th>{{ __('procurement.vendors') }}</th>
                            <th>{{ __('procurement.order_date') }}</th>
                            <th>{{ __('procurement.source_request') }}</th>
                            <th>{{ __('procurement.scope') }}</th>
                            <th class="text-end">{{ __('procurement.total_order_amount') }}</th>
                            <th class="text-center">{{ __('common.status') }}</th>
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
    var table = TempleUI.initDataTable('#ordersTable', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("procurement.orders.index") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.vendor_id = $('#filterVendor').val();
            }
        },
        columns: [
            { data: 'order_number', name: 'order_number', orderable: true },
            { data: 'vendor', name: 'vendor_id', orderable: true },
            { data: 'order_date', name: 'order_date', orderable: true },
            { data: 'purchase_request', name: 'purchase_request_id', orderable: true },
            { data: 'scope', name: 'event_id', orderable: true },
            { data: 'total_amount', name: 'total_amount', orderable: true, className: 'text-end fw-bold text-primary' },
            { data: 'status', name: 'status', orderable: true, className: 'text-center' },
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

    $('#orderFilterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function() {
        $('#orderFilterForm')[0].reset();
        table.ajax.reload();
    });
});
</script>
@endpush
