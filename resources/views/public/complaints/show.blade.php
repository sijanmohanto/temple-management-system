@extends('layouts.public')

@section('title', $complaint->tracking_number . ' — ' . __('portal.public.my_complaints') . ' — ' . __('common.app_name'))

@section('content')
<div class="py-2">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 small bg-white p-2 px-3 rounded-3 shadow-sm border">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> {{ __('portal.public.home') }}
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('public.complaints.index') }}" class="text-decoration-none text-muted">
                    {{ __('portal.public.my_complaints') }}
                </a>
            </li>
            <li class="breadcrumb-item active font-monospace text-dark fw-semibold" aria-current="page">
                {{ $complaint->tracking_number }}
            </li>
        </ol>
    </nav>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Header Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background: linear-gradient(135deg, #4a154b 0%, #611f69 50%, #0d6efd 100%);">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-light text-dark px-3 py-1 font-monospace fs-6">{{ $complaint->tracking_number }}</span>
                        @if($complaint->status === 'submitted')
                            <span class="badge bg-info text-dark px-3 py-1">
                                <i class="bi bi-send me-1"></i> {{ __('portal.complaint_statuses.submitted') }}
                            </span>
                        @elseif($complaint->status === 'under_review')
                            <span class="badge bg-warning text-dark px-3 py-1">
                                <i class="bi bi-hourglass-split me-1"></i> {{ __('portal.complaint_statuses.under_review') }}
                            </span>
                        @elseif($complaint->status === 'replied')
                            <span class="badge bg-primary text-white px-3 py-1">
                                <i class="bi bi-chat-dots me-1"></i> {{ __('portal.complaint_statuses.replied') }}
                            </span>
                        @elseif($complaint->status === 'resolved')
                            <span class="badge bg-success text-white px-3 py-1">
                                <i class="bi bi-check-circle-fill me-1"></i> {{ __('portal.complaint_statuses.resolved') }}
                            </span>
                        @elseif($complaint->status === 'rejected')
                            <span class="badge bg-danger text-white px-3 py-1">
                                <i class="bi bi-x-circle-fill me-1"></i> {{ __('portal.complaint_statuses.rejected') }}
                            </span>
                        @elseif($complaint->status === 'closed')
                            <span class="badge bg-secondary text-white px-3 py-1">
                                <i class="bi bi-lock me-1"></i> {{ __('portal.complaint_statuses.closed') }}
                            </span>
                        @endif
                    </div>
                    <h1 class="h3 fw-bold mb-1">{{ $complaint->subject }}</h1>
                    <p class="text-white-50 mb-0 small">
                        {{ __('portal.history_payment_date') }}: {{ $complaint->created_at->format('d M Y, h:i A') }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('public.complaints.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> {{ __('portal.public.back_to_complaints') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left: Complaint Details -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-info-circle text-primary me-2"></i> {{ __('portal.complaints_module.title') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('portal.public.complaint_reference') }}:</span>
                            <span class="font-monospace fw-bold text-dark">{{ $complaint->tracking_number }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('portal.public.complaint_type') }}:</span>
                            @php
                                $catKey = 'portal.complaint_categories.' . $complaint->category;
                                $catLabel = \Illuminate\Support\Facades\Lang::has($catKey) ? __($catKey) : ucwords(str_replace('_', ' ', $complaint->category));
                            @endphp
                            <span class="badge bg-light text-dark border">{{ $catLabel }}</span>
                        </li>
                        @if($complaint->event)
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">{{ __('events.event') }}:</span>
                                <span class="fw-semibold text-dark">{{ $complaint->event->name }} ({{ $complaint->event->event_year }})</span>
                            </li>
                        @endif
                        @if($complaint->payment)
                            <li class="list-group-item px-0">
                                <span class="text-muted d-block mb-1">{{ __('portal.public.related_payment') }}:</span>
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <a href="{{ route('public.payments.show', $complaint->payment->payment_code) }}" class="font-monospace fw-bold text-primary text-decoration-none">
                                            {{ $complaint->payment->payment_code }}
                                        </a>
                                        <span class="font-monospace fw-bold text-success">৳{{ number_format($complaint->payment->amount, 2) }}</span>
                                    </div>
                                    <div class="small text-muted">
                                        <span>{{ strtoupper($complaint->payment->payment_method) }}</span>
                                        @if($complaint->payment->payment_date)
                                            • {{ $complaint->payment->payment_date->format('d M Y') }}
                                        @endif
                                        • <span class="badge bg-light text-dark border">{{ strtoupper($complaint->payment->status) }}</span>
                                    </div>
                                </div>
                            </li>
                        @endif
                    </ul>

                    <div class="mt-4 pt-3 border-top">
                        <label class="small text-muted fw-bold mb-2 d-block">{{ __('portal.public.original_inquiry') }}:</label>
                        <div class="p-3 bg-light rounded-3 border small text-dark" style="white-space: pre-line;">{{ $complaint->description }}</div>
                    </div>

                    @if($complaint->attachment_path)
                        <div class="mt-4 pt-3 border-top">
                            <label class="small text-muted fw-bold mb-2 d-block">{{ __('portal.complaints_module.attachment') }}:</label>
                            <a href="{{ route('public.complaints.attachment', $complaint->tracking_number) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i> {{ __('portal.public.download_attachment') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Disclaimer Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-light">
                <div class="card-body p-3 small text-muted">
                    <i class="bi bi-shield-check text-primary me-1"></i>
                    {{ __('portal.public.financial_disclaimer') }}
                </div>
            </div>
        </div>

        <!-- Right: Activities & Replies Timeline -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-chat-left-dots text-primary me-2"></i> {{ __('portal.public.timeline') }}
                    </h5>
                </div>
                <div class="card-body p-4">
                    @forelse($activities as $act)
                        <div class="d-flex gap-3 mb-4">
                            <!-- Icon Indicator -->
                            <div class="flex-shrink-0">
                                @if($act->activity_type === 'submission')
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                @elseif($act->activity_type === 'reply')
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="bi bi-reply-fill"></i>
                                    </div>
                                @elseif($act->new_status === 'resolved')
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                @elseif($act->new_status === 'rejected')
                                    <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="bi bi-x-lg"></i>
                                    </div>
                                @elseif($act->new_status === 'under_review')
                                    <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                @else
                                    <div class="rounded-circle bg-light text-dark border d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="flex-grow-1">
                                <div class="card border rounded-3 shadow-none {{ $act->activity_type === 'reply' ? 'border-primary-subtle bg-primary-subtle bg-opacity-10' : '' }}">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                @if($act->activity_type === 'submission')
                                                    <strong class="text-dark small">{{ __('portal.complaint_statuses.submitted') }}</strong>
                                                @elseif($act->activity_type === 'reply')
                                                    <strong class="text-primary small">
                                                        <i class="bi bi-person-badge me-1"></i> {{ __('portal.complaints_module.admin_response') }}
                                                    </strong>
                                                @elseif($act->activity_type === 'status_change')
                                                    <strong class="text-dark small">
                                                        {{ __('portal.public.status_history') }}:
                                                        <span class="badge bg-light text-dark border ms-1">{{ strtoupper($act->new_status) }}</span>
                                                    </strong>
                                                @else
                                                    <strong class="text-dark small">{{ ucwords(str_replace('_', ' ', $act->activity_type)) }}</strong>
                                                @endif
                                            </div>
                                            <small class="text-muted font-monospace">{{ $act->created_at->format('d M Y, h:i A') }}</small>
                                        </div>

                                        @if($act->content)
                                            <div class="small text-dark mt-2" style="white-space: pre-line;">{{ $act->content }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-clock-history fs-3 d-block mb-2 text-secondary opacity-50"></i>
                            <p class="small mb-0">{{ __('portal.public.no_activities_yet') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
