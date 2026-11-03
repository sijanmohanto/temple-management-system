<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\StoreEventDocumentRequest;
use App\Http\Requests\PublicPortal\UpdateEventDocumentRequest;
use App\Models\Event;
use App\Models\EventDocument;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventDocumentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_event_documents|view_public_documents', only: ['index', 'show', 'download']),
            new Middleware('permission:manage_event_documents|manage_public_documents', only: ['create', 'store', 'edit', 'update']),
            new Middleware('permission:publish_event_documents|publish_public_documents', only: ['publish', 'unpublish']),
            new Middleware('permission:delete_event_documents|delete_public_documents|manage_event_documents', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of event documents.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax() || $request->wantsJson()) {
            $query = EventDocument::with(['event', 'uploader', 'publisher']);

            // Filters
            if ($request->filled('event_id')) {
                $query->where('event_id', $request->integer('event_id'));
            }

            if ($request->filled('document_type')) {
                $query->where('document_type', $request->input('document_type'));
            }

            if ($request->filled('publication_status')) {
                $query->where('publication_status', $request->input('publication_status'));
            }

            // Global search
            if ($searchValue = $request->input('search.value')) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('title_en', 'like', "%{$searchValue}%")
                        ->orWhere('title_bn', 'like', "%{$searchValue}%")
                        ->orWhere('file_name', 'like', "%{$searchValue}%")
                        ->orWhereHas('event', function ($eq) use ($searchValue) {
                            $eq->where('name_en', 'like', "%{$searchValue}%")
                                ->orWhere('name_bn', 'like', "%{$searchValue}%")
                                ->orWhere('event_code', 'like', "%{$searchValue}%");
                        });
                });
            }

            $totalRecords = EventDocument::count();
            $filteredRecords = $query->count();

            // Ordering
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'desc');

            $columnMap = [
                0 => 'title_en',
                1 => 'event_id',
                2 => 'document_type',
                3 => 'file_size',
                4 => 'publication_status',
                5 => 'published_at',
            ];

            if ($orderColumnIndex !== null && isset($columnMap[$orderColumnIndex])) {
                $query->orderBy($columnMap[$orderColumnIndex], $orderDirection);
            } else {
                $query->orderByDesc('id');
            }

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 15);
            $documents = $query->skip($start)->take($length)->get();

            $data = $documents->map(function (EventDocument $doc) {
                return [
                    'document_title' => $this->formatTitleColumn($doc),
                    'event_name' => $this->formatEventColumn($doc),
                    'document_type' => $this->formatTypeBadge($doc->document_type),
                    'file_info' => $this->formatFileInfoColumn($doc),
                    'publication_status' => $this->formatPublicationBadge($doc->publication_status),
                    'published_at' => $doc->published_at ? $doc->published_at->format('d M Y, h:i A') : '<span class="text-muted">—</span>',
                    'actions' => $this->formatActionsColumn($doc),
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        $events = Event::with('category')->orderBy('name_en')->get();
        $selectedEventId = $request->integer('event_id') ?: null;

        return view('portal.documents.index', compact('events', 'selectedEventId'));
    }

    /**
     * Show form to create a new document.
     */
    public function create(Request $request): View
    {
        $events = Event::with('category')->orderBy('name_en')->get();
        $preselectedEventId = $request->integer('event_id') ?: null;

        return view('portal.documents.create', compact('events', 'preselectedEventId'));
    }

    /**
     * Store a newly created document.
     */
    public function store(StoreEventDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('file');

        // Store file safely in private local storage with randomized name
        $path = $file->store('private/event_documents', 'local');

        $requestedStatus = $validated['publication_status'] ?? null;
        $isPublished = false;
        if (($requestedStatus === EventDocument::PUB_STATUS_PUBLISHED || $request->boolean('publish_now'))
            && ($request->user()->can('publish_event_documents') || $request->user()->can('publish_public_documents'))) {
            $isPublished = true;
        }

        $document = EventDocument::create([
            'event_id' => $validated['event_id'],
            'title_en' => $validated['title_en'],
            'title_bn' => $validated['title_bn'] ?? null,
            'document_type' => $validated['document_type'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'description_en' => $validated['description_en'] ?? null,
            'description_bn' => $validated['description_bn'] ?? null,
            'publication_status' => $isPublished ? EventDocument::PUB_STATUS_PUBLISHED : EventDocument::PUB_STATUS_DRAFT,
            'published_at' => $isPublished ? now() : null,
            'published_by' => $isPublished ? auth()->id() : null,
            'uploaded_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'document_created',
            "Uploaded event document '{$document->title_en}' for event #{$document->event_id}",
            $document
        );

        return redirect()
            ->route('portal.documents.show', $document)
            ->with('success', __('portal.documents.created_successfully'));
    }

    /**
     * Show document details.
     */
    public function show(EventDocument $document): View
    {
        $document->load(['event', 'uploader', 'publisher', 'unpublisher']);

        return view('portal.documents.show', compact('document'));
    }

    /**
     * Show form to edit document.
     */
    public function edit(EventDocument $document): View
    {
        $events = Event::with('category')->orderBy('name_en')->get();

        return view('portal.documents.edit', compact('document', 'events'));
    }

    /**
     * Update the specified document.
     */
    public function update(UpdateEventDocumentRequest $request, EventDocument $document): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'event_id' => $validated['event_id'],
            'title_en' => $validated['title_en'],
            'title_bn' => $validated['title_bn'] ?? null,
            'document_type' => $validated['document_type'],
            'description_en' => $validated['description_en'] ?? null,
            'description_bn' => $validated['description_bn'] ?? null,
        ];

        if (isset($validated['publication_status']) && ($request->user()->can('publish_event_documents') || $request->user()->can('publish_public_documents'))) {
            $data['publication_status'] = $validated['publication_status'];
            if ($validated['publication_status'] === EventDocument::PUB_STATUS_PUBLISHED && ! $document->published_at) {
                $data['published_at'] = now();
                $data['published_by'] = auth()->id();
            } elseif ($validated['publication_status'] !== EventDocument::PUB_STATUS_PUBLISHED) {
                $data['unpublished_at'] = now();
                $data['unpublished_by'] = auth()->id();
            }
        }

        // Replace file if new file provided
        if ($request->hasFile('file')) {
            $newFile = $request->file('file');

            // Delete old file if present
            if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            $newPath = $newFile->store('private/event_documents', 'local');
            $data['file_path'] = $newPath;
            $data['file_name'] = $newFile->getClientOriginalName();
            $data['file_size'] = $newFile->getSize();
            $data['mime_type'] = $newFile->getClientMimeType() ?: 'application/octet-stream';
        }

        $document->update($data);

        AuditLogger::log(
            'document_updated',
            "Updated document details for '{$document->title_en}' (#{$document->id})",
            $document
        );

        return redirect()
            ->route('portal.documents.show', $document)
            ->with('success', __('portal.documents.updated_successfully'));
    }

    /**
     * Remove the specified document (soft delete).
     */
    public function destroy(EventDocument $document): RedirectResponse|JsonResponse
    {
        $document->delete();

        AuditLogger::log(
            'document_deleted',
            "Soft-deleted document '{$document->title_en}' (#{$document->id})",
            $document
        );

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.documents.deleted_successfully'),
            ]);
        }

        return redirect()
            ->route('portal.documents.index')
            ->with('success', __('portal.documents.deleted_successfully'));
    }

    /**
     * Download or stream the document file.
     */
    public function download(Request $request, EventDocument $document): StreamedResponse|BinaryFileResponse
    {
        if (! $document->file_path || ! Storage::disk('local')->exists($document->file_path)) {
            abort(404, __('portal.documents.file_not_found'));
        }

        if ($request->boolean('preview') && in_array($document->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])) {
            return response()->file(Storage::disk('local')->path($document->file_path), [
                'Content-Type' => $document->mime_type,
                'Content-Disposition' => 'inline; filename="'.addslashes($document->file_name).'"',
            ]);
        }

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    /**
     * Publish document to public portal.
     */
    public function publish(Request $request, EventDocument $document): JsonResponse|RedirectResponse
    {
        $document->update([
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'document_published',
            "Published document '{$document->title_en}' for event #{$document->event_id}",
            $document
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.documents.published_successfully'),
                'status' => $document->publication_status,
                'badge' => $this->formatPublicationBadge($document->publication_status),
            ]);
        }

        return back()->with('success', __('portal.documents.published_successfully'));
    }

    /**
     * Unpublish document from public portal.
     */
    public function unpublish(Request $request, EventDocument $document): JsonResponse|RedirectResponse
    {
        $document->update([
            'publication_status' => EventDocument::PUB_STATUS_UNPUBLISHED,
            'unpublished_at' => now(),
            'unpublished_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'document_unpublished',
            "Unpublished document '{$document->title_en}' for event #{$document->event_id}",
            $document
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('portal.documents.unpublished_successfully'),
                'status' => $document->publication_status,
                'badge' => $this->formatPublicationBadge($document->publication_status),
            ]);
        }

        return back()->with('success', __('portal.documents.unpublished_successfully'));
    }

    // -------------------------------------------------------------
    // Formatters for DataTables
    // -------------------------------------------------------------

    protected function formatTitleColumn(EventDocument $doc): string
    {
        $title = e($doc->title);
        $enTitle = e($doc->title_en);
        $bnTitle = e($doc->title_bn ?? '');
        $showUrl = route('portal.documents.show', $doc);

        $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION));
        $icon = match ($ext) {
            'pdf' => '<i class="bi bi-file-earmark-pdf text-danger fs-5 me-2"></i>',
            'jpg', 'jpeg', 'png' => '<i class="bi bi-file-earmark-image text-primary fs-5 me-2"></i>',
            default => '<i class="bi bi-file-earmark-text text-secondary fs-5 me-2"></i>',
        };

        $html = '<div class="d-flex align-items-center">'.$icon.'<div>';
        $html .= '<a href="'.$showUrl.'" class="fw-semibold text-decoration-none text-dark">'.$title.'</a>';

        if (app()->getLocale() === 'bn' && $enTitle !== $title) {
            $html .= '<div class="small text-muted">'.$enTitle.'</div>';
        } elseif (app()->getLocale() === 'en' && $bnTitle) {
            $html .= '<div class="small text-muted font-bengali">'.$bnTitle.'</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    protected function formatEventColumn(EventDocument $doc): string
    {
        if (! $doc->event) {
            return '<span class="text-muted">—</span>';
        }

        $eventName = e($doc->event->name);
        $eventUrl = route('portal.events.show', $doc->event_id);
        $eventCode = e($doc->event->event_code);

        return '<div><a href="'.$eventUrl.'" class="text-decoration-none fw-semibold">'.$eventName.'</a>'
            .'<div class="small text-muted"><code class="text-secondary">'.$eventCode.'</code></div></div>';
    }

    protected function formatTypeBadge(string $type): string
    {
        $badgeClass = match ($type) {
            EventDocument::TYPE_FINANCIAL_REPORT => 'bg-success',
            EventDocument::TYPE_AUDIT_REPORT => 'bg-primary',
            EventDocument::TYPE_SUPPORTING_DOCUMENT => 'bg-info text-dark',
            default => 'bg-secondary',
        };

        $key = 'portal.document_types.'.$type;
        $label = __($key);
        if ($label === $key) {
            $label = ucwords(str_replace('_', ' ', $type));
        }

        return '<span class="badge '.$badgeClass.'">'.e($label).'</span>';
    }

    protected function formatFileInfoColumn(EventDocument $doc): string
    {
        $size = e($doc->formatted_file_size);
        $fileName = e($doc->file_name);
        $downloadUrl = route('portal.documents.download', $doc);

        return '<div class="small fw-semibold text-truncate" style="max-width: 180px;" title="'.$fileName.'">'.$fileName.'</div>'
            .'<div class="small text-muted">'.$size.' &bull; <a href="'.$downloadUrl.'" class="text-primary text-decoration-none" title="'.__('portal.actions.download').'"><i class="bi bi-download"></i> '.__('portal.actions.download').'</a></div>';
    }

    protected function formatPublicationBadge(string $status): string
    {
        return match ($status) {
            EventDocument::PUB_STATUS_PUBLISHED => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>'.__('portal.status.published').'</span>',
            EventDocument::PUB_STATUS_UNPUBLISHED => '<span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i>'.__('portal.status.unpublished').'</span>',
            default => '<span class="badge bg-warning text-dark"><i class="bi bi-pencil-square me-1"></i>'.__('portal.status.draft').'</span>',
        };
    }

    protected function formatActionsColumn(EventDocument $doc): string
    {
        $showUrl = route('portal.documents.show', $doc);
        $editUrl = route('portal.documents.edit', $doc);
        $downloadUrl = route('portal.documents.download', $doc);
        $user = auth()->user();

        $html = '<div class="btn-group btn-group-sm" role="group">';
        $html .= '<a href="'.$showUrl.'" class="btn btn-outline-secondary" title="'.__('portal.actions.view').'"><i class="bi bi-eye"></i></a>';
        $html .= '<a href="'.$downloadUrl.'" class="btn btn-outline-info" title="'.__('portal.actions.download').'"><i class="bi bi-download"></i></a>';

        if ($user && $user->can('manage_public_documents')) {
            $html .= '<a href="'.$editUrl.'" class="btn btn-outline-primary" title="'.__('portal.actions.edit').'"><i class="bi bi-pencil"></i></a>';
        }

        if ($user && $user->can('publish_public_documents')) {
            if ($doc->isPublished()) {
                $unpublishUrl = route('portal.documents.unpublish', $doc);
                $html .= '<button type="button" class="btn btn-outline-warning btn-unpublish" data-url="'.$unpublishUrl.'" data-id="'.$doc->id.'" title="'.__('portal.actions.unpublish').'"><i class="bi bi-slash-circle"></i></button>';
            } else {
                $publishUrl = route('portal.documents.publish', $doc);
                $html .= '<button type="button" class="btn btn-outline-success btn-publish" data-url="'.$publishUrl.'" data-id="'.$doc->id.'" title="'.__('portal.actions.publish').'"><i class="bi bi-check-circle"></i></button>';
            }
        }

        if ($user && $user->can('delete_public_documents')) {
            $deleteUrl = route('portal.documents.destroy', $doc);
            $html .= '<button type="button" class="btn btn-outline-danger btn-delete-doc" data-url="'.$deleteUrl.'" data-id="'.$doc->id.'" title="'.__('portal.actions.delete').'"><i class="bi bi-trash"></i></button>';
        }

        $html .= '</div>';

        return $html;
    }
}
