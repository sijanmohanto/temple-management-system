<?php

namespace App\Http\Controllers\Devotee;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Services\AuditLogger;
use App\Services\DevoteeCredentialService;
use App\Services\EmailOtpService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DevoteeProfileController extends Controller
{
    public function __construct(
        protected EmailOtpService $otpService,
        protected DevoteeCredentialService $credentialService
    ) {}

    /**
     * Display the Devotee Profile Settings view.
     */
    public function edit(): View
    {
        $user = auth()->user();
        $person = $user->person;

        $hasRegisteredEmail = ! empty($user->email) || ! empty($person?->email);
        $registeredEmail = $user->email ?: $person?->email;

        return view('devotee.profile.edit', compact('user', 'person', 'hasRegisteredEmail', 'registeredEmail'));
    }

    /**
     * AJAX endpoint to request an Email OTP for profile or password changes.
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $request->validate([
            'purpose' => ['required', 'string', 'in:profile_update,password_change,email_change'],
        ]);

        $user = auth()->user();
        $purpose = $request->input('purpose');

        try {
            $otp = $this->otpService->generateAndSendOtp($user, $purpose);

            // Mask email for display: e.g. a***b@example.com
            $parts = explode('@', $otp->email);
            $local = $parts[0];
            $domain = $parts[1] ?? '';
            $maskedLocal = strlen($local) > 2
                ? substr($local, 0, 1).str_repeat('*', strlen($local) - 2).substr($local, -1)
                : $local.'*';
            $maskedEmail = $maskedLocal.'@'.$domain;

            return response()->json([
                'success' => true,
                'message' => __('devotee.otp_sent_to_email', ['email' => $maskedEmail]),
                'masked_email' => $maskedEmail,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update permitted devotee profile fields with mandatory Email OTP.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $person = $user->person;

        $registeredEmail = $user->email ?: $person?->email;
        if (empty($registeredEmail)) {
            return back()->with('error', __('devotee.no_registered_email_error'));
        }

        $validated = $request->validate([
            'otp_code' => ['required', 'string', 'size:6'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'present_address' => ['nullable', 'string', 'max:1000'],
            'permanent_address' => ['nullable', 'string', 'max:1000'],
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        // Verify Email OTP
        if (! $this->otpService->verifyOtp($user, EmailOtp::PURPOSE_PROFILE_UPDATE, $validated['otp_code'])) {
            return back()->withInput()->with('error', __('devotee.invalid_or_expired_otp'));
        }

        DB::transaction(function () use ($user, $person, $validated) {
            // Update Person profile
            $person->update([
                'occupation' => $validated['occupation'] ?? $person->occupation,
                'present_address' => $validated['present_address'] ?? $person->present_address,
                'permanent_address' => $validated['permanent_address'] ?? $person->permanent_address,
                'email' => $validated['email'] ?? $person->email,
                'updated_by' => $user->id,
            ]);

            // Synchronize User profile
            $user->update([
                'email' => $validated['email'] ?? $user->email,
                'address' => $validated['present_address'] ?? $user->address,
            ]);

            AuditLogger::log(
                'devotee_profile_self_updated',
                "Devotee {$user->name} (#{$user->id}) updated their profile details via verified Email OTP.",
                $user
            );
        });

        return redirect()->route('devotee.profile')
            ->with('success', __('devotee.profile_updated_successfully'));
    }

    /**
     * Update devotee password with current password verification + mandatory Email OTP.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $person = $user->person;

        $registeredEmail = $user->email ?: $person?->email;
        if (empty($registeredEmail)) {
            return back()->with('error', __('devotee.no_registered_email_error'));
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'otp_code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // 1. Verify current password
        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withInput()->withErrors([
                'current_password' => __('auth.password'),
            ]);
        }

        // 2. Verify Email OTP
        if (! $this->otpService->verifyOtp($user, EmailOtp::PURPOSE_PASSWORD_CHANGE, $validated['otp_code'])) {
            return back()->withInput()->withErrors([
                'otp_code' => __('devotee.invalid_or_expired_otp'),
            ]);
        }

        // 3. Atomically update hash and encrypted credential
        $this->credentialService->updateCredentials(
            $user,
            $validated['password'],
            $user,
            'Devotee self-service password change'
        );

        AuditLogger::log(
            'devotee_password_self_updated',
            "Devotee {$user->name} (#{$user->id}) changed their password using verified Email OTP.",
            $user
        );

        return redirect()->route('devotee.profile')
            ->with('success', __('devotee.password_changed_successfully'));
    }
}
