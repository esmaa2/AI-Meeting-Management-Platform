<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // GET /api/tasks
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['assignee', 'creator', 'meeting'])
            ->where(function ($q) use ($request) {
                $q->where('created_by', $request->user()->id)
                  ->orWhere('assigned_to', $request->user()->id);
            });

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->filled('meeting_id')) {
            $query->where('meeting_id', $request->meeting_id);
        }
        if ($request->boolean('overdue')) {
            $query->overdue();
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $tasks = $query->orderBy('due_date')->paginate($request->integer('per_page', 50));

        return response()->json($tasks);
    }

    // GET /api/tasks/board — grouped by status for kanban
    public function board(Request $request): JsonResponse
    {
        $base = Task::with(['assignee', 'creator', 'meeting'])
            ->where(function ($q) use ($request) {
                $q->where('created_by', $request->user()->id)
                  ->orWhere('assigned_to', $request->user()->id);
            });

        $columns = [
            'backlog'     => (clone $base)->byStatus('backlog')->latest()->get(),
            'in_progress' => (clone $base)->byStatus('in_progress')->latest()->get(),
            'in_review'   => (clone $base)->byStatus('in_review')->latest()->get(),
            'done'        => (clone $base)->byStatus('done')->latest()->limit(20)->get(),
        ];

        return response()->json([
            'columns' => $columns,
            'counts'  => [
                'backlog'     => $columns['backlog']->count(),
                'in_progress' => $columns['in_progress']->count(),
                'in_review'   => $columns['in_review']->count(),
                'done'        => $columns['done']->count(),
            ],
        ]);
    }

    // POST /api/tasks
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'meeting_id'  => ['nullable', 'exists:meetings,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'title'       => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'status'      => ['sometimes', 'in:backlog,in_progress,in_review,done'],
            'priority'    => ['sometimes', 'in:low,medium,high,critical'],
            'department'  => ['nullable', 'string', 'max:100'],
            'due_date'    => ['nullable', 'date'],
            'progress'    => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        $task = Task::create(array_merge($data, [
            'created_by' => $request->user()->id,
        ]));

        return response()->json([
            'message' => 'Task created.',
            'task'    => $task->load(['assignee', 'creator', 'meeting']),
        ], 201);
    }

    // GET /api/tasks/{task}
    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTask($request, $task);

        return response()->json([
            'task' => $task->load(['assignee', 'creator', 'meeting.owner']),
        ]);
    }

    // PATCH /api/tasks/{task}
    public function update(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTask($request, $task);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'title'       => ['sometimes', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'status'      => ['sometimes', 'in:backlog,in_progress,in_review,done'],
            'priority'    => ['sometimes', 'in:low,medium,high,critical'],
            'department'  => ['nullable', 'string', 'max:100'],
            'due_date'    => ['nullable', 'date'],
            'progress'    => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);

        // Auto-set progress when marking done
        if (isset($data['status']) && $data['status'] === 'done') {
            $data['progress'] = 100;
        }

        $task->update($data);

        return response()->json([
            'message' => 'Task updated.',
            'task'    => $task->fresh(['assignee', 'creator', 'meeting']),
        ]);
    }

    // PATCH /api/tasks/{task}/move — quick kanban column move
    public function move(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTask($request, $task);

        $data = $request->validate([
            'status' => ['required', 'in:backlog,in_progress,in_review,done'],
        ]);

        $task->update([
            'status'   => $data['status'],
            'progress' => $data['status'] === 'done' ? 100 : $task->progress,
        ]);

        return response()->json(['message' => 'Task moved.', 'task' => $task->fresh()]);
    }

    // DELETE /api/tasks/{task}
    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTask($request, $task);

        $task->delete();

        return response()->json(['message' => 'Task deleted.']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function authorizeTask(Request $request, Task $task): void
    {
        $userId = $request->user()->id;
        if ($task->created_by !== $userId && $task->assigned_to !== $userId) {
            abort(403, 'Access denied.');
        }
    }
}