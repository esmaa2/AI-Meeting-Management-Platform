<?php

namespace App\Http\Controllers;

use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TeamController extends Controller
{
    // GET /api/team — list all users in workspace
    public function index(Request $request): JsonResponse
{
    $currentUser = $request->user();

    if (!$currentUser->team_id) {
        return response()->json([
            'data' => [],
            'message' => 'You are not assigned to a team yet.'
        ]);
    }

    $query = User::with('settings')
        ->where('team_id', $currentUser->team_id)
        ->select([
            'id',
            'team_id',
            'first_name',
            'last_name',
            'display_name',
            'email',
            'role',
            'plan',
            'department',
            'job_title',
            'avatar_url',
            'created_at',
        ]);

    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('first_name', 'like', '%' . $request->search . '%')
              ->orWhere('last_name', 'like', '%' . $request->search . '%')
              ->orWhere('email', 'like', '%' . $request->search . '%');
        });
    }

    if ($request->filled('department')) {
        $query->where('department', $request->department);
    }

    $users = $query->paginate($request->integer('per_page', 24));

    $users->getCollection()->transform(function (User $user) {
        $user->setAttribute(
            'meetings_per_month',
            $user->ownedMeetings()
                ->whereMonth('created_at', now()->month)
                ->count()
        );

        return $user;
    });

    return response()->json($users);
}

    // GET /api/team/stats
   public function stats(Request $request): JsonResponse
{
    $currentUser = $request->user();

    if (!$currentUser->team_id) {
        return response()->json([
            'total_members' => 0,
            'active_this_week' => 0,
            'new_this_month' => 0,
            'pending_invites' => 0,
        ]);
    }

    $teamUsers = User::where('team_id', $currentUser->team_id);

    return response()->json([
        'total_members' => (clone $teamUsers)->count(),
        'active_this_week' => (clone $teamUsers)->where('updated_at', '>=', now()->subWeek())->count(),
        'new_this_month' => (clone $teamUsers)->whereMonth('created_at', now()->month)->count(),
        'pending_invites' => TeamInvite::where('status', 'pending')
            ->where('invited_by', $currentUser->id)
            ->count(),
    ]);
}

    // GET /api/team/{user}
    public function show(Request $request, User $user): JsonResponse
    {
        $meetings = $user->ownedMeetings()->count();
        $tasks    = $user->tasks()->count();

        return response()->json([
            'user' => array_merge($user->only([
                'id', 'first_name', 'last_name', 'display_name', 'email',
                'role', 'plan', 'department', 'job_title', 'avatar_url',
                'biography', 'created_at',
            ]), [
                'initials'           => $user->initials,
                'full_name'          => $user->full_name,
                'total_meetings'     => $meetings,
                'total_tasks'        => $tasks,
                'meetings_this_month'=> $user->ownedMeetings()
                                             ->whereMonth('created_at', now()->month)
                                             ->count(),
            ]),
        ]);
    }

    // POST /api/team/invite
    public function invite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Don't invite existing members
        if (User::where('email', $data['email'])->exists()) {
            return response()->json(['message' => 'User is already a member.'], 422);
        }

        // Expire old invites for same email
        TeamInvite::where('email', $data['email'])
                  ->where('status', 'pending')
                  ->update(['status' => 'expired']);

        $invite = TeamInvite::create([
            'invited_by' => $request->user()->id,
            'email'      => $data['email'],
            'token'      => TeamInvite::generateToken(),
            'status'     => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // TODO: Mail::to($invite->email)->send(new TeamInviteEmail($invite));

        return response()->json([
            'message' => "Invitation sent to {$invite->email}.",
            'invite'  => $invite,
        ], 201);
    }

    // GET /api/team/invites — list pending invites
    public function invites(Request $request): JsonResponse
    {
        $invites = TeamInvite::with('inviter')
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return response()->json(['invites' => $invites]);
    }

    // DELETE /api/team/invites/{invite} — cancel invite
    public function cancelInvite(Request $request, TeamInvite $invite): JsonResponse
    {
        if ($invite->invited_by !== $request->user()->id && $request->user()->role !== 'admin') {
            abort(403, 'Access denied.');
        }

        $invite->update(['status' => 'expired']);

        return response()->json(['message' => 'Invite cancelled.']);
    }

    // POST /api/team/invites/accept — accept invite via token
    public function acceptInvite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $invite = TeamInvite::where('token', $data['token'])
                            ->where('status', 'pending')
                            ->where('expires_at', '>', now())
                            ->firstOrFail();

        $invite->update(['status' => 'accepted']);

        return response()->json(['message' => 'Invite accepted.', 'email' => $invite->email]);
    }
}