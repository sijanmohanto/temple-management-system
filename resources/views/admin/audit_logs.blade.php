@extends('layouts.app')

@section('title', __('nav.audit_logs'))

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="bi bi-shield-check text-danger me-2"></i> {{ __('nav.audit_logs') }}
            </h1>
            <p class="text-muted small mb-0">
                Server-enforced audit trails and security monitoring (Protected by <code>role:super_admin</code>)
            </p>
        </div>
        <div>
            <span class="badge bg-danger px-3 py-2 fs-6">
                <i class="bi bi-lock-fill me-1"></i> Super Admin Protected
            </span>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="auditLogsTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>{{ __('dashboard.activity_time') }}</th>
                            <th>{{ __('dashboard.activity_user') }}</th>
                            <th>{{ __('dashboard.activity_action') }}</th>
                            <th>{{ __('dashboard.activity_desc') }}</th>
                            <th>Model / Target</th>
                            <th>IP Address</th>
                            <th class="pe-4">Metadata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td class="ps-4 text-muted small">#{{ $log->id }}</td>
                                <td class="text-nowrap text-muted small">
                                    {{ $log->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="fw-semibold">
                                    @if($log->user)
                                        <span class="badge badge-role-{{ $log->user->roles->first()?->slug ?? 'devotee' }} me-1">
                                            {{ $log->user->primary_role_title }}
                                        </span>
                                        {{ $log->user->name }}
                                    @else
                                        <span class="text-muted fst-italic">System</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td>{{ $log->description }}</td>
                                <td class="small text-muted">
                                    {{ $log->model_type ? class_basename($log->model_type) . ' #' . $log->model_id : '—' }}
                                </td>
                                <td class="text-muted small">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                                <td class="pe-4 small text-muted">
                                    @if($log->properties)
                                        <code>{{ json_encode($log->properties) }}</code>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="auditLogsNativePagination">
                @if($logs->hasPages())
                    <div class="p-3 border-top">
                        {{ $logs->links() }}
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
        var auditLogsTable = TempleUI.initDataTable('#auditLogsTable', {
            serverSide: true,
            processing: true,
            deferLoading: {{ $logs->total() }},
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'desc']],
            ajax: {
                url: "{{ route('admin.audit-logs') }}"
            },
            columns: [
                { data: 0, name: 'id', className: 'ps-4' },
                { data: 1, name: 'created_at' },
                { data: 2, name: 'user', orderable: false },
                { data: 3, name: 'action' },
                { data: 4, name: 'description' },
                { data: 5, name: 'model_type' },
                { data: 6, name: 'ip_address' },
                { data: 7, name: 'metadata', orderable: false, className: 'pe-4' }
            ]
        });

        // Hide native pagination once DataTables handles it
        $('#auditLogsNativePagination').hide();
    });
</script>
@endpush
