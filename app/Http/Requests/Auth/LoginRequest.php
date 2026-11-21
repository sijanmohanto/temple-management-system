<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'login' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = trim((string) ($this->input('phone') ?? $this->input('login') ?? $this->input('email') ?? ''));
        $password = (string) $this->input('password');

        $errorField = ($this->filled('phone') && ! $this->filled('email') && ! $this->filled('login')) ? 'phone' : 'email';

        if (empty($identifier)) {
            throw ValidationException::withMessages([
                $errorField => __('auth.failed'),
            ]);
        }

        $user = null;

        // 1. Resolve user by email if contains '@'
        if (str_contains($identifier, '@')) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($identifier)])->first();
        } else {
            // 2. Resolve user by phone variants
            $variants = User::getPhoneVariants($identifier);
            $matchedUsers = User::whereIn('phone', $variants)->get();

            if ($matchedUsers->count() > 1) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    $errorField => __('devotee.ambiguous_phone_error', ['phone' => $identifier]),
                ]);
            }

            if ($matchedUsers->count() === 1) {
                $user = $matchedUsers->first();
            } else {
                // Fallback: check if the identifier was an email without '@' or legacy match
                $user = User::where('email', $identifier)->first();
            }
        }

        // Verify credentials
        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                $errorField => __('auth.failed'),
            ]);
        }

        // Validate active account status
        if (! $user->isActive()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                $errorField => __('auth.account_inactive'),
            ]);
        }

        // Prevent unlinked devotee accounts from accessing the portal
        if ($user->hasRole('devotee') && ! $user->hasAnyRole(['super_admin', 'temple_admin', 'treasurer'])) {
            $person = $user->person();
            if (! $person) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    $errorField => __('devotee.unlinked_account_error'),
                ]);
            }

            if (! $user->person_id && $person->id) {
                $user->person_id = $person->id;
                $user->save();
            }
        }

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $errorMsg = trans('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => ceil($seconds / 60),
        ]);

        throw ValidationException::withMessages([
            'email' => $errorMsg,
            'phone' => $errorMsg,
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $identifier = trim((string) ($this->input('phone') ?? $this->input('login') ?? $this->input('email') ?? ''));

        return Str::transliterate(Str::lower($identifier).'|'.$this->ip());
    }
}
