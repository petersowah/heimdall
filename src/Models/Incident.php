<?php

namespace PeterSowah\Heimdall\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['domain_id', 'type', 'status', 'started_at', 'resolved_at', 'details'])]
class Incident extends Model
{
    protected $table = 'heimdall_incidents';

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
