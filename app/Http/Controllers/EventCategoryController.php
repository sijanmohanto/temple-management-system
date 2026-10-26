<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventCategoryRequest;
use App\Http\Requests\UpdateEventCategoryRequest;
use App\Models\EventCategory;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class EventCategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_events', only: ['index']),
            new Middleware('permission:manage_event_categories', except: ['index']),
        ];
    }

    /**
     * Display a listing of event categories.
     */
    public function index(Request $request): View|JsonResponse
    {
        $categories = EventCategory::withCount('events')
            ->orderBy('name_en')
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'categories' => $categories,
            ]);
        }

        return view('events.categories.index', compact('categories'));
    }

    /**
     * Store a newly created event category.
     */
    public function store(StoreEventCategoryRequest $request): RedirectResponse|JsonResponse
    {
        $category = EventCategory::create($request->validated());

        AuditLogger::log(
            'event_category_created',
            "Created event category '{$category->name_en}'",
            $category,
            $category->toArray()
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('events.category_created_success'),
                'category' => $category,
            ]);
        }

        return redirect()
            ->route('events.categories.index')
            ->with('success', __('events.category_created_success'));
    }

    /**
     * Update the specified event category.
     */
    public function update(UpdateEventCategoryRequest $request, EventCategory $category): RedirectResponse|JsonResponse
    {
        $oldData = $category->toArray();
        $category->update($request->validated());

        AuditLogger::log(
            'event_category_updated',
            "Updated event category '{$category->name_en}'",
            $category,
            [
                'old' => $oldData,
                'new' => $category->toArray(),
            ]
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('events.category_updated_success'),
                'category' => $category,
            ]);
        }

        return redirect()
            ->route('events.categories.index')
            ->with('success', __('events.category_updated_success'));
    }

    /**
     * Toggle the status of an event category.
     */
    public function toggleStatus(EventCategory $category): RedirectResponse|JsonResponse
    {
        $newStatus = $category->isActive() ? 'inactive' : 'active';
        $category->update(['status' => $newStatus]);

        AuditLogger::log(
            'event_category_status_toggled',
            "Toggled status of event category '{$category->name_en}' to {$newStatus}",
            $category,
            ['status' => $newStatus]
        );

        $message = $newStatus === 'active'
            ? __('events.category_activated_success')
            : __('events.category_deactivated_success');

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $newStatus,
            ]);
        }

        return redirect()
            ->route('events.categories.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified event category.
     */
    public function destroy(EventCategory $category): RedirectResponse|JsonResponse
    {
        if (! $category->canBeSafelyDeleted()) {
            $errorMessage = $category->is_system
                ? __('events.cannot_delete_system_category')
                : __('events.cannot_delete_category_with_events');

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }

            return redirect()
                ->route('events.categories.index')
                ->with('error', $errorMessage);
        }

        $categoryName = $category->name_en;
        $category->delete();

        AuditLogger::log(
            'event_category_deleted',
            "Deleted event category '{$categoryName}'",
            null,
            ['name_en' => $categoryName]
        );

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('events.category_deleted_success'),
            ]);
        }

        return redirect()
            ->route('events.categories.index')
            ->with('success', __('events.category_deleted_success'));
    }
}
