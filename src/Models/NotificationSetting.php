<?php

namespace PeterSowah\Heimdall\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'slack_webhook_url',
    'telegram_bot_token',
    'telegram_chat_id',
    'notification_emails',
])]
class NotificationSetting extends Model
{
    protected $table = 'heimdall_notification_settings';

    protected function casts(): array
    {
        return [
            'notification_emails' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class));
    }
}
