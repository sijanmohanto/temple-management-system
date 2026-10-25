<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show profile edit view.
     */
    public function edit(): View
    {
        $user = Auth::user();

        return view('profile.edit', compact('user'));
    }

    /**
     * Update user profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $user->fill($validated);
        $user->save();

        session(['locale' => $user->preferred_locale]);
        app()->setLocale($user->preferred_locale);

        AuditLogger::log(
            'profile.update',
            "User {$user->name} updated their profile information.",
            $user
        );

        return redirect()->route('profile.edit')->with('success', __('common.profile_updated'));
    }

    /**
     * Update user password.
     */
    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        AuditLogger::log(
            'password.update',
            "User {$user->name} changed their password.",
            $user
        );

        return redirect()->route('profile.edit')->with('success', __('common.password_updated'));
    }
}
