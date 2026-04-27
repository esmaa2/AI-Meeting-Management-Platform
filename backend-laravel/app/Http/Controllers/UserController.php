<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // GET /api/user/profile
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('settings', 'integrations'),
        ]);
    }

    // PATCH /api/user/profile
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name'   => ['sometimes', 'string', 'max:100'],
            'last_name'    => ['sometimes', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'biography'    => ['nullable', 'string', 'max:1000'],
            'department'   => ['nullable', 'string', 'max:100'],
            'job_title'    => ['nullable', 'string', 'max:100'],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated.',
            'user'    => $user->fresh(),
        ]);
    }

    // POST /api/user/avatar
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');

        $user->update(['avatar_url' => "/storage/{$path}"]);

        return response()->json([
            'message'    => 'Avatar updated.',
            'avatar_url' => $user->avatar_url,
        ]);
    }

    // PATCH /api/user/password
    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        // Revoke all other tokens for security
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password updated. Other sessions have been revoked.']);
    }

    // GET /api/user/settings
    public function settings(Request $request): JsonResponse
    {
        $settings = $request->user()->settings ?? UserSetting::create([
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['settings' => $settings]);
    }

    // PATCH /api/user/settings
    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'auto_send_summary_emails'       => ['sometimes', 'boolean'],
            'create_tasks_from_action_items' => ['sometimes', 'boolean'],
            'weekly_digest_report'           => ['sometimes', 'boolean'],
            'email_notifications'            => ['sometimes', 'boolean'],
            'push_notifications'             => ['sometimes', 'boolean'],
        ]);

        $settings = $request->user()->settings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return response()->json([
            'message'  => 'Settings updated.',
            'settings' => $settings,
        ]);
    }

    // GET /api/user/dashboard — stats for the dashboard page
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $meetingsProcessed = $user->ownedMeetings()->where('status', 'ready')->count();
        $tasksGenerated    = $user->createdTasks()->count();
        $summariesSent     = $user->emailDrafts()->where('status', 'sent')->count();

        $recentMeetings = $user->ownedMeetings()
            ->with(['attendees'])
            ->latest()
            ->limit(5)
            ->get();

        $upcomingTasks = $user->tasks()
            ->with('meeting')
            ->whereNotIn('status', ['done'])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->limit(3)
            ->get();

        $sentimentBreakdown = $user->ownedMeetings()
            ->whereNotNull('sentiment')
            ->where('status', 'ready')
            ->selectRaw('sentiment, count(*) as count')
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment');

        return response()->json([
            'stats' => [
                'meetings_processed' => $meetingsProcessed,
                'tasks_generated'    => $tasksGenerated,
                'summaries_sent'     => $summariesSent,
            ],
            'recent_meetings'    => $recentMeetings,
            'upcoming_tasks'     => $upcomingTasks,
            'sentiment_breakdown'=> $sentimentBreakdown,
            'storage_used_percent' => $user->storage_used_percent,
        ]);
    }
}