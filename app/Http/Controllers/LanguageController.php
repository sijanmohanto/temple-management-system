<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LanguageController extends Controller
{
    /**
     * Switch application language between English and Bengali.
     */
    public function switchLocale(Request $request, string $locale): RedirectResponse
    {
        $supported = ['en', 'bn'];

        if (!in_array($locale, $supported, true)) {
            $locale = config('app.locale', 'en');
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);

        if (Auth::check()) {
            $user = Auth::user();
            $user->preferred_locale = $locale;
            $user->save();

            AuditLogger::log(
                'user.locale_change',
                "User changed locale to: {$locale}",
                $user,
                ['locale' => $locale]
            );
        }

        return redirect()->back()->with('success', __('common.locale_switched'));
    }
}
