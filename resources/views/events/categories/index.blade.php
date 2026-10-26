@extends('layouts.app')

@section('title', __('events.event_categories'))

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('events.index') }}" class="text-decoration-none">{{ __('events.events') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('events.categories') }}</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-1 text-dark">{{ __('events.event_categories') }}</h1>
            <p class="text-muted small mb-0">{{ __('events.categories') }} &bull; {{ $categories->count() }} {{ __('common.details') }}</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> {{ __('common.back') }}
            </a>
            @if(Auth::user()->hasPermission('manage_event_categories'))
                <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                    <i class="bi bi-plus-circle-fill me-1"></i> {{ __('events.add_category') }}
                </button>
            @endif
        </div>
    </div>

    <!-- Categories Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="categoriesDataTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">{{ __('events.name_en') }}</th>
                            <th>{{ __('events.name_bn') }}</th>
                            <th>{{ __('events.category_slug') }}</th>
                            <th>{{ __('events.total_events') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end pe-3">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ $category->name_en }}</div>
                                    @if($category->is_system)
                                        <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">{{ __('events.is_system') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-dark">{{ $category->name_bn ?: '—' }}</span>
                                </td>
                                <td>
                                    <code class="font-monospace text-muted">{{ $category->slug }}</code>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                        {{ $category->events_count }} {{ __('events.events') }}
                                    </span>
                                </td>
                                <td>
                                    @if($category->isActive())
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                            <i class="bi bi-check-circle me-1"></i>{{ __('common.status_active') }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">
                                            <i class="bi bi-slash-circle me-1"></i>{{ __('common.status_inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    @if(Auth::user()->hasPermission('manage_event_categories'))
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary edit-cat-btn"
                                                    data-id="{{ $category->id }}"
                                                    data-name-en="{{ $category->name_en }}"
                                                    data-name-bn="{{ $category->name_bn }}"
                                                    data-slug="{{ $category->slug }}"
                                                    data-desc-en="{{ $category->description_en }}"
                                                    data-desc-bn="{{ $category->description_bn }}"
                                                    data-status="{{ $category->status }}"
                                                    title="{{ __('common.edit') }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <form action="{{ route('events.categories.toggle-status', $category) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-{{ $category->isActive() ? 'warning' : 'success' }}" title="{{ $category->isActive() ? __('common.deactivate') : __('common.activate') }}">
                                                    <i class="bi bi-power"></i>
                                                </button>
                                            </form>

                                            @if($category->canBeSafelyDeleted())
                                                <form action="{{ route('events.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="{{ __('common.delete') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Create Category Modal -->
@if(Auth::user()->hasPermission('manage_event_categories'))
    <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('events.categories.store') }}" method="POST" id="createCategoryForm">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="createCategoryModalLabel">
                            <i class="bi bi-plus-circle me-2 text-primary"></i>{{ __('events.add_category') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold required">{{ __('events.category_name_en') }}</label>
                            <input type="text" name="name_en" id="createCatNameEn" class="form-control" required placeholder="e.g. Durga Puja">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('events.category_name_bn') }}</label>
                            <input type="text" name="name_bn" class="form-control" placeholder="যেমন: দুর্গাপূজা">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold required">{{ __('events.category_slug') }}</label>
                            <input type="text" name="slug" id="createCatSlug" class="form-control font-monospace" required placeholder="e.g. durga-puja">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('events.description_en') }}</label>
                            <textarea name="description_en" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('events.description_bn') }}</label>
                            <textarea name="description_bn" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold required">{{ __('common.status') }}</label>
                            <select name="status" class="form-select" required>
                                <option value="active">{{ __('common.status_active') }}</option>
                                <option value="inactive">{{ __('common.status_inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="saveCatBtn" class="btn btn-primary rounded-pill px-4" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                            <i class="bi bi-check2-circle me-1"></i>{{ __('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 2: Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="" method="POST" id="editCategoryForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark" id="editCategoryModalLabel">
                            <i class="bi bi-pencil me-2 text-primary"></i>{{ __('events.edit_category') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold required">{{ __('events.category_name_en') }}</label>
                            <input type="text" name="name_en" id="editCatNameEn" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('events.category_name_bn') }}</label>
                            <input type="text" name="name_bn" id="editCatNameBn" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold required">{{ __('events.category_slug') }}</label>
                            <input type="text" name="slug" id="editCatSlug" class="form-control font-monospace" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('events.description_en') }}</label>
                            <textarea name="description_en" id="editCatDescEn" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ __('events.description_bn') }}</label>
                            <textarea name="description_bn" id="editCatDescBn" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold required">{{ __('common.status') }}</label>
                            <select name="status" id="editCatStatus" class="form-select" required>
                                <option value="active">{{ __('common.status_active') }}</option>
                                <option value="inactive">{{ __('common.status_inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" id="updateCatBtn" class="btn btn-primary rounded-pill px-4" style="background-color: var(--temple-maroon); border-color: var(--temple-maroon);">
                            <i class="bi bi-check2-circle me-1"></i>{{ __('common.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        TempleUI.initDataTable('#categoriesDataTable', {
            pageLength: 20,
            searching: true,
            paging: true,
            info: true
        });

        // Slug auto-generation on create
        $('#createCatNameEn').on('input', function() {
            var val = $(this).val().toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            $('#createCatSlug').val(val);
        });

        // Handle edit button click
        $('.edit-cat-btn').on('click', function() {
            var id = $(this).data('id');
            var nameEn = $(this).data('name-en');
            var nameBn = $(this).data('name-bn');
            var slug = $(this).data('slug');
            var descEn = $(this).data('desc-en');
            var descBn = $(this).data('desc-bn');
            var status = $(this).data('status');

            $('#editCategoryForm').attr('action', "{{ url('events-categories') }}/" + id);
            $('#editCatNameEn').val(nameEn);
            $('#editCatNameBn').val(nameBn);
            $('#editCatSlug').val(slug);
            $('#editCatDescEn').val(descEn);
            $('#editCatDescBn').val(descBn);
            $('#editCatStatus').val(status);

            var editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            editModal.show();
        });

        $('#createCategoryForm').on('submit', function() {
            TempleUI.button.loading('#saveCatBtn');
        });

        $('#editCategoryForm').on('submit', function() {
            TempleUI.button.loading('#updateCatBtn');
        });
    });
</script>
@endpush
