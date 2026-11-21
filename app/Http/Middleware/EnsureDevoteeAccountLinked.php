<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDevoteeAccountLinked
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', __('auth.account_inactive'));
        }

        // Devotee must have an active linked Person profile
        $person = $user->person;
        if (! $person) {
            if ($user->hasRole('devotee') && ! $user->hasAnyRole(['super_admin', 'temple_admin', 'treasurer'])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', __('devotee.unlinked_account_error'));
            }
        } elseif (! $person->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', __('devotee.person_inactive_error'));
        }

        return $next($request);
    }
}
