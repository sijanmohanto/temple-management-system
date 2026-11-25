<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Committee;
use App\Models\Family;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        // Redirect ordinary devotees directly to their dedicated self-service portal
        if ($user->hasRole('devotee') && ! $user->hasAnyRole(['super_admin', 'temple_admin', 'treasurer'])) {
            return redirect()->route('devotee.dashboard');
        }

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_roles' => Role::count(),
            'total_audit_logs' => AuditLog::count(),
            'total_people' => Person::count(),
            'active_people' => Person::where('status', 'active')->count(),
            'total_families' => Family::count(),
            'active_committees' => Committee::where('status', 'active')->count(),
        ];

        // Retrieve latest audit logs for administrator visibility
        $recentAuditLogs = AuditLog::with('user')
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard.index', compact('user', 'stats', 'recentAuditLogs'));
    }
}
