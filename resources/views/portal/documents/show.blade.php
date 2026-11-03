@extends('layouts.app')

@section('title', $document->title)

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('nav.dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('portal.documents.index') }}">{{ __('portal.event_documents') }}</a></li>
                    <li class="breadcrumb-item active">{{ $document->title }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ $document->title }}</h1>
            <p class="text-muted small mb-0">{{ $document->event?->name }} &bull; {{ $document->document_type_name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('portal.documents.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('portal.actions.back_to_list') }}
            </a>
            @if(auth()->user()->can('manage_event_documents') || auth()->user()->can('manage_public_documents'))
                <a href="{{ route('portal.documents.edit', $document) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> {{ __('portal.actions.edit') }}
                </a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Document Overview --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">{{ __('portal.documents.document_details') }}</h5>
                    <div>
                        <span class="badge {{ $document->isPublished() ? 'bg-success' : ($document->isUnpublished() ? 'bg-secondary' : 'bg-warning text-dark') }} fs-6">
                            {{ __('portal.status.' . $document->publication_status) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.documents.title_en') }}</label>
                            <div class="fw-semibold fs-6">{{ $document->title_en }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small font-bengali">{{ __('portal.documents.title_bn') }}</label>
                            <div class="fw-semibold fs-6 font-bengali">{{ $document->title_bn ?: '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.documents.event') }}</label>
                            <div>
                                <a href="{{ route('portal.events.show', $document->event_id) }}" class="fw-semibold text-decoration-none">
                                    {{ $document->event?->name }}
                                </a>
                                <span class="badge bg-light text-dark border ms-2">{{ $document->event?->event_code }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">{{ __('portal.documents.type') }}</label>
                            <div>
                                <span class="badge bg-info text-dark">{{ $document->document_type_name }}</span>
                            </div>
                        </div>
                    </div>

                    @if($document->description_en || $document->description_bn)
                        <hr>
                        <div class="row g-3 mb-3">
                            @if($document->description_en)
                                <div class="col-md-6">
                                    <label class="text-muted small">{{ __('portal.documents.description_en') }}</label>
                                    <p class="text-secondary mb-0">{{ $document->description_en }}</p>
                                </div>
                            @endif
                            @if($document->description_bn)
                                <div class="col-md-6">
                                    <label class="text-muted small font-bengali">{{ __('portal.documents.description_bn') }}</label>
                                    <p class="text-secondary font-bengali mb-0">{{ $document->description_bn }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <hr>

                    {{-- File Preview / Actions Area --}}
                    <div class="p-3 bg-light rounded border d-flex flex-wrap justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            @php
                                $ext = strtolower(pathinfo($document->file_name, PATHINFO_EXTENSION));
                            @endphp
                            @if($ext === 'pdf')
                                <i class="bi bi-file-earmark-pdf text-danger fs-1 me-3"></i>
                            @elseif(in_array($ext, ['jpg', 'jpeg', 'png']))
                                <i class="bi bi-file-earmark-image text-primary fs-1 me-3"></i>
                            @else
                                <i class="bi bi-file-earmark-text text-secondary fs-1 me-3"></i>
                            @endif
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $document->file_name }}</h6>
                                <div class="small text-muted">
                                    {{ $document->formatted_file_size }} &bull; {{ $document->mime_type }}
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2 mt-sm-0">
                            <a href="{{ route('portal.documents.download', ['document' => $document, 'preview' => 1]) }}"
                               target="_blank" class="btn btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i> {{ __('portal.actions.preview') }}
                            </a>
                            <a href="{{ route('portal.documents.download', $document) }}" class="btn btn-primary">
                                <i class="bi bi-download me-1"></i> {{ __('portal.actions.download') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Meta & Publication Control Sidebar --}}
        <div class="col-lg-4">
            {{-- Publication Status Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('portal.publication_status') }}</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">{{ __('portal.publication_status') }}</label>
                        <div>
                            @if($document->isPublished())
                                <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>{{ __('portal.status.published') }}</span>
                            @elseif($document->isUnpublished())
                                <span class="badge bg-secondary fs-6"><i class="bi bi-slash-circle me-1"></i>{{ __('portal.status.unpublished') }}</span>
                            @else
                                <span class="badge bg-warning text-dark fs-6"><i class="bi bi-pencil-square me-1"></i>{{ __('portal.status.draft') }}</span>
                            @endif
                        </div>
                    </div>

                    @if(auth()->user()->can('publish_event_documents') || auth()->user()->can('publish_public_documents'))
                        <div class="d-grid gap-2 pt-2">
                            @if($document->isPublished())
                                <form action="{{ route('portal.documents.unpublish', $document) }}" method="POST" onsubmit="return confirm('{{ __('portal.documents.unpublish_confirm') }}')">
                                    @csrf
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="bi bi-slash-circle me-1"></i> {{ __('portal.actions.unpublish') }}
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('portal.documents.publish', $document) }}" method="POST" onsubmit="return confirm('{{ __('portal.documents.publish_confirm') }}')">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-check-circle me-1"></i> {{ __('portal.actions.publish') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Audit & Upload Info --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ __('common.audit_information') }}</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('portal.documents.uploaded_by') }}:</span>
                            <span class="fw-semibold">{{ $document->uploader?->name ?? '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('portal.documents.uploaded_at') }}:</span>
                            <span class="fw-semibold">{{ $document->created_at->format('d M Y, h:i A') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('portal.published_by') }}:</span>
                            <span class="fw-semibold">{{ $document->publisher?->name ?? '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('portal.published_at') }}:</span>
                            <span class="fw-semibold">{{ $document->published_at ? $document->published_at->format('d M Y, h:i A') : '—' }}</span>
                        </li>
                        @if($document->unpublished_at)
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">{{ __('portal.unpublished_by') }}:</span>
                                <span class="fw-semibold">{{ $document->unpublisher?->name ?? '—' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">{{ __('portal.unpublished_at') }}:</span>
                                <span class="fw-semibold">{{ $document->unpublished_at->format('d M Y, h:i A') }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
                @if(auth()->user()->can('delete_event_documents') || auth()->user()->can('delete_public_documents') || auth()->user()->can('manage_event_documents'))
                    <div class="card-footer bg-white border-top p-3">
                        <form action="{{ route('portal.documents.destroy', $document) }}" method="POST" onsubmit="return confirm('{{ __('portal.documents.delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                                <i class="bi bi-trash me-1"></i> {{ __('portal.actions.delete') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
