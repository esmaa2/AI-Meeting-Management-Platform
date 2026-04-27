<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'auto_send_summary_emails',
        'create_tasks_from_action_items',
        'weekly_digest_report',
        'email_notifications',
        'push_notifications',
    ];

    protected $casts = [
        'auto_send_summary_emails'       => 'boolean',
        'create_tasks_from_action_items' => 'boolean',
        'weekly_digest_report'           => 'boolean',
        'email_notifications'            => 'boolean',
        'push_notifications'             => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}