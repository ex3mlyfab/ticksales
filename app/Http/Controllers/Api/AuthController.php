<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Log in an authenticated API user.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Authenticated.',
            'user' => $this->serializeUser(Auth::user()),
        ]);
    }

    /**
     * Get the current authenticated user profile.
     */
    public function me(Request $request)
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
     * Log out the current authenticated API user.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

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
