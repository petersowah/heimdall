<?php

namespace PeterSowah\Heimdall\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['domain_id', 'channel', 'alert_type', 'sent_at', 'payload'])]
class AlertLog extends Model
{
    protected $table = 'heimdall_alert_logs';

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
