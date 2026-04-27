<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // POST /api/auth/register
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', Password::min(8)->mixedCase()->numbers()],
            'plan'       => ['sometimes', 'in:free,pro,enterprise'],
        ]);

        $user = User::create([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'password'      => Hash::make($data['password']),
            'plan'          => $data['plan'] ?? 'free',
            'display_name'  => $data['first_name'] . ' ' . substr($data['last_name'], 0, 1) . '.',
        ]);

        // Create default settings for new user
        UserSetting::create(['user_id' => $user->id]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully.',
            'user'    => $this->userResource($user),
            'token'   => $token,
        ], 201);
    }

    // POST /api/auth/login
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'user'    => $this->userResource($user),
            'token'   => $token,
        ]);
    }

    // POST /api/auth/logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    // GET /api/auth/me
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userResource($request->user()->load('settings')),
        ]);
    }

    // POST /api/auth/logout-all — revoke all tokens
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'All sessions revoked.']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function userResource(User $user): array
    {
        return [
            'id'                   => $user->id,
            'first_name'           => $user->first_name,
            'last_name'            => $user->last_name,
            'full_name'            => $user->full_name,
            'initials'             => $user->initials,
            'display_name'         => $user->display_name,
            'email'                => $user->email,
            'biography'            => $user->biography,
            'role'                 => $user->role,
            'plan'                 => $user->plan,
            'department'           => $user->department,
            'job_title'            => $user->job_title,
            'avatar_url'           => $user->avatar_url,
            'storage_used'         => $user->storage_used,
            'storage_limit'        => $user->storage_limit,
            'storage_used_percent' => $user->storage_used_percent,
            'settings'             => $user->settings,
            'created_at'           => $user->created_at,
        ];
    }
}