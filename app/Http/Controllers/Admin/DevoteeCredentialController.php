<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Services\DevoteeCredentialService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DevoteeCredentialController extends Controller
{
    public function __construct(
        protected DevoteeCredentialService $credentialService
    ) {}

    /**
     * Reveal devotee password for authorized admin.
     */
    public function reveal(Request $request, Person $person): JsonResponse
    {
        $actor = $request->user();
        if (! $actor || ! $actor->hasPermission('view_devotee_passwords')) {
            abort(403, __('devotee.unauthorized_password_view'));
        }

        $user = $person->resolveUser();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('devotee.no_account_linked'),
            ], 404);
        }

        try {
            $reason = $request->input('reason', 'Administrative inspection');
            $decrypted = $this->credentialService->getDecryptedPassword($user, $actor, $reason);

            return response()->json([
                'success' => true,
                'password' => $decrypted,
                'login_identifier' => $user->phone ?: $user->email,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reset or update devotee password by authorized admin.
     */
    public function reset(Request $request, Person $person): JsonResponse|RedirectResponse
    {
        $actor = $request->user();
        if (! $actor || ! $actor->hasPermission('update_devotee_passwords')) {
            abort(403, __('devotee.unauthorized_password_update'));
        }

        $user = $person->resolveUser();
        if (! $user) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('devotee.no_account_linked'),
                ], 404);
            }

            return back()->with('error', __('devotee.no_account_linked'));
        }

        $request->validate([
            'new_password' => ['nullable', 'string', 'min:8'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $newPassword = $request->filled('new_password')
            ? (string) $request->input('new_password')
            : $this->credentialService->generateSecurePassword(12);

        $reason = $request->input('reason', 'Admin password reset');
        $this->credentialService->updateCredentials($user, $newPassword, $actor, $reason);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('devotee.password_reset_success'),
                'new_password' => $newPassword,
            ]);
        }

        return back()->with('success', __('devotee.password_reset_success_with_pass', ['password' => $newPassword]));
    }

    /**
     * Provision login account for an existing person record.
     */
    public function provision(Request $request, Person $person): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor || ! $actor->hasPermission('manage_people')) {
            abort(403);
        }

        if (empty($person->phone) && empty($person->email)) {
            return back()->with('error', __('devotee.phone_or_email_required_for_account'));
        }

        try {
            $user = $this->credentialService->provisionDevoteeAccount($person, null, $actor);

            return back()->with('success', __('devotee.account_provisioned_successfully', [
                'identifier' => $user->phone ?: $user->email,
            ]));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
