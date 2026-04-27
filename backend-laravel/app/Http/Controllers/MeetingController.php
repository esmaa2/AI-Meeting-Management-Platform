<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class MeetingController extends Controller
{
    // GET /api/meetings
    public function index(Request $request): JsonResponse
    {
        $query = Meeting::with(['owner', 'attendees'])
            ->where(function ($q) use ($request) {
                $q->where('owner_id', $request->user()->id)
                  ->orWhereHas('attendees', fn($q2) => $q2->where('users.id', $request->user()->id));
            });

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('sentiment')) {
            $query->where('sentiment', $request->sentiment);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $meetings = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($meetings);
    }

    // POST /api/meetings
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'analysis_profile' => ['sometimes', 'in:executive_summary,action_oriented,verbatim_archive'],
            'transcript'       => ['nullable', 'string'],
            'audio'            => ['nullable', 'file', 'mimes:mp3,wav,m4a', 'max:512000'], // 500MB
        ]);

        $meeting = Meeting::create([
            'owner_id'         => $request->user()->id,
            'title'            => $data['title'],
            'analysis_profile' => $data['analysis_profile'] ?? 'executive_summary',
            'transcript'       => $data['transcript'] ?? null,
            'status'           => 'processing',
        ]);

        // Handle audio file upload
        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            $path = $file->store("meetings/{$meeting->id}/audio", 'private');

            $meeting->update([
                'audio_file_path' => $path,
                'audio_file_name' => $file->getClientOriginalName(),
                'audio_file_size' => $file->getSize(),
            ]);

            // Update user storage
            $request->user()->increment('storage_used', $file->getSize());
        }

        // Attach owner as host attendee
        $meeting->attendees()->attach($request->user()->id, ['is_host' => true]);

        // TODO: Dispatch AI processing job here
        // ProcessMeetingJob::dispatch($meeting);

        // For now, simulate immediate processing
        $meeting->update([
            'status'     => 'ready',
            'ai_summary' => 'AI-generated summary will appear here after processing.',
            'sentiment'  => 'productive',
            'word_count' => $data['transcript'] ? str_word_count($data['transcript']) : 0,
        ]);

        return response()->json([
            'message' => 'Meeting created and processing started.',
            'meeting' => $meeting->load(['owner', 'attendees', 'tasks']),
        ], 201);
    }

    // GET /api/meetings/{meeting}
    public function show(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeAccess($request, $meeting);

        return response()->json([
            'meeting' => $meeting->load(['owner', 'attendees', 'tasks.assignee', 'emailDrafts']),
        ]);
    }

    // PATCH /api/meetings/{meeting}
    public function update(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeOwner($request, $meeting);

        $data = $request->validate([
            'title'            => ['sometimes', 'string', 'max:255'],
            'analysis_profile' => ['sometimes', 'in:executive_summary,action_oriented,verbatim_archive'],
            'transcript'       => ['nullable', 'string'],
            'ai_summary'       => ['nullable', 'string'],
            'sentiment'        => ['nullable', 'in:productive,informational,decision_focused'],
            'status'           => ['sometimes', 'in:processing,ready,failed'],
            'tags'             => ['nullable', 'array'],
            'tags.*'           => ['string', 'max:50'],
        ]);

        $meeting->update($data);

        return response()->json([
            'message' => 'Meeting updated.',
            'meeting' => $meeting->fresh(['owner', 'attendees', 'tasks']),
        ]);
    }

    // DELETE /api/meetings/{meeting}
    public function destroy(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeOwner($request, $meeting);

        // Free up storage
        if ($meeting->audio_file_path) {
            Storage::disk('private')->delete($meeting->audio_file_path);
            $request->user()->decrement('storage_used', $meeting->audio_file_size ?? 0);
        }

        $meeting->delete();

        return response()->json(['message' => 'Meeting deleted.']);
    }

    // POST /api/meetings/{meeting}/attendees
    public function addAttendees(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeOwner($request, $meeting);

        $data = $request->validate([
            'user_ids'   => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        foreach ($data['user_ids'] as $userId) {
            $meeting->attendees()->syncWithoutDetaching([$userId => ['is_host' => false]]);
        }

        return response()->json([
            'message'   => 'Attendees added.',
            'attendees' => $meeting->attendees()->get(),
        ]);
    }

    // DELETE /api/meetings/{meeting}/attendees/{user}
    public function removeAttendee(Request $request, Meeting $meeting, int $userId): JsonResponse
    {
        $this->authorizeOwner($request, $meeting);

        $meeting->attendees()->detach($userId);

        return response()->json(['message' => 'Attendee removed.']);
    }

    // GET /api/meetings/{meeting}/stats — meeting stats card
    public function stats(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeAccess($request, $meeting);

        $taskCount    = $meeting->tasks()->count();
        $completedTasks = $meeting->tasks()->where('status', 'done')->count();
        $talkTime     = $meeting->attendees()->sum('meeting_attendees.talk_time_seconds');

        return response()->json([
            'duration_seconds'   => $meeting->duration_seconds,
            'duration_formatted' => $meeting->duration_formatted,
            'talk_time_seconds'  => $talkTime,
            'word_count'         => $meeting->word_count,
            'task_count'         => $taskCount,
            'completed_tasks'    => $completedTasks,
            'sentiment'          => $meeting->sentiment,
            'attendee_count'     => $meeting->attendees()->count(),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function authorizeAccess(Request $request, Meeting $meeting): void
    {
        $userId = $request->user()->id;
        $isOwner = $meeting->owner_id === $userId;
        $isAttendee = $meeting->attendees()->where('users.id', $userId)->exists();

        if (!$isOwner && !$isAttendee) {
            abort(403, 'You do not have access to this meeting.');
        }
    }

    private function authorizeOwner(Request $request, Meeting $meeting): void
    {
        if ($meeting->owner_id !== $request->user()->id) {
            abort(403, 'Only the meeting owner can perform this action.');
        }
    }

 

public function analyze(Meeting $meeting)
{
    if (!$meeting->transcript) {
        return response()->json([
            'message' => 'This meeting has no transcript.'
        ], 422);
    }

    $response = Http::withToken(env('OPENAI_API_KEY'))
        ->post('https://api.openai.com/v1/responses', [
            'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
            'input' => "Return ONLY valid JSON. No markdown. No ```json.

Summarize this meeting and extract action tasks.

JSON format:
{
  \"summary\": \"...\",
  \"tasks\": [
    {
      \"title\": \"...\",
      \"assigned_to\": \"...\",
      \"priority\": \"medium\",
      \"due_date\": null
    }
  ]
}

Meeting title: {$meeting->title}

Transcript:
{$meeting->transcript}"
        ]);

    if (!$response->successful()) {
        return response()->json([
            'message' => 'AI request failed',
            'error' => $response->json()
        ], 500);
    }

    $text = $response->json('output.0.content.0.text');

    $text = preg_replace('/```json|```/', '', $text);
    $text = trim($text);

    $analysis = json_decode($text, true);

    if (!$analysis) {
        return response()->json([
            'message' => 'AI returned invalid JSON',
            'raw' => $text
        ], 500);
    }

    $meeting->update([
        'ai_summary' => $analysis['summary'] ?? '',
        'status' => 'ready',
        'word_count' => str_word_count($meeting->transcript),
    ]);

   $createdTasks = [];

foreach (($analysis['tasks'] ?? []) as $task) {
    $assignedName = trim($task['assigned_to'] ?? '');

    $assignedUser = null;

    if ($assignedName !== '') {
        $assignedUser = User::query()
            ->where('id', '!=', $meeting->owner_id)
            ->where(function ($q) use ($assignedName) {
                $q->where('first_name', 'like', '%' . $assignedName . '%')
                  ->orWhere('last_name', 'like', '%' . $assignedName . '%')
                  ->orWhere('display_name', 'like', '%' . $assignedName . '%')
                  ->orWhere('name', 'like', '%' . $assignedName . '%')
                  ->orWhere('email', 'like', '%' . $assignedName . '%');
            })
            ->first();
    }

    $createdTasks[] = Task::create([
        'meeting_id'  => $meeting->id,
        'created_by'  => $meeting->owner_id,
        'assigned_to' => $assignedUser?->id,
        'title'       => $task['title'] ?? 'Untitled task',
        'description' => $assignedUser
            ? 'Assigned to: ' . ($assignedUser->display_name ?? $assignedUser->name ?? $assignedUser->email)
            : 'Assigned to: ' . ($assignedName ?: 'Unassigned'),
        'priority'    => $task['priority'] ?? 'medium',
        'due_date'    => $task['due_date'] ?? null,
        'status'      => 'backlog',
    ]);
}
}
}