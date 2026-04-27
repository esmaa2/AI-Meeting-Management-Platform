<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'created_by',
        'subject',
        'body',
        'recipients',
        'status',
        'include_recording',
        'request_read_receipt',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'recipients'           => 'array',
        'include_recording'    => 'boolean',
        'request_read_receipt' => 'boolean',
        'scheduled_at'         => 'datetime',
        'sent_at'              => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}