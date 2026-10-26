<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventLifecycleLog;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class EventDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_events'),
        ];
    }

    /**
     * Display the Puja & Event Management dashboard.
     */
    public function index(): View
    {
        $currentYear = (int) date('Y');

        $metrics = [
            'total' => Event::count(),
            'active' => Event::where('status', Event::STATUS_ACTIVE)->count(),
            'upcoming' => Event::where('status', Event::STATUS_UPCOMING)->count(),
            'draft' => Event::where('status', Event::STATUS_DRAFT)->count(),
            'closing' => Event::where('status', Event::STATUS_CLOSING)->count(),
            'closed' => Event::where('status', Event::STATUS_CLOSED)->count(),
            'categories' => EventCategory::count(),
            'current_year_events' => Event::where('event_year', $currentYear)->count(),
        ];

        $activeEvents = Event::with('category')
            ->whereIn('status', [Event::STATUS_ACTIVE, Event::STATUS_UPCOMING])
            ->orderBy('start_date')
            ->take(6)
            ->get();

        $recentLogs = EventLifecycleLog::with(['event', 'user'])
            ->latest()
            ->take(8)
            ->get();

        return view('events.dashboard', compact('metrics', 'activeEvents', 'recentLogs', 'currentYear'));
    }
}
