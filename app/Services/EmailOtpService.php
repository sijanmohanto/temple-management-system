<?php

namespace App\Services;

use App\Mail\DevoteeOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class EmailOtpService
{
    /**
     * Generate, store, and email an OTP code.
     */
    public function generateAndSendOtp(User $user, string $purpose, ?string $targetEmail = null): EmailOtp
    {
        $email = $targetEmail ?: $user->email ?: $user->person?->email;

        if (empty($email)) {
            throw new RuntimeException(__('devotee.no_registered_email_error'));
        }

        // Rate limiting: max 3 requests within 10 minutes for this purpose
        $recentCount = EmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentCount >= 3) {
            throw new RuntimeException(__('devotee.otp_rate_limited'));
        }

        // Invalidate older unused OTPs for the same user and purpose
        EmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        // Generate cryptographically secure 6-digit code
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiryMinutes = 10;

        $otpRecord = EmailOtp::create([
            'user_id' => $user->id,
            'email' => $email,
            'otp_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'attempts' => 0,
        ]);

        // Send email
        Mail::to($email)->send(new DevoteeOtpMail($code, $purpose, $expiryMinutes));

        return $otpRecord;
    }

    /**
     * Verify submitted OTP code for an action.
     */
    public function verifyOtp(User $user, string $purpose, string $code): bool
    {
        $cleanCode = trim($code);
        if (empty($cleanCode)) {
            return false;
        }

        $otp = EmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->attempts >= 5) {
            $otp->update(['used_at' => now()]); // Invalidate after too many attempts

            return false;
        }

        $otp->increment('attempts');

        if (Hash::check($cleanCode, $otp->otp_hash)) {
            $otp->update(['used_at' => now()]);

            return true;
        }

        return false;
    }
}
