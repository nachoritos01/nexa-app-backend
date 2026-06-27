<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\StorePushTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'error' => 'Invalid credentials.',
                'code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        $deviceName = $validated['device_name'] ?? 'mobile';
        $token = $user->createToken($deviceName);

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
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
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
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

    public function pushToken(StorePushTokenRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->update([
            'push_token' => $request->validated('push_token'),
        ]);

        return response()->json([
            'message' => 'Push token stored successfully.',
        ]);
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
