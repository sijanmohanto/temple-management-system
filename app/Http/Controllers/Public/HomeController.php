<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the public home page with published running, upcoming, and completed events.
     */
    public function index(): View
    {
        // Published Running Events (Active or Closing)
        $runningEvents = Event::with('category')
            ->published()
            ->whereIn('status', [Event::STATUS_ACTIVE, Event::STATUS_CLOSING])
            ->orderBy('start_date', 'asc')
            ->get();

        // Published Upcoming Events
        $upcomingEvents = Event::with('category')
            ->published()
            ->where('status', Event::STATUS_UPCOMING)
            ->orderBy('start_date', 'asc')
            ->get();

        // Published Completed Events (Recent 4)
        $completedEvents = Event::with('category')
            ->published()
            ->where('status', Event::STATUS_CLOSED)
            ->orderBy('end_date', 'desc')
            ->take(4)
            ->get();

        return view('public.home', compact('runningEvents', 'upcomingEvents', 'completedEvents'));
    }
}
