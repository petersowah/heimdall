<?php

namespace PeterSowah\Heimdall\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['domain_id', 'type', 'status', 'checked_at', 'value', 'message', 'raw_data'])]
class Check extends Model
{
    protected $table = 'heimdall_checks';

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
