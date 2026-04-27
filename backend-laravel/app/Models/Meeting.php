<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'title',
        'analysis_profile',
        'transcript',
        'ai_summary',
        'audio_file_path',
        'audio_file_name',
        'audio_file_size',
        'duration_seconds',
        'word_count',
        'sentiment',
        'status',
        'department',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    // ── Computed ──────────────────────────────────────────────────────────────

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) return '—';
        $mins = intdiv($this->duration_seconds, 60);
        $secs = $this->duration_seconds % 60;
        return $mins > 0 ? "{$mins} min" . ($secs > 0 ? " {$secs} sec" : '') : "{$secs} sec";
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class, 'meeting_attendees')
                    ->withPivot('is_host', 'talk_time_seconds')
                    ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function emailDrafts()
    {
        return $this->hasMany(EmailDraft::class);
    }
}