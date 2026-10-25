<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = ['en', 'bn'];

        $locale = session('locale');

        if (!$locale && Auth::check() && Auth::user()->preferred_locale) {
            $locale = Auth::user()->preferred_locale;
        }

        if (!$locale || !in_array($locale, $supportedLocales, true)) {
            $locale = config('app.locale', 'en');
        }

        App::setLocale($locale);
        view()->share('currentLocale', $locale);

        return $next($request);
    }
}
