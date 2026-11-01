<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Exchange credentials for a Sanctum bearer token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api');

        return response()->json([
            'message' => 'Authenticated.',
            'token' => $token->plainTextToken,
            'user' => $this->serializeUser($user),
        ]);
    }

    /**
     * Get the current authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        return response()->json([
            'data' => $this->serializeUser($request->user()),
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'currency' => $tenant->currency,
            ] : null,
        ]);
    }

    /**
     * Revoke the token used to authenticate the request.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        // Bearer token requests: revoke the token. Session requests: the
        // sanctum guard hands back a TransientToken which is a no-op here.
        if ($token !== null && method_exists($token, 'delete')) {
            $token->delete();
        }

        // First-party stateful clients may also carry a session.
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    /**
     * Serialize the authenticated user for JSON responses.
     *
     * @return array<string, mixed>
     */
    protected function serializeUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_platform_admin' => (bool) $user->is_platform_admin,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
        ];
    }
}
