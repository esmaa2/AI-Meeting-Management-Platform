<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'display_name',
        'biography',
        'role',
        'plan',
        'department',
        'job_title',
        'avatar_url',
        'storage_used',
        'storage_limit',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // ── Computed ──────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1)
        );
    }

    public function getStorageUsedPercentAttribute(): int
    {
        $limit = $this->meetings_limit ?? 0;

    if ($limit <= 0) {
        return 0;
    }

    return ($this->meetings_used / $limit) * 100;
}

    // ── Relationships ──────────────────────────────────────────────────────────

    public function ownedMeetings()
    {
        return $this->hasMany(Meeting::class, 'owner_id');
    }

    public function attendedMeetings()
    {
        return $this->belongsToMany(Meeting::class, 'meeting_attendees')
                    ->withPivot('is_host', 'talk_time_seconds')
                    ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function emailDrafts()
    {
        return $this->hasMany(EmailDraft::class, 'created_by');
    }

    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function sentInvites()
    {
        return $this->hasMany(TeamInvite::class, 'invited_by');
    }
}