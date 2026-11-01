@extends('layouts.app')

@section('title', __('procurement.payment_list'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="bi bi-cash-stack text-success me-2"></i>{{ __('procurement.vendor_payments') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('procurement.payment_list') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if(Auth::user()->hasPermission('manage_vendor_payments'))
                <a href="{{ route('procurement.payments.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('procurement.record_payment') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Summary Performance Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.vendor_payments') }}</div>
                    <div class="h3 mb-0 fw-bold text-primary">{{ $summary['total_payments'] }}</div>
                    <small class="text-muted">{{ __('procurement.payment_list') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.total_paid') }}</div>
                    <div class="h3 mb-0 fw-bold text-success">{{ $summary['posted_amount'] }}</div>
                    <small class="text-muted">{{ __('procurement.status_posted') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.pending_verification') }}</div>
                    <div class="h3 mb-0 fw-bold text-warning">{{ $summary['pending_count'] }}</div>
                    <small class="text-muted">{{ __('procurement.status_submitted') }} / {{ __('procurement.status_verified') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm border-start border-4 border-secondary h-100">
                <div class="card-body">
                    <div class="text-muted small fw-bold text-uppercase">{{ __('procurement.status_draft') }}</div>
                    <div class="h3 mb-0 fw-bold text-secondary">{{ $summary['draft_count'] }}</div>
                    <small class="text-muted">{{ __('procurement.status_draft') }}</small>
                </div>
            </div>
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
            <form id="paymentFilterForm" class="row g-3">
                <!-- Status Filter -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('common.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select form-select-sm">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        <option value="draft">{{ __('procurement.status_draft') }}</option>
                        <option value="submitted">{{ __('procurement.status_submitted') }}</option>
                        <option value="verified">{{ __('procurement.status_verified') }}</option>
                        <option value="approved">{{ __('procurement.status_approved') }}</option>
                        <option value="posted">{{ __('procurement.status_posted') }}</option>
                        <option value="rejected">{{ __('procurement.status_rejected') }}</option>
                        <option value="cancelled">{{ __('procurement.status_cancelled') }}</option>
                        <option value="reversed">{{ __('procurement.status_reversed') }}</option>
                    </select>
                </div>

                <!-- Vendor Filter -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('procurement.vendor') }}</label>
                    <select name="vendor_id" id="filterVendor" class="form-select form-select-sm">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        @foreach($vendors as $ven)
                            <option value="{{ $ven->id }}">{{ $ven->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Method Filter -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('procurement.payment_method') }}</label>
                    <select name="payment_method" id="filterMethod" class="form-select form-select-sm">
                        <option value="all">-- {{ __('common.all') }} --</option>
                        @foreach(\App\Models\VendorPayment::supportedPaymentMethods() as $mKey => $mLabel)
                            <option value="{{ $mKey }}">{{ $mLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-md-3 d-flex align-items-end gap-2">
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
                <table class="table table-hover align-middle w-100" id="paymentsTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('procurement.payment_number') }}</th>
                            <th>{{ __('procurement.vendor') }}</th>
                            <th>{{ __('procurement.payment_date') }}</th>
                            <th>{{ __('procurement.payment_method') }}</th>
                            <th>{{ __('procurement.payment_account') }}</th>
                            <th class="text-end">{{ __('procurement.payment_amount') }}</th>
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
    var table = TempleUI.initDataTable('#paymentsTable', {
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("procurement.payments.index") }}',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.vendor_id = $('#filterVendor').val();
                d.payment_method = $('#filterMethod').val();
            }
        },
        columns: [
            { data: 'payment_number', name: 'payment_number', orderable: true },
            { data: 'vendor', name: 'vendor_id', orderable: true },
            { data: 'payment_date', name: 'payment_date', orderable: true },
            { data: 'payment_method', name: 'payment_method', orderable: true },
            { data: 'financial_account', name: 'financial_account_id', orderable: true },
            { data: 'amount', name: 'amount', orderable: true, className: 'text-end fw-bold text-success font-monospace' },
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

    $('#paymentFilterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#btnResetFilters').on('click', function() {
        $('#paymentFilterForm')[0].reset();
        table.ajax.reload();
    });
});
</script>
@endpush
