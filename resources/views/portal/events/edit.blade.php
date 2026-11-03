@extends('layouts.app')

@section('title', __('portal.configure_public_info') . ' - ' . $event->name)

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('portal.events.index') }}">{{ __('portal.public_events') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $event->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="bi bi-gear-wide-connected text-primary me-2"></i>{{ __('portal.configure_public_info') }}
            </h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('portal.events.show', $event) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-eye me-1"></i> {{ __('common.view') }}
            </a>
            <a href="{{ route('portal.events.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-sliders me-1 text-primary"></i> {{ __('portal.public_configuration') }}
                        </h6>
                        <span class="badge {{ $event->isPublished() ? 'bg-success' : 'bg-secondary' }}">
                            {{ __('portal.' . $event->publication_status) }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('portal.events.update', $event) }}" method="POST" enctype="multipart/form-data" id="publicEventForm">
                        @csrf
                        @method('PUT')

                        <!-- Slug -->
                        <div class="mb-4">
                            <label for="slug" class="form-label fw-bold small text-dark">
                                {{ __('portal.slug') }} <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted small font-monospace">/events/</span>
                                <input type="text" name="slug" id="slug"
                                       class="form-control font-monospace @error('slug') is-invalid @enderror"
                                       value="{{ old('slug', $event->slug) }}" required maxlength="191"
                                       placeholder="e.g. durga-puja-2026">
                            </div>
                            <small class="text-muted d-block mt-1">{{ __('portal.slug_help') }}</small>
                            @error('slug')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Banner Image Upload -->
                        <div class="mb-4">
                            <label for="banner_image" class="form-label fw-bold small text-dark">
                                {{ __('portal.banner_image') }}
                            </label>

                            @if($event->banner_image_path)
                                <div class="mb-3 p-2 border rounded bg-light">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ asset('storage/' . $event->banner_image_path) }}" alt="Current Banner"
                                             class="rounded shadow-sm" style="max-height: 90px; max-width: 160px; object-fit: cover;">
                                        <div>
                                            <div class="small fw-bold text-dark mb-1">{{ __('portal.current_banner') }}</div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remove_banner" value="1" id="remove_banner">
                                                <label class="form-check-label text-danger small" for="remove_banner">
                                                    {{ __('portal.remove_banner') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <input type="file" name="banner_image" id="banner_image"
                                   class="form-control @error('banner_image') is-invalid @enderror"
                                   accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted d-block mt-1">{{ __('portal.banner_help') }}</small>
                            @error('banner_image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Short Description EN -->
                        <div class="mb-4">
                            <label for="short_description_en" class="form-label fw-bold small text-dark">
                                {{ __('portal.short_description_en') }}
                            </label>
                            <textarea name="short_description_en" id="short_description_en" rows="3"
                                      class="form-control @error('short_description_en') is-invalid @enderror"
                                      maxlength="500" placeholder="A concise summary for public visitors...">{{ old('short_description_en', $event->short_description_en) }}</textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">{{ __('portal.short_description_help') }}</small>
                                <small class="text-muted"><span id="charCountEn">0</span> / 500</small>
                            </div>
                            @error('short_description_en')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Short Description BN -->
                        <div class="mb-4">
                            <label for="short_description_bn" class="form-label fw-bold small text-dark">
                                {{ __('portal.short_description_bn') }}
                            </label>
                            <textarea name="short_description_bn" id="short_description_bn" rows="3"
                                      class="form-control @error('short_description_bn') is-invalid @enderror"
                                      maxlength="500" placeholder="সাধারণ দর্শনার্থীদের জন্য সংক্ষিপ্ত বিবরণ...">{{ old('short_description_bn', $event->short_description_bn) }}</textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">{{ __('portal.short_description_help') }}</small>
                                <small class="text-muted"><span id="charCountBn">0</span> / 500</small>
                            </div>
                            @error('short_description_bn')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="{{ route('portal.events.show', $event) }}" class="btn btn-outline-secondary">
                                {{ __('common.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary px-4" id="btnSubmit">
                                <i class="bi bi-check-lg me-1"></i> {{ __('common.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <!-- Event Readonly Context Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-1 text-primary"></i> {{ __('events.event_details') }}
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('events.event_code') }}:</span>
                            <span class="font-monospace fw-bold">{{ $event->event_code }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('events.category') }}:</span>
                            <span>{{ $event->category?->name_en ?? 'Event' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('events.event_year') }}:</span>
                            <span>{{ $event->event_year }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('events.dates') }}:</span>
                            <span class="fw-semibold">{{ $event->start_date->format('d M Y') }} — {{ $event->end_date->format('d M Y') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">{{ __('portal.liturgical_status') }}:</span>
                            <span class="badge bg-light text-dark border">{{ $event->status }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Quick Publish Action Card -->
            @if(Auth::user()->hasPermission('publish_public_events'))
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-broadcast me-1 text-success"></i> {{ __('portal.publication_status') }}
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($event->isPublished())
                            <div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2">
                                <i class="bi bi-check-circle-fill fs-5"></i>
                                <div class="small">
                                    <strong>{{ __('portal.published') }}</strong>
                                    <div class="text-muted">{{ $event->published_at?->format('d M Y, h:i A') }}</div>
                                </div>
                            </div>
                            <form action="{{ route('portal.events.unpublish', $event) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('{{ __('portal.unpublish_confirmation') }}')">
                                    <i class="bi bi-eye-slash me-1"></i> {{ __('portal.unpublish') }}
                                </button>
                            </form>
                        @else
                            <div class="alert alert-secondary d-flex align-items-center gap-2 mb-3 py-2">
                                <i class="bi bi-eye-slash-fill fs-5"></i>
                                <div class="small">
                                    <strong>{{ __('portal.' . $event->publication_status) }}</strong>
                                    <div class="text-muted">Not visible on public portal</div>
                                </div>
                            </div>
                            <form action="{{ route('portal.events.publish', $event) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100" onclick="return confirm('{{ __('portal.publish_confirmation') }}')">
                                    <i class="bi bi-globe me-1"></i> {{ __('portal.publish') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function updateCounter(input, counter) {
        var len = $(input).val().length;
        $(counter).text(len);
    }

    $('#short_description_en').on('input', function() {
        updateCounter(this, '#charCountEn');
    });
    updateCounter('#short_description_en', '#charCountEn');

    $('#short_description_bn').on('input', function() {
        updateCounter(this, '#charCountBn');
    });
    updateCounter('#short_description_bn', '#charCountBn');
});
</script>
@endpush
