<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\UpdatePublicEventRequest;
use App\Models\Event;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicEventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_public_events', only: ['index', 'show']),
            new Middleware('permission:manage_public_events', only: ['edit', 'update']),
            new Middleware('permission:publish_public_events', only: ['publish', 'unpublish']),
        ];
    }

    /**
     * Display a listing of events with public portal configuration.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = Event::with(['category', 'publisher', 'unpublisher'])
                ->select('events.*');

            // Publication status filter
            if ($request->filled('publication_status') && $request->publication_status !== 'all') {
                $query->where('publication_status', $request->publication_status);
            }

            // Event liturgical status filter
            if ($request->filled('event_status') && $request->event_status !== 'all') {
                $query->where('status', $request->event_status);
            }

            // Year filter
            if ($request->filled('year') && $request->year !== 'all') {
                $query->where('event_year', $request->year);
            }

            $totalRecords = Event::count();
            $filteredRecords = (clone $query)->count();

            // Order handling (strictly 7 data columns, no row number column)
            $orderColIdx = (int) $request->input('order.0.column', 0);
            $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

            $orderCol = match ($orderColIdx) {
                0 => 'name_en',
                1 => 'slug',
                2 => 'event_year',
                3 => 'start_date',
                4 => 'status',
                5 => 'publication_status',
                default => 'created_at',
            };

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 15);
            if ($length <= 0) {
                $length = 15;
            }

            $events = $query->orderBy($orderCol, $orderDir)
                ->skip($start)
                ->take($length)
                ->get();

            $data = $events->map(function (Event $event) {
                return [
                    'event_details' => $this->formatEventDetailsColumn($event),
                    'slug' => '<code class="text-primary fw-bold">'.e($event->slug ?: '—').'</code>',
                    'category_year' => '<span class="badge bg-light text-dark border">'.e($event->category?->name_en ?? 'Event').'</span>'
                        .'<div class="small text-muted">'.e($event->event_year).'</div>',
                    'dates' => '<div class="small fw-semibold">'.$event->start_date->format('d M Y').' — '.$event->end_date->format('d M Y').'</div>',
                    'event_status' => $this->formatEventStatusBadge($event->status),
                    'publication_status' => $this->formatPublicationBadge($event->publication_status),
                    'actions' => $this->formatActionsColumn($event),
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        $years = Event::distinct()->orderByDesc('event_year')->pluck('event_year');

        return view('portal.events.index', compact('years'));
    }

    /**
     * Show the public configuration details for an event.
     */
    public function show(Event $event): View
    {
        $event->load(['category', 'publisher', 'unpublisher', 'creator']);

        return view('portal.events.show', compact('event'));
    }

    /**
     * Show the form for editing public event details.
     */
    public function edit(Event $event): View
    {
        return view('portal.events.edit', compact('event'));
    }

    /**
     * Update the public configuration for the event.
     */
    public function update(UpdatePublicEventRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();

        $bannerPath = $event->banner_image_path;

        // Handle banner removal
        if ($request->boolean('remove_banner') && $bannerPath) {
            if (Storage::disk('public')->exists($bannerPath)) {
                Storage::disk('public')->delete($bannerPath);
            }
            $bannerPath = null;
        }

        // Handle new banner upload
        if ($request->hasFile('banner_image')) {
            if ($bannerPath && Storage::disk('public')->exists($bannerPath)) {
                Storage::disk('public')->delete($bannerPath);
            }
            $bannerPath = $request->file('banner_image')->store('banners', 'public');
        }

        $oldData = [
            'slug' => $event->slug,
            'banner_image_path' => $event->banner_image_path,
            'short_description_en' => $event->short_description_en,
            'short_description_bn' => $event->short_description_bn,
        ];

        $event->update([
            'slug' => $validated['slug'],
            'short_description_en' => $validated['short_description_en'] ?? null,
            'short_description_bn' => $validated['short_description_bn'] ?? null,
            'banner_image_path' => $bannerPath,
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::log(
            'public_event_updated',
            "Updated public portal configuration for event {$event->name_en} ({$event->event_code})",
            $event,
            [
                'old' => $oldData,
                'new' => [
                    'slug' => $event->slug,
                    'banner_image_path' => $bannerPath,
                    'short_description_en' => $event->short_description_en,
                    'short_description_bn' => $event->short_description_bn,
                ],
            ]
        );

        return redirect()->route('portal.events.show', $event)
            ->with('success', __('portal.event_public_info_updated'));
    }

    /**
     * Publish an event for public portal display.
     */
    public function publish(Request $request, Event $event): RedirectResponse|JsonResponse
    {
        if (empty($event->slug)) {
            $message = __('portal.event_requires_slug_to_publish');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $event->update([
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::log(
            'public_event_published',
            "Published event {$event->name_en} ({$event->event_code}) to public portal",
            $event,
            ['published_by' => Auth::id(), 'published_at' => now()->toIso8601String()]
        );

        $message = __('portal.event_published_successfully');
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /**
     * Unpublish an event from public portal display.
     */
    public function unpublish(Request $request, Event $event): RedirectResponse|JsonResponse
    {
        $event->update([
            'publication_status' => Event::PUB_STATUS_UNPUBLISHED,
            'unpublished_at' => now(),
            'unpublished_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::log(
            'public_event_unpublished',
            "Unpublished event {$event->name_en} ({$event->event_code}) from public portal",
            $event,
            ['unpublished_by' => Auth::id(), 'unpublished_at' => now()->toIso8601String()]
        );

        $message = __('portal.event_unpublished_successfully');
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    private function formatEventDetailsColumn(Event $event): string
    {
        $html = '<div class="d-flex align-items-center gap-2">';
        if ($event->banner_image_path) {
            $html .= '<img src="'.e(asset('storage/'.$event->banner_image_path)).'" class="rounded border shadow-sm" style="width: 48px; height: 36px; object-fit: cover;" alt="Banner">';
        } else {
            $html .= '<div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 36px;"><i class="bi bi-image"></i></div>';
        }
        $html .= '<div>';
        $html .= '<a href="'.route('portal.events.show', $event).'" class="fw-bold text-dark text-decoration-none hover-primary">'.e($event->name).'</a>';
        $html .= '<div class="small text-muted font-monospace">'.e($event->event_code).'</div>';
        $html .= '</div></div>';

        return $html;
    }

    private function formatEventStatusBadge(string $status): string
    {
        return match ($status) {
            Event::STATUS_ACTIVE => '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">'.e(__('events.status_active')).'</span>',
            Event::STATUS_UPCOMING => '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">'.e(__('events.status_upcoming')).'</span>',
            Event::STATUS_CLOSING => '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">'.e(__('events.status_closing')).'</span>',
            Event::STATUS_CLOSED => '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">'.e(__('events.status_closed')).'</span>',
            default => '<span class="badge bg-light text-dark border">'.e(__('events.status_draft')).'</span>',
        };
    }

    private function formatPublicationBadge(string $status): string
    {
        return match ($status) {
            Event::PUB_STATUS_PUBLISHED => '<span class="badge bg-success"><i class="bi bi-globe me-1"></i>'.e(__('portal.published')).'</span>',
            Event::PUB_STATUS_UNPUBLISHED => '<span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>'.e(__('portal.unpublished')).'</span>',
            default => '<span class="badge bg-warning text-dark"><i class="bi bi-pencil me-1"></i>'.e(__('portal.draft')).'</span>',
        };
    }

    private function formatActionsColumn(Event $event): string
    {
        $html = '<div class="d-flex justify-content-end gap-1">';

        // View details
        $html .= '<a href="'.route('portal.events.show', $event).'" class="btn btn-sm btn-outline-primary" title="'.e(__('common.view')).'">';
        $html .= '<i class="bi bi-eye"></i></a>';

        // Edit configuration
        if (Auth::user()->hasPermission('manage_public_events')) {
            $html .= '<a href="'.route('portal.events.edit', $event).'" class="btn btn-sm btn-outline-secondary" title="'.e(__('common.edit')).'">';
            $html .= '<i class="bi bi-gear"></i></a>';
        }

        // Publish / Unpublish actions
        if (Auth::user()->hasPermission('publish_public_events')) {
            if ($event->publication_status === Event::PUB_STATUS_PUBLISHED) {
                $html .= '<button type="button" class="btn btn-sm btn-outline-warning btn-toggle-publish" data-action="unpublish" data-url="'.route('portal.events.unpublish', $event).'" title="'.e(__('portal.unpublish')).'">';
                $html .= '<i class="bi bi-eye-slash"></i></button>';
            } else {
                $html .= '<button type="button" class="btn btn-sm btn-outline-success btn-toggle-publish" data-action="publish" data-url="'.route('portal.events.publish', $event).'" title="'.e(__('portal.publish')).'">';
                $html .= '<i class="bi bi-globe"></i></button>';
            }
        }

        $html .= '</div>';

        return $html;
    }
}
