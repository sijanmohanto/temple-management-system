@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', __('portal.public.financial_report_details') . ' — ' . $event->name . ' (' . $event->event_year . ') — ' . __('common.app_name'))

@section('content')
<div class="py-2">
    <!-- Breadcrumb & Back Link -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small bg-white p-2 px-3 rounded-3 shadow-sm border">
                <li class="breadcrumb-item">
                    <a href="{{ auth()->check() ? route('devotee.dashboard') : route('home') }}" class="text-decoration-none text-muted">
                        <i class="bi bi-house-door me-1"></i> {{ auth()->check() ? __('devotee.my_dashboard') : __('portal.public.home') }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('public.previous-events.index') }}" class="text-decoration-none text-muted">
                        {{ __('portal.public.previous_events_reports') }}
                    </a>
                </li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                    {{ $event->name }}
                </li>
            </ol>
        </nav>

        <a href="{{ route('public.previous-events.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> {{ __('portal.actions.back_to_list') }}
        </a>
    </div>

    <!-- Event Showcase Header Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
        <!-- Banner Image (Uploaded or Themed Deity Image) -->
        <div class="position-relative" style="max-height: 400px; overflow: hidden; background: #1e1b18;">
            <img src="{{ $event->featured_image_url }}" alt="{{ $event->name }}" class="w-100" style="max-height: 400px; object-fit: cover; object-position: center;">
            <div class="position-absolute top-0 end-0 m-3">
                <span class="badge bg-dark bg-opacity-75 text-warning border border-warning border-opacity-50 px-3 py-2 fs-6 shadow-sm">
                    {{ $event->event_year }}
                </span>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            <!-- Badges & Title -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                @if($event->status === 'closed')
                    <span class="badge bg-secondary px-3 py-2 fs-6">
                        <i class="bi bi-check-circle me-1 small"></i> {{ __('portal.public.closed_badge') }}
                    </span>
                @elseif($event->status === 'closing')
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6">
                        <i class="bi bi-clock-history me-1 small"></i> {{ __('events.statuses.closing') }}
                    </span>
                @else
                    <span class="badge bg-info text-dark px-3 py-2 fs-6">
                        <i class="bi bi-calendar-event me-1 small"></i> {{ $event->status_label }}
                    </span>
                @endif

                <span class="badge bg-light text-dark border px-3 py-2 fs-6">
                    <i class="bi bi-tag me-1"></i> {{ $event->category?->name ?? __('events.event') }}
                </span>

                <span class="badge bg-light text-dark border px-3 py-2 fs-6">
                    <i class="bi bi-calendar-check me-1"></i> {{ __('portal.public.financial_year') }}: {{ $event->event_year }}
                </span>

                @if($documents->isNotEmpty())
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                        <i class="bi bi-patch-check-fill me-1"></i> {{ __('portal.public.official_report_badge') }}
                    </span>
                @endif

                @if($event->settlement && $event->settlement->isCompleted())
                    <span class="badge bg-success px-3 py-2 fs-6">
                        <i class="bi bi-shield-check me-1"></i> {{ __('portal.public.settled_badge') }}
                    </span>
                @endif
            </div>

            <h1 class="fw-bold text-dark mb-3">{{ $event->name }}</h1>

            <!-- Dates & Venue -->
            <div class="row g-3 text-muted small mb-4">
                <div class="col-md-6 d-flex align-items-center gap-2">
                    <div class="bg-light p-2 rounded border">
                        <i class="bi bi-calendar-range text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark">{{ __('portal.public.event_dates') }}</div>
                        <div>{{ $event->start_date?->format('d M Y') }} — {{ $event->end_date?->format('d M Y') }}</div>
                    </div>
                </div>
                @if($event->venue)
                    <div class="col-md-6 d-flex align-items-center gap-2">
                        <div class="bg-light p-2 rounded border">
                            <i class="bi bi-geo-alt text-danger fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-semibold text-dark">{{ __('portal.public.venue') }}</div>
                            <div>{{ $event->venue }}</div>
                        </div>
                    </div>
                @endif
            </div>

            @if($event->short_description || $event->description)
                <p class="text-secondary mb-0">
                    {{ $event->short_description ?: $event->description }}
                </p>
            @endif
        </div>
    </div>

    <!-- Dual Source Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-3 shadow-sm border" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active px-4 py-2 fw-semibold" id="tab-documents-btn" data-bs-toggle="pill" data-bs-target="#tab-documents" type="button" role="tab" aria-controls="tab-documents" aria-selected="true">
                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>{{ __('portal.public.audited_statements_tab') }}
                <span class="badge {{ $documents->count() > 0 ? 'bg-danger text-white' : 'bg-secondary text-white' }} rounded-pill ms-2">
                    {{ $documents->count() }}
                </span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 fw-semibold" id="tab-system-btn" data-bs-toggle="pill" data-bs-target="#tab-system" type="button" role="tab" aria-controls="tab-system" aria-selected="false">
                <i class="bi bi-graph-up-arrow me-2 text-primary"></i>{{ __('portal.public.system_financial_summary') }}
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content" id="reportTabsContent">
        <!-- Tab 1 (Primary): Audited Statements & Official Documents -->
        <div class="tab-pane fade show active" id="tab-documents" role="tabpanel" aria-labelledby="tab-documents-btn">
            {{-- Official Notice --}}
            <div class="alert alert-light border shadow-sm rounded-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-success fs-4"></i>
                <div>
                    <strong class="text-dark">{{ __('portal.public.official_closing_report_title') }}:</strong>
                    <span class="text-secondary small ms-1">{{ __('portal.public.historical_event_notice') }}</span>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="bi bi-file-earmark-check text-success me-2"></i>{{ __('portal.public.audited_statements_tab') }}
                        </h5>
                        <p class="text-muted small mb-0">{{ __('portal.public.audited_statements_help') }}</p>
                    </div>
                    <span class="badge bg-light text-dark border px-3 py-2">
                        {{ trans_choice('portal.public.published_documents_count', $documents->count(), ['count' => $documents->count()]) }}
                    </span>
                </div>

                <div class="card-body p-0">
                    @if($documents->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-3">{{ __('portal.public.document_title') }}</th>
                                        <th>{{ __('portal.public.report_category') }}</th>
                                        <th>{{ __('portal.public.financial_year') }}</th>
                                        <th>{{ __('portal.public.file_format') }}</th>
                                        <th>{{ __('portal.public.published_date') }}</th>
                                        <th class="text-end pe-3">{{ __('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($documents as $doc)
                                        @php
                                            $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION));
                                            $canPreview = in_array($doc->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
                                            $previewUrl = route('public.previous-events.document.preview', [$event->slug, $doc->uuid]);
                                            $downloadUrl = route('public.previous-events.document.download', [$event->slug, $doc->uuid]);
                                        @endphp
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center">
                                                    @if($ext === 'pdf')
                                                        <i class="bi bi-file-earmark-pdf text-danger fs-3 me-2"></i>
                                                    @elseif(in_array($ext, ['xlsx', 'xls', 'csv']))
                                                        <i class="bi bi-file-earmark-excel text-success fs-3 me-2"></i>
                                                    @elseif(in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                                                        <i class="bi bi-file-earmark-image text-primary fs-3 me-2"></i>
                                                    @else
                                                        <i class="bi bi-file-earmark-text text-secondary fs-3 me-2"></i>
                                                    @endif
                                                    <div>
                                                        <div class="fw-bold text-dark">{{ $doc->title }}</div>
                                                        @if($doc->description)
                                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($doc->description, 60) }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border">
                                                    {{ $doc->report_category_name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="bi bi-calendar-event me-1"></i>{{ $doc->financial_year ?: $event->event_year }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border text-uppercase">{{ $ext }}</span>
                                                <small class="text-muted ms-1">{{ $doc->formatted_file_size }}</small>
                                            </td>
                                            <td class="text-muted small">
                                                {{ $doc->published_at?->format('d M Y') ?? '—' }}
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="btn-group btn-group-sm">
                                                    @if($canPreview)
                                                        <button type="button" class="btn btn-outline-primary btn-preview-doc"
                                                                data-bs-toggle="modal" data-bs-target="#documentPreviewModal"
                                                                data-preview-url="{{ $previewUrl }}"
                                                                data-download-url="{{ $downloadUrl }}"
                                                                data-doc-title="{{ $doc->title }}"
                                                                title="{{ __('portal.public.preview_document') }}">
                                                            <i class="bi bi-eye me-1"></i> {{ __('portal.public.preview_document') }}
                                                        </button>
                                                    @endif
                                                    <a href="{{ $downloadUrl }}" class="btn {{ in_array($ext, ['xlsx', 'xls', 'csv']) ? 'btn-success' : 'btn-outline-secondary' }}" title="{{ __('portal.public.download_document') }}">
                                                        <i class="bi bi-download me-1"></i> {{ __('portal.public.download_document') }}
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-folder-x fs-1 opacity-50 mb-3 d-block"></i>
                            <h5 class="fw-bold text-dark mb-2">{{ __('portal.public.no_published_documents') }}</h5>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Embedded In-Page PDF Viewer for Primary PDF Report --}}
            @php
                $firstPdfDoc = $documents->first(fn($d) => $d->mime_type === 'application/pdf');
            @endphp
            @if($firstPdfDoc)
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-pdf text-danger fs-4"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">{{ $firstPdfDoc->title }}</h6>
                                <small class="text-muted">{{ __('portal.public.embedded_report_preview') }} &bull; {{ $firstPdfDoc->formatted_file_size }}</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('public.previous-events.document.preview', [$event->slug, $firstPdfDoc->uuid]) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('portal.public.open_in_new_tab') }}
                            </a>
                            <a href="{{ route('public.previous-events.document.download', [$event->slug, $firstPdfDoc->uuid]) }}" class="btn btn-sm temple-btn-maroon">
                                <i class="bi bi-download me-1"></i> {{ __('portal.public.download_document') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0" style="background-color: #525659;">
                        <iframe src="{{ route('public.previous-events.document.preview', [$event->slug, $firstPdfDoc->uuid]) }}" class="w-100" style="height: 650px; border: none;"></iframe>
                    </div>
                </div>
            @endif
        </div>

        <!-- Tab 2: System-Generated Financial Summary (Double-Entry Verification) -->
        <div class="tab-pane fade" id="tab-system" role="tabpanel" aria-labelledby="tab-system-btn">
            @if($hasLedgerRecords)
                <!-- Ledger Notice -->
                <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                    <span class="small">{{ __('portal.public.system_financial_summary_help') }}</span>
                </div>

                <!-- 4 Metrics Cards -->
                <div class="row g-3 mb-4">
                    <!-- Total Income -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted fw-semibold text-uppercase">{{ __('portal.public.total_income') }}</span>
                                    <span class="badge bg-success-subtle text-success p-2 rounded-circle">
                                        <i class="bi bi-cash-coin fs-6"></i>
                                    </span>
                                </div>
                                <h3 class="fw-bold text-success mb-0">৳{{ number_format($incomeExpenseStatement['total_income'], 2) }}</h3>
                                <small class="text-muted">{{ __('portal.public.verified_collection_badge') }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Total Expenses -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-danger">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted fw-semibold text-uppercase">{{ __('portal.public.total_expenses') }}</span>
                                    <span class="badge bg-danger-subtle text-danger p-2 rounded-circle">
                                        <i class="bi bi-receipt fs-6"></i>
                                    </span>
                                </div>
                                <h3 class="fw-bold text-danger mb-0">৳{{ number_format($incomeExpenseStatement['total_expense'], 2) }}</h3>
                                <small class="text-muted">{{ __('expenses.approved_expenses') }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Net Surplus / Deficit -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 {{ $incomeExpenseStatement['net_surplus'] >= 0 ? 'border-primary' : 'border-danger' }}">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted fw-semibold text-uppercase">{{ __('portal.public.net_surplus_deficit') }}</span>
                                    <span class="badge {{ $incomeExpenseStatement['net_surplus'] >= 0 ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger' }} p-2 rounded-circle">
                                        <i class="bi {{ $incomeExpenseStatement['net_surplus'] >= 0 ? 'bi-graph-up' : 'bi-graph-down' }} fs-6"></i>
                                    </span>
                                </div>
                                <h3 class="fw-bold {{ $incomeExpenseStatement['net_surplus'] >= 0 ? 'text-primary' : 'text-danger' }} mb-0">
                                    ৳{{ number_format(abs($incomeExpenseStatement['net_surplus']), 2) }}
                                </h3>
                                <div class="mt-1">
                                    @if($incomeExpenseStatement['net_surplus'] > 0)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle small">{{ __('portal.public.surplus') }}</span>
                                    @elseif($incomeExpenseStatement['net_surplus'] < 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">{{ __('portal.public.deficit') }}</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border small">{{ __('portal.public.balanced') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Outstanding Due -->
                    <div class="col-xl-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted fw-semibold text-uppercase">{{ __('portal.public.total_due') }}</span>
                                    <span class="badge bg-warning-subtle text-warning p-2 rounded-circle">
                                        <i class="bi bi-hourglass-split fs-6"></i>
                                    </span>
                                </div>
                                <h3 class="fw-bold text-dark mb-0">৳{{ number_format($collectionMetrics['outstanding_pledges'], 2) }}</h3>
                                <small class="text-muted">{{ __('portal.public.remaining_due_badge') }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Category Breakdowns (Income & Expense) -->
                <div class="row g-4 mb-4">
                    <!-- Income Breakdown -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-plus-circle text-success me-2"></i>{{ __('portal.public.income_breakdown') }}
                                </h5>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    ৳{{ number_format($incomeExpenseStatement['total_income'], 2) }}
                                </span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light small">
                                            <tr>
                                                <th class="ps-3">{{ __('portal.public.account_code') }}</th>
                                                <th>{{ __('portal.public.account_category') }}</th>
                                                <th class="text-end">{{ __('portal.public.amount') }}</th>
                                                <th class="text-end pe-3">{{ __('portal.public.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($incomeExpenseStatement['incomes'] as $row)
                                                @php
                                                    $pct = $incomeExpenseStatement['total_income'] > 0
                                                        ? round(($row['amount'] / $incomeExpenseStatement['total_income']) * 100, 1)
                                                        : 0;
                                                @endphp
                                                <tr>
                                                    <td class="ps-3"><span class="badge bg-light text-dark border">{{ $row['code'] }}</span></td>
                                                    <td class="fw-semibold text-dark">{{ app()->getLocale() === 'bn' && !empty($row['name_bn']) ? $row['name_bn'] : $row['name_en'] }}</td>
                                                    <td class="text-end fw-bold text-success">৳{{ number_format($row['amount'], 2) }}</td>
                                                    <td class="text-end pe-3 text-muted small">{{ $pct }}%</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted small">
                                                        {{ __('reports.no_data') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if(!empty($incomeExpenseStatement['incomes']))
                                            <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td colspan="2" class="ps-3">{{ __('portal.public.total') }}</td>
                                                    <td class="text-end text-success">৳{{ number_format($incomeExpenseStatement['total_income'], 2) }}</td>
                                                    <td class="text-end pe-3">100%</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expense Breakdown -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-dash-circle text-danger me-2"></i>{{ __('portal.public.expense_breakdown') }}
                                </h5>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    ৳{{ number_format($incomeExpenseStatement['total_expense'], 2) }}
                                </span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light small">
                                            <tr>
                                                <th class="ps-3">{{ __('portal.public.account_code') }}</th>
                                                <th>{{ __('portal.public.account_category') }}</th>
                                                <th class="text-end">{{ __('portal.public.amount') }}</th>
                                                <th class="text-end pe-3">{{ __('portal.public.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($incomeExpenseStatement['expenses'] as $row)
                                                @php
                                                    $pct = $incomeExpenseStatement['total_expense'] > 0
                                                        ? round(($row['amount'] / $incomeExpenseStatement['total_expense']) * 100, 1)
                                                        : 0;
                                                @endphp
                                                <tr>
                                                    <td class="ps-3"><span class="badge bg-light text-dark border">{{ $row['code'] }}</span></td>
                                                    <td class="fw-semibold text-dark">{{ app()->getLocale() === 'bn' && !empty($row['name_bn']) ? $row['name_bn'] : $row['name_en'] }}</td>
                                                    <td class="text-end fw-bold text-danger">৳{{ number_format($row['amount'], 2) }}</td>
                                                    <td class="text-end pe-3 text-muted small">{{ $pct }}%</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted small">
                                                        {{ __('reports.no_data') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if(!empty($incomeExpenseStatement['expenses']))
                                            <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td colspan="2" class="ps-3">{{ __('portal.public.total') }}</td>
                                                    <td class="text-end text-danger">৳{{ number_format($incomeExpenseStatement['total_expense'], 2) }}</td>
                                                    <td class="text-end pe-3">100%</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Settlement Card (if settled) -->
                @if($event->settlement && $event->settlement->isCompleted())
                    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white border-start border-4 border-success">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                <h5 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-shield-check text-success me-2"></i>{{ __('portal.public.event_settlement_title') }}
                                </h5>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                    {{ __('portal.public.settled_badge') }}
                                </span>
                            </div>
                            <p class="text-secondary small mb-3">
                                {{ __('portal.public.settlement_notice') }}
                            </p>
                            <div class="row g-3 bg-light p-3 rounded-3 border">
                                <div class="col-md-3 col-sm-6">
                                    <div class="text-muted small">{{ __('portal.public.settlement_number') }}</div>
                                    <div class="fw-bold text-dark">{{ $event->settlement->settlement_number }}</div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="text-muted small">{{ __('portal.public.settlement_date') }}</div>
                                    <div class="fw-bold text-dark">{{ $event->settlement->settlement_date?->format('d M Y') }}</div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="text-muted small">{{ __('portal.public.settlement_transferred_amount') }}</div>
                                    <div class="fw-bold text-success fs-6">৳{{ number_format($event->settlement->surplus_amount, 2) }}</div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="text-muted small">{{ __('finance.destination_account') }}</div>
                                    <div class="fw-bold text-dark">{{ $event->settlement->destinationAccount?->name ?? 'General Temple Fund' }}</div>
                                </div>
                                @if($event->settlement->notes)
                                    <div class="col-12 border-top pt-2 mt-2">
                                        <div class="text-muted small">{{ __('portal.public.settlement_notes') }}</div>
                                        <div class="text-secondary small">{{ $event->settlement->notes }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <!-- Informative Unrecorded State -->
                <div class="card border-0 bg-white shadow-sm p-4 text-center rounded-3 mb-4">
                    <div class="text-muted mb-2">
                        <i class="bi bi-info-circle fs-2 text-primary opacity-50"></i>
                    </div>
                    <h5 class="text-dark fw-bold mb-2">{{ __('portal.public.no_ledger_records_title') }}</h5>
                    <p class="text-muted small mb-0 mx-auto" style="max-width: 600px;">
                        {{ __('portal.public.no_ledger_records_notice') }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Document Preview Modal -->
<div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-labelledby="documentPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0 overflow-hidden">
            <div class="modal-header bg-light py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="documentPreviewModalLabel">
                    <i class="bi bi-file-earmark-text me-2 text-primary"></i><span id="previewModalDocTitle">{{ __('portal.public.pdf_preview_title') }}</span>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="previewModalOpenTab" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('portal.public.open_in_new_tab') }}
                    </a>
                    <a href="#" id="previewModalDownload" class="btn btn-sm temple-btn-maroon">
                        <i class="bi bi-download me-1"></i> {{ __('portal.public.download_document') }}
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0" style="min-height: 520px; max-height: 80vh; background-color: #525659;">
                <iframe id="previewIframe" src="" class="w-100" style="height: 75vh; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const previewModal = document.getElementById('documentPreviewModal');
    if (previewModal) {
        const previewIframe = document.getElementById('previewIframe');
        const modalTitle = document.getElementById('previewModalDocTitle');
        const openTabBtn = document.getElementById('previewModalOpenTab');
        const downloadBtn = document.getElementById('previewModalDownload');

        document.querySelectorAll('.btn-preview-doc').forEach(button => {
            button.addEventListener('click', function () {
                const previewUrl = this.getAttribute('data-preview-url');
                const downloadUrl = this.getAttribute('data-download-url');
                const docTitle = this.getAttribute('data-doc-title');

                if (modalTitle) modalTitle.textContent = docTitle;
                if (openTabBtn) openTabBtn.setAttribute('href', previewUrl);
                if (downloadBtn) downloadBtn.setAttribute('href', downloadUrl);
                if (previewIframe) previewIframe.setAttribute('src', previewUrl);
            });
        });

        previewModal.addEventListener('hidden.bs.modal', function () {
            if (previewIframe) previewIframe.setAttribute('src', '');
        });
    }
});
</script>
@endpush
