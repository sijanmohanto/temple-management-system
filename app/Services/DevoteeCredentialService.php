<?php

namespace App\Services;

use App\Models\DevoteeCredential;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DevoteeCredentialService
{
    /**
     * Generate a cryptographically secure random password.
     */
    public function generateSecurePassword(int $length = 12): string
    {
        // Must contain upper, lower, numbers, and special characters
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnpqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '@#$%&*!';

        $password = '';
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        $allChars = $upper.$lower.$numbers.$symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * Set or update devotee credentials atomically.
     * Both hash and encrypted copy are updated within a single DB transaction.
     */
    public function updateCredentials(
        User $user,
        string $plainPassword,
        ?User $actor = null,
        ?string $reason = null
    ): void {
        DB::transaction(function () use ($user, $plainPassword, $actor, $reason) {
            // 1. Hash password for Laravel auth
            $user->password = Hash::make($plainPassword);
            $user->save();

            // 2. Encrypt password for authorized administrative retrieval
            $encrypted = Crypt::encryptString($plainPassword);

            DevoteeCredential::updateOrCreate(
                ['user_id' => $user->id],
                ['encrypted_password' => $encrypted]
            );

            // 3. Log audit event (without logging plaintext or encrypted passwords)
            AuditLogger::log(
                'devotee_password_updated',
                "Updated credentials for devotee user #{$user->id} ({$user->name})"
                    .($reason ? " - Reason: {$reason}" : ''),
                $user,
                [
                    'actor_id' => $actor?->id,
                    'devotee_user_id' => $user->id,
                    'reason' => $reason,
                    'ip' => request()->ip(),
                ]
            );
        });
    }

    /**
     * Decrypt and retrieve devotee password for authorized admin.
     * Enforces permission and logs audit entry.
     */
    public function getDecryptedPassword(User $user, User $actor, ?string $reason = null): string
    {
        if (! $actor->hasPermission('view_devotee_passwords')) {
            throw new RuntimeException(__('devotee.unauthorized_password_view'));
        }

        $credential = DevoteeCredential::where('user_id', $user->id)->first();
        if (! $credential || empty($credential->encrypted_password)) {
            throw new RuntimeException(__('devotee.no_credentials_found'));
        }

        try {
            $decrypted = Crypt::decryptString($credential->encrypted_password);
        } catch (Exception $e) {
            Log::error("Failed to decrypt devotee credential for User ID {$user->id}: ".$e->getMessage());
            throw new RuntimeException(__('devotee.credential_decryption_failed'));
        }

        AuditLogger::log(
            'devotee_password_viewed',
            "Administrator #{$actor->id} ({$actor->name}) viewed password for devotee User #{$user->id} ({$user->name})",
            $user,
            [
                'actor_id' => $actor->id,
                'devotee_user_id' => $user->id,
                'reason' => $reason,
                'ip' => request()->ip(),
            ]
        );

        return $decrypted;
    }

    /**
     * Automatically provision a devotee login account for a Person profile.
     * Prevents duplicate accounts and enforces unambiguous phone matching.
     */
    public function provisionDevoteeAccount(
        Person $person,
        ?string $initialPassword = null,
        ?User $actor = null
    ): User {
        // 1. Check if Person already has an active User account
        if ($person->user_id) {
            $existing = User::find($person->user_id);
            if ($existing) {
                return $existing;
            }
        }

        $existingLinked = User::where('person_id', $person->id)->first();
        if ($existingLinked) {
            return $existingLinked;
        }

        // 2. Safe phone matching to avoid silent or ambiguous linking
        $cleanPhone = ! empty($person->phone) ? trim($person->phone) : null;
        if ($cleanPhone) {
            $variants = User::getPhoneVariants($cleanPhone);
            $matchedUsers = User::whereIn('phone', $variants)->get();

            if ($matchedUsers->count() > 1) {
                throw new RuntimeException(__('devotee.ambiguous_phone_error', ['phone' => $cleanPhone]));
            }

            if ($matchedUsers->count() === 1) {
                $matchedUser = $matchedUsers->first();
                if ($matchedUser->person_id && (int) $matchedUser->person_id !== (int) $person->id) {
                    throw new RuntimeException(__('devotee.phone_already_linked_different_person', ['phone' => $cleanPhone]));
                }

                // Safely link the existing account
                $matchedUser->person_id = $person->id;
                $matchedUser->save();

                return $matchedUser;
            }
        }

        // 3. Create fresh User account linked to Person
        $plainPassword = $initialPassword ?: $this->generateSecurePassword(12);

        return DB::transaction(function () use ($person, $cleanPhone, $plainPassword, $actor) {
            $user = User::create([
                'person_id' => $person->id,
                'name' => $person->name_en,
                'phone' => $cleanPhone,
                'email' => ! empty($person->email) ? trim($person->email) : null,
                'password' => Hash::make($plainPassword),
                'status' => $person->status === 'active' ? 'active' : 'inactive',
                'preferred_locale' => app()->getLocale(),
                'address' => $person->present_address,
            ]);

            // Assign 'devotee' role
            $devoteeRole = Role::where('slug', 'devotee')->first();
            if ($devoteeRole) {
                $user->assignRole($devoteeRole);
            }

            // Store encrypted password copy
            DevoteeCredential::create([
                'user_id' => $user->id,
                'encrypted_password' => Crypt::encryptString($plainPassword),
            ]);

            AuditLogger::log(
                'devotee_account_provisioned',
                "Provisioned devotee login account for {$person->name_en} ({$person->person_code}) - Phone: {$cleanPhone}",
                $user,
                [
                    'actor_id' => $actor?->id,
                    'person_id' => $person->id,
                    'user_id' => $user->id,
                    'ip' => request()->ip(),
                ]
            );

            return $user;
        });
    }
}
