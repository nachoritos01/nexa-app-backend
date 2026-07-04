<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Precomputed bcrypt hash (cost 12) of a random value. Used only to equalize
     * response time when the email doesn't exist, preventing timing-based user
     * enumeration. It is NOT a real password.
     */
    private const DUMMY_HASH = '$2y$12$OaVCrVbE4SIIupWXlPg5vuXJ1/q8JvvgVphYPDwdr9MpeBayaNLea';

    /**
     * Login.
     *
     * Public endpoint: exchange email + password for a Bearer token. Use the
     * returned token (and a tenant id from `tenants`) for the agency endpoints.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        // Always run a hash comparison (against a dummy hash when the user is
        // unknown) so response time doesn't reveal whether the email exists.
        if ($user) {
            $passwordValid = Hash::check($validated['password'], $user->password);
        } else {
            Hash::check($validated['password'], self::DUMMY_HASH);
            $passwordValid = false;
        }

        if (! $user || ! $passwordValid) {
            return response()->json([
                'error' => 'Invalid credentials.',
                'code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        $deviceName = $validated['device_name'] ?? 'mobile';
        $token = $user->createToken($deviceName);

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'user' => $this->userPayload($user),
            'tenants' => $this->getUserTenants($user->id),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->userPayload($user),
            'tenants' => $this->getUserTenants($user->id),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Build the authenticated-user payload shared by login() and me().
     *
     * `role` is the user's (global) spatie role; `permissions` is filtered to the
     * `agency.*` namespace so the panel only sees its own permissions, never the
     * e-commerce ones. The panel uses these to hide/disable write actions.
     *
     * @return array{id: int, name: string, email: string, role: string|null, permissions: list<string>}
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            'permissions' => $user->getAllPermissions()
                ->pluck('name')
                ->filter(fn (string $permission) => str_starts_with($permission, 'agency.'))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, slug: string, plan: string, role: string}>
     */
    private function getUserTenants(int $userId): array
    {
        /** @var list<object{id: int, name: string, slug: string, plan: string, role: string}> $rows */
        $rows = DB::select(
            'SELECT t.id, t.name, t.slug, t.plan, tu.role
             FROM tenants t
             INNER JOIN tenant_user tu ON tu.tenant_id = t.id
             WHERE tu.user_id = ?',
            [$userId]
        );

        return array_map(fn (object $row) => [
            'id' => (int) $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'plan' => $row->plan,
            'role' => $row->role,
        ], $rows);
    }
}
