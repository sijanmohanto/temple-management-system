@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', __('portal.public.my_complaints') . ' — ' . __('common.app_name'))

@section('content')
<div class="py-2">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0 small bg-white p-2 px-3 rounded-3 shadow-sm border">
            <li class="breadcrumb-item">
                <a href="{{ auth()->check() ? route('devotee.dashboard') : route('home') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> {{ auth()->check() ? __('devotee.my_dashboard') : __('portal.public.home') }}
                </a>
            </li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                {{ __('portal.public.my_complaints') }}
            </li>
        </ol>
    </nav>

    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background: linear-gradient(135deg, #4a154b 0%, #611f69 50%, #0d6efd 100%);">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="badge bg-light text-dark px-3 py-1 font-monospace mb-2">{{ $donor->person_code }}</span>
                    <h1 class="h2 fw-bold mb-1">{{ __('portal.public.my_complaints') }}</h1>
                    <p class="text-white-50 mb-0 fs-6">
                        {{ app()->getLocale() === 'bn' && !empty($donor->name_bn) ? $donor->name_bn : $donor->name_en }} — {{ __('portal.public.my_complaints_subtitle') }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('public.complaints.create') }}" class="btn btn-warning text-dark fw-semibold">
                        <i class="bi bi-plus-circle me-1"></i> {{ __('portal.public.submit_complaint') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('public.complaints.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="category" class="form-label small fw-bold text-muted mb-1">{{ __('portal.public.complaint_type') }}</label>
                    <select name="category" id="category" class="form-select form-select-sm">
                        <option value="all" {{ request('category') === 'all' || !request('category') ? 'selected' : '' }}>-- {{ __('portal.complaints_module.all_categories') }} --</option>
                        <option value="payment_not_updated" {{ request('category') === 'payment_not_updated' ? 'selected' : '' }}>{{ __('portal.complaint_categories.payment_not_updated') }}</option>
                        <option value="wrong_paid_amount" {{ request('category') === 'wrong_paid_amount' ? 'selected' : '' }}>{{ __('portal.complaint_categories.wrong_paid_amount') }}</option>
                        <option value="wrong_due_amount" {{ request('category') === 'wrong_due_amount' ? 'selected' : '' }}>{{ __('portal.complaint_categories.wrong_due_amount') }}</option>
                        <option value="other" {{ request('category') === 'other' ? 'selected' : '' }}>{{ __('portal.complaint_categories.other') }}</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label small fw-bold text-muted mb-1">{{ __('portal.public.filter_status_label') }}</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>-- {{ __('portal.public.all_statuses') }} --</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>{{ __('portal.complaint_statuses.submitted') }}</option>
                        <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>{{ __('portal.complaint_statuses.under_review') }}</option>
                        <option value="replied" {{ request('status') === 'replied' ? 'selected' : '' }}>{{ __('portal.complaint_statuses.replied') }}</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>{{ __('portal.complaint_statuses.resolved') }}</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('portal.complaint_statuses.rejected') }}</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>{{ __('portal.complaint_statuses.closed') }}</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="search" class="form-label small fw-bold text-muted mb-1">{{ __('common.search') }}</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="{{ __('portal.public.complaint_reference') }}..." value="{{ request('search') }}">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> {{ __('portal.public.filter_button') }}
                    </button>
                    @if(request()->hasAny(['category', 'status', 'search']))
                        <a href="{{ route('public.complaints.index') }}" class="btn btn-outline-secondary btn-sm" title="{{ __('portal.actions.reset') }}">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Complaints List Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-4">{{ __('portal.public.complaint_reference') }}</th>
                        <th>{{ __('portal.public.complaint_type') }}</th>
                        <th>{{ __('portal.public.related_payment') }} / {{ __('events.event') }}</th>
                        <th>{{ __('portal.history_payment_date') }}</th>
                        <th class="text-center">{{ __('portal.public.table_status') }}</th>
                        <th>{{ __('portal.public.latest_reply') }}</th>
                        <th class="text-end pe-4">{{ __('portal.public.table_action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                        <tr>
                            <td class="ps-4">
                                <span class="font-monospace fw-bold text-primary">{{ $complaint->tracking_number }}</span>
                                <div class="small text-muted text-truncate" style="max-width: 200px;">{{ $complaint->subject }}</div>
                            </td>
                            <td>
                                @php
                                    $catKey = 'portal.complaint_categories.' . $complaint->category;
                                    $catLabel = \Illuminate\Support\Facades\Lang::has($catKey) ? __($catKey) : ucwords(str_replace('_', ' ', $complaint->category));
                                @endphp
                                <span class="badge bg-light text-dark border">
                                    {{ $catLabel }}
                                </span>
                            </td>
                            <td>
                                @if($complaint->payment)
                                    <div>
                                        <a href="{{ route('public.payments.show', $complaint->payment->payment_code) }}" class="font-monospace fw-semibold text-decoration-none small">
                                            <i class="bi bi-receipt me-1"></i> {{ $complaint->payment->payment_code }}
                                        </a>
                                        <span class="small text-muted font-monospace">(৳{{ number_format($complaint->payment->amount, 2) }})</span>
                                    </div>
                                @endif
                                @if($complaint->event)
                                    <div class="small text-muted">
                                        <i class="bi bi-calendar-event me-1"></i> {{ $complaint->event->name }}
                                    </div>
                                @elseif(! $complaint->payment)
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-dark small">{{ $complaint->created_at->format('d M Y') }}</span>
                                <div class="small text-muted">{{ $complaint->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="text-center">
                                @if($complaint->status === 'submitted')
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-1">
                                        <i class="bi bi-send me-1"></i> {{ __('portal.complaint_statuses.submitted') }}
                                    </span>
                                @elseif($complaint->status === 'under_review')
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1">
                                        <i class="bi bi-hourglass-split me-1"></i> {{ __('portal.complaint_statuses.under_review') }}
                                    </span>
                                @elseif($complaint->status === 'replied')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
                                        <i class="bi bi-chat-dots me-1"></i> {{ __('portal.complaint_statuses.replied') }}
                                    </span>
                                @elseif($complaint->status === 'resolved')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> {{ __('portal.complaint_statuses.resolved') }}
                                    </span>
                                @elseif($complaint->status === 'rejected')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1">
                                        <i class="bi bi-x-circle-fill me-1"></i> {{ __('portal.complaint_statuses.rejected') }}
                                    </span>
                                @elseif($complaint->status === 'closed')
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary px-3 py-1">
                                        <i class="bi bi-lock me-1"></i> {{ __('portal.complaint_statuses.closed') }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-dark border px-3 py-1">
                                        {{ $complaint->status }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($complaint->admin_response)
                                    <div class="small text-dark text-truncate" style="max-width: 220px;" title="{{ $complaint->admin_response }}">
                                        <i class="bi bi-reply-fill text-primary me-1"></i> {{ $complaint->admin_response }}
                                    </div>
                                    <small class="text-muted d-block">{{ $complaint->updated_at->diffForHumans() }}</small>
                                @else
                                    <span class="small text-muted fst-italic">
                                        <i class="bi bi-clock me-1"></i> {{ __('portal.complaint_statuses.submitted') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('public.complaints.show', $complaint->tracking_number) }}" class="btn btn-sm btn-outline-primary" title="{{ __('portal.public.view_details') }}">
                                    <i class="bi bi-eye me-1"></i> {{ __('portal.actions.view') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-chat-square-text fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                                <p class="mb-3">{{ __('portal.public.no_complaints_found') }}</p>
                                <a href="{{ route('public.complaints.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i> {{ __('portal.public.submit_complaint') }}
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($complaints->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $complaints->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
