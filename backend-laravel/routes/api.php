<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailDraftController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MeetlyAI API Routes
|--------------------------------------------------------------------------
|
| All routes return JSON. Protected routes require a Bearer token obtained
| from POST /api/auth/login or POST /api/auth/register.
|
*/

// ── Public auth routes ───────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register',       [AuthController::class, 'register']);
    Route::post('login',          [AuthController::class, 'login']);
    Route::post('invites/accept', [TeamController::class, 'acceptInvite']); // accept invite before login
});

// ── Protected routes (Sanctum token required) ────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('me',          [AuthController::class, 'me']);
        Route::post('logout',     [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });

    // User profile & settings
    Route::prefix('user')->group(function () {
        Route::get('dashboard',       [UserController::class, 'dashboard']);
        Route::get('profile',         [UserController::class, 'profile']);
        Route::patch('profile',       [UserController::class, 'updateProfile']);
        Route::post('avatar',         [UserController::class, 'uploadAvatar']);
        Route::patch('password',      [UserController::class, 'updatePassword']);
        Route::get('settings',        [UserController::class, 'settings']);
        Route::patch('settings',      [UserController::class, 'updateSettings']);
    });

    // Meetings
    Route::prefix('meetings')->group(function () {
        Route::get('/',                                          [MeetingController::class, 'index']);
        Route::post('/',                                         [MeetingController::class, 'store']);
        Route::get('{meeting}',                                  [MeetingController::class, 'show']);
        Route::patch('{meeting}',                                [MeetingController::class, 'update']);
        Route::delete('{meeting}',                               [MeetingController::class, 'destroy']);
        Route::get('{meeting}/stats',                            [MeetingController::class, 'stats']);
        Route::post('{meeting}/attendees',                       [MeetingController::class, 'addAttendees']);
        Route::delete('{meeting}/attendees/{userId}',            [MeetingController::class, 'removeAttendee']);
    });

    // Tasks (Kanban)
    Route::prefix('tasks')->group(function () {
        Route::get('board',          [TaskController::class, 'board']);
        Route::get('/',              [TaskController::class, 'index']);
        Route::post('/',             [TaskController::class, 'store']);
        Route::get('{task}',         [TaskController::class, 'show']);
        Route::patch('{task}',       [TaskController::class, 'update']);
        Route::patch('{task}/move',  [TaskController::class, 'move']);
        Route::delete('{task}',      [TaskController::class, 'destroy']);
    });

    // Email Drafts
    Route::prefix('email-drafts')->group(function () {
        Route::get('/',                          [EmailDraftController::class, 'index']);
        Route::post('/',                         [EmailDraftController::class, 'store']);
        Route::get('{emailDraft}',               [EmailDraftController::class, 'show']);
        Route::patch('{emailDraft}',             [EmailDraftController::class, 'update']);
        Route::delete('{emailDraft}',            [EmailDraftController::class, 'destroy']);
        Route::post('{emailDraft}/send',         [EmailDraftController::class, 'send']);
        Route::post('{emailDraft}/regenerate',   [EmailDraftController::class, 'regenerate']);
    });

    // Team
    Route::prefix('team')->group(function () {
        Route::get('stats',                      [TeamController::class, 'stats']);
        Route::get('/',                          [TeamController::class, 'index']);
        Route::get('{user}',                     [TeamController::class, 'show']);
        Route::post('invite',                    [TeamController::class, 'invite']);
        Route::get('invites',                    [TeamController::class, 'invites']);
        Route::delete('invites/{invite}',        [TeamController::class, 'cancelInvite']);
    });

    // Integrations
    Route::prefix('integrations')->group(function () {
        Route::get('/',                          [IntegrationController::class, 'index']);
        Route::post('{provider}/connect',        [IntegrationController::class, 'connect']);
        Route::delete('{provider}',              [IntegrationController::class, 'disconnect']);
        Route::patch('{provider}',               [IntegrationController::class, 'update']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/',                          [NotificationController::class, 'index']);
        Route::get('unread-count',               [NotificationController::class, 'unreadCount']);
        Route::post('mark-all-read',             [NotificationController::class, 'markAllRead']);
        Route::patch('{id}/read',                [NotificationController::class, 'markRead']);
        Route::delete('{id}',                    [NotificationController::class, 'destroy']);
    });
Route::post('/meetings/{meeting}/analyze', [MeetingController::class, 'analyze']);


});