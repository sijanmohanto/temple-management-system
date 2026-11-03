@extends('layouts.app')

@section('title', $complaint->tracking_number . ' - ' . $complaint->subject)

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('portal.complaints.index') }}">{{ __('portal.complaints') }}</a></li>
                    <li class="breadcrumb-item active">{{ $complaint->tracking_number }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ $complaint->tracking_number }}</h1>
            <p class="text-muted small mb-0">{{ $complaint->subject }} &bull; {{ $complaint->complainant_name }}</p>
        </div>
        <div>
            <a href="{{ route('portal.complaints.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('portal.actions.back_to_list') }}
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main Complaint Details --}}
        <div class="col-lg-8">
            {{-- Complaint Overview Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary">{{ $complaint->category_name }}</span>
                        @php
                            $pBadge = match ($complaint->priority) {
                                \App\Models\Complaint::PRIORITY_URGENT => 'bg-danger',
                                \App\Models\Complaint::PRIORITY_HIGH => 'bg-warning text-dark',
                                \App\Models\Complaint::PRIORITY_MEDIUM => 'bg-info text-dark',
                                default => 'bg-light text-dark border',
                            };
                        @endphp
                        <span class="badge {{ $pBadge }}">{{ $complaint->priority_name }}</span>
                    </div>
                    <div>
                        <span class="badge {{ $complaint->status === 'resolved' ? 'bg-success' : ($complaint->status === 'rejected' ? 'bg-danger' : ($complaint->status === 'closed' ? 'bg-dark' : 'bg-warning text-dark')) }} fs-6">
                            {{ $complaint->status_name }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-3">{{ $complaint->subject }}</h5>
                    <div class="p-3 bg-light rounded border mb-4">
                        <p class="mb-0 text-secondary" style="white-space: pre-wrap;">{{ $complaint->description }}</p>
                    </div>

                    {{-- Attachment Preview if present --}}
                    @if($complaint->attachment_path)
                        <div class="p-3 bg-white rounded border d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-paperclip fs-3 text-primary me-2"></i>
                                <div>
                                    <div class="fw-semibold">{{ __('portal.complaints_module.attachment') }}</div>
                                    <div class="small text-muted">Uploaded supporting document / screenshot</div>
                                </div>
                            </div>
                            <div>
                                <a href="{{ $complaint->attachment_url }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('portal.actions.preview') }}
                                </a>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.complaints_module.complainant') }}</label>
                            <div class="fw-semibold">{{ $complaint->complainant_name }}</div>
                            <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $complaint->complainant_phone }}</div>
                            @if($complaint->complainant_email)
                                <div class="small text-muted"><i class="bi bi-envelope me-1"></i>{{ $complaint->complainant_email }}</div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.complaints_module.linked_person') }}</label>
                            @if($complaint->person)
                                <div>
                                    <a href="{{ route('people.show', $complaint->person) }}" class="fw-semibold text-decoration-none">
                                        {{ $complaint->person->name_en }} ({{ $complaint->person->person_code }})
                                    </a>
                                </div>
                            @else
                                <div class="text-muted small mb-2">Guest / Unlinked</div>
                                @if(auth()->user()->can('manage_complaints'))
                                    <form action="{{ route('portal.complaints.link-person', $complaint) }}" method="POST" class="d-flex gap-2">
                                        @csrf
                                        <select name="person_id" class="form-select form-select-sm select2-enable" required>
                                            <option value="">-- {{ __('portal.payments.select_person') }} --</option>
                                            @foreach($suggestedPersons as $sp)
                                                <option value="{{ $sp->id }}">{{ $sp->name_en }} ({{ $sp->person_code }}) - {{ $sp->phone }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Link</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Related Context Card (Event or Payment) --}}
            @if($complaint->event || $complaint->payment)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="card-title mb-0 fw-semibold">Related Record Context</h6>
                    </div>
                    <div class="card-body">
                        @if($complaint->event)
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-2">
                                <div>
                                    <span class="badge bg-secondary me-1">Event</span>
                                    <a href="{{ route('portal.events.show', $complaint->event) }}" class="fw-bold text-decoration-none">
                                        {{ $complaint->event->name }}
                                    </a>
                                    <div class="small text-muted">{{ $complaint->event->event_code }} &bull; {{ $complaint->event->event_year }}</div>
                                </div>
                            </div>
                        @endif

                        @if($complaint->payment)
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-2">
                                <div>
                                    <span class="badge bg-primary me-1">Payment</span>
                                    <a href="{{ route('portal.payments.show', $complaint->payment) }}" class="fw-bold text-decoration-none">
                                        <code>{{ $complaint->payment->payment_code }}</code>
                                    </a>
                                    <div class="small text-muted">Amount: ৳ {{ number_format((float) $complaint->payment->amount, 2) }} &bull; TrxID: {{ $complaint->payment->transaction_reference }}</div>
                                </div>
                            </div>
                        @endif

                        <div class="alert alert-info py-2 px-3 small mb-0 mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Financial Integrity:</strong> Inquiries and complaints never alter financial ledgers automatically. Any refund or adjustment must go through authorized voucher reversal procedures.
                        </div>
                    </div>
                </div>
            @endif

            {{-- Official Admin Response Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-chat-dots me-1 text-primary"></i> {{ __('portal.complaints_module.admin_response') }}
                    </h6>
                </div>
                <div class="card-body">
                    @if($complaint->admin_response)
                        <div class="p-3 bg-success-subtle rounded border border-success-subtle mb-3">
                            <p class="mb-0" style="white-space: pre-wrap;">{{ $complaint->admin_response }}</p>
                        </div>
                    @else
                        <p class="text-muted small mb-3">No official response has been dispatched to the complainant yet.</p>
                    @endif

                    @if(! in_array($complaint->status, [\App\Models\Complaint::STATUS_RESOLVED, \App\Models\Complaint::STATUS_CLOSED]) && (auth()->user()->can('respond_complaints') || auth()->user()->can('manage_complaints')))
                        <form action="{{ route('portal.complaints.respond', $complaint) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="admin_response" class="form-label small fw-bold">
                                    {{ __('portal.complaints_module.admin_response') }}
                                </label>
                                <textarea name="admin_response" id="admin_response" rows="3" class="form-control" required placeholder="Write official message to complainant...">{{ old('admin_response', $complaint->admin_response) }}</textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="update_status" id="update_status_res" value="resolved">
                                    <label class="form-check-label small fw-semibold" for="update_status_res">
                                        Mark complaint as Resolved with this response
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-send me-1"></i> {{ __('portal.complaints_module.respond') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Private Internal Staff Notes Thread --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-lock me-1 text-secondary"></i> {{ __('portal.complaints_module.internal_notes') }}
                    </h6>
                </div>
                <div class="card-body">
                    @if(auth()->user()->can('manage_complaints'))
                        <form action="{{ route('portal.complaints.add-note', $complaint) }}" method="POST" class="mb-4">
                            @csrf
                            <div class="mb-2">
                                <textarea name="note" id="note" rows="2" class="form-control" required placeholder="Add private note for committee or handling staff..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-plus-circle me-1"></i> {{ __('portal.complaints_module.add_note') }}
                            </button>
                        </form>
                    @endif

                    @if($complaint->internal_notes)
                        <div class="p-3 bg-light rounded border small" style="white-space: pre-wrap; font-family: monospace;">{{ $complaint->internal_notes }}</div>
                    @else
                        <div class="text-muted small">No internal notes added yet.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar: Controls & Audit --}}
        <div class="col-lg-4">
            {{-- Staff Assignment Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('portal.complaints_module.assigned_to') }}</h6>
                </div>
                <div class="card-body">
                    @if(auth()->user()->can('assign_complaints') || auth()->user()->can('manage_complaints'))
                        <form action="{{ route('portal.complaints.assign', $complaint) }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <select name="assigned_to" class="form-select select2-enable" onchange="this.form.submit()">
                                    <option value="">-- {{ __('portal.complaints_module.unassigned') }} --</option>
                                    @foreach($staffUsers as $staff)
                                        <option value="{{ $staff->id }}" {{ $complaint->assigned_to == $staff->id ? 'selected' : '' }}>
                                            {{ $staff->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    @else
                        <div class="fw-semibold">{{ $complaint->assignee?->name ?? __('portal.complaints_module.unassigned') }}</div>
                    @endif
                </div>
            </div>

            {{-- Priority Control Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('portal.complaints_module.priority') }}</h6>
                </div>
                <div class="card-body">
                    @if(auth()->user()->can('manage_complaints'))
                        <form action="{{ route('portal.complaints.update-priority', $complaint) }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <select name="priority" class="form-select" onchange="this.form.submit()">
                                    <option value="low" {{ $complaint->priority === 'low' ? 'selected' : '' }}>{{ __('portal.complaint_priorities.low') }}</option>
                                    <option value="medium" {{ $complaint->priority === 'medium' ? 'selected' : '' }}>{{ __('portal.complaint_priorities.medium') }}</option>
                                    <option value="high" {{ $complaint->priority === 'high' ? 'selected' : '' }}>{{ __('portal.complaint_priorities.high') }}</option>
                                    <option value="urgent" {{ $complaint->priority === 'urgent' ? 'selected' : '' }}>{{ __('portal.complaint_priorities.urgent') }}</option>
                                </select>
                            </div>
                        </form>
                    @else
                        <span class="badge {{ $pBadge }}">{{ $complaint->priority_name }}</span>
                    @endif
                </div>
            </div>

            {{-- Status Resolution Actions --}}
            @if(! in_array($complaint->status, [\App\Models\Complaint::STATUS_RESOLVED, \App\Models\Complaint::STATUS_CLOSED]))
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="card-title mb-0 fw-semibold">{{ __('common.actions') }}</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        @if(auth()->user()->can('resolve_complaints') || auth()->user()->can('manage_complaints'))
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resolveModal">
                                <i class="bi bi-check-circle me-1"></i> {{ __('portal.complaints_module.resolve') }}
                            </button>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle me-1"></i> {{ __('portal.complaints_module.reject') }}
                            </button>
                        @endif
                    </div>
                </div>
            @elseif($complaint->status === \App\Models\Complaint::STATUS_RESOLVED && (auth()->user()->can('resolve_complaints') || auth()->user()->can('manage_complaints')))
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="card-title mb-0 fw-semibold">Closure</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('portal.complaints.close', $complaint) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-dark w-100">
                                <i class="bi bi-lock me-1"></i> {{ __('portal.complaints_module.close') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Audit Details --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('common.audit_information') }}</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Submitted At:</span>
                            <span class="fw-semibold">{{ $complaint->created_at->format('d M Y, h:i A') }}</span>
                        </li>
                        @if($complaint->resolved_at)
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Resolved At:</span>
                                <span class="fw-semibold">{{ $complaint->resolved_at->format('d M Y, h:i A') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Resolved By:</span>
                                <span class="fw-semibold">{{ $complaint->resolver?->name ?? '—' }}</span>
                            </li>
                        @endif
                        @if($complaint->closed_at)
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Closed At:</span>
                                <span class="fw-semibold">{{ $complaint->closed_at->format('d M Y, h:i A') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Closed By:</span>
                                <span class="fw-semibold">{{ $complaint->closer?->name ?? '—' }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals for Resolve and Reject --}}
<div class="modal fade" id="resolveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('portal.complaints.resolve', $complaint) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-check-circle me-1"></i> {{ __('portal.complaints_module.resolve') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="resolve_response" class="form-label small fw-bold">Resolution Note & Response <span class="text-danger">*</span></label>
                        <textarea name="admin_response" id="resolve_response" rows="4" class="form-control" required placeholder="State how the complaint was resolved...">{{ old('admin_response', $complaint->admin_response) }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Resolution</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('portal.complaints.reject', $complaint) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-x-circle me-1"></i> {{ __('portal.complaints_module.reject') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason_text" class="form-label small fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason_text" rows="4" class="form-control" required placeholder="State why this complaint is rejected or invalid..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Complaint</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
