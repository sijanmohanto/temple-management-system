@extends('layouts.app')

@section('title', __('donations.receipt_register'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('donations.receipt_register') }}</h1>
            <p class="text-muted small mb-0">{{ __('donations.official_receipt') }} &bull; {{ $receipts->total() }} {{ __('common.details') }}</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('collections.dashboard') }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> {{ __('donations.dashboard') }}
            </a>
            <a href="{{ route('collections.index') }}" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-cash-stack me-1"></i> {{ __('donations.payments') }}
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('receipts.index') }}" class="row g-3" id="receiptsFilterForm">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">{{ __('common.status') }}</label>
                    <select name="status" id="filterStatus" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="valid" {{ request('status') === 'valid' ? 'selected' : '' }}>{{ __('donations.receipt_status_valid') }}</option>
                        <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>{{ __('donations.receipt_status_voided') }}</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">{{ __('common.search') }}</label>
                    <input type="text" name="search" id="filterSearch" class="form-control" placeholder="Search receipt number, payment code, donor name..." value="{{ request('search') }}">
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">
                        <i class="bi bi-funnel me-1"></i> {{ __('common.filter') }}
                    </button>
                    <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary rounded-pill" title="{{ __('common.reset') }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Receipts Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="receiptsDataTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('donations.receipt_number') }}</th>
                            <th>{{ __('donations.payer') }}</th>
                            <th>{{ __('donations.type') }}</th>
                            <th>{{ __('donations.amount') }}</th>
                            <th>{{ __('donations.verified_at') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($receipts as $receipt)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace">{{ $receipt->receipt_number }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $receipt->payment?->payer_display_name }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                        {{ __('donations.type_'.$receipt->payment?->collection_type) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">৳{{ number_format((float) $receipt->payment?->amount, 2) }}</div>
                                </td>
                                <td>
                                    <div class="small text-muted">{{ $receipt->issued_at?->format('d M Y, h:i A') }}</div>
                                </td>
                                <td>
                                    @if($receipt->isValid())
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <i class="bi bi-check-circle me-1"></i>{{ __('donations.receipt_status_valid') }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                            <i class="bi bi-slash-circle me-1"></i>{{ __('donations.receipt_status_voided') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('receipts.show', $receipt) }}" class="btn btn-outline-primary" title="{{ __('common.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if(Auth::user()->hasPermission('print_receipts'))
                                            <a href="{{ route('receipts.print', $receipt) }}" target="_blank" class="btn btn-outline-secondary" title="{{ __('donations.print_receipt') }}">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="receiptsNativePagination" class="mt-3">
                @if($receipts->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $receipts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var table = TempleUI.initDataTable('#receiptsDataTable', {
            serverSide: true,
            processing: true,
            deferLoading: {{ $receipts->total() }},
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
            ajax: {
                url: "{{ route('receipts.index') }}",
                data: function(d) {
                    d.status = $('#filterStatus').val();
                    d.search = $('#filterSearch').val();
                }
            },
            columns: [
                { data: 0, name: 'receipt_number' },
                { data: 1, name: 'payer' },
                { data: 2, name: 'type' },
                { data: 3, name: 'amount' },
                { data: 4, name: 'issued_at' },
                { data: 5, name: 'status' },
                { data: 6, name: 'actions', orderable: false, className: 'text-end pe-3' }
            ]
        });

        $('#receiptsNativePagination').hide();

        $('#receiptsFilterForm').on('submit', function(e) {
            e.preventDefault();
            table.draw();
        });

        $('#filterStatus').on('change', function() {
            table.draw();
        });
    });
</script>
@endpush
