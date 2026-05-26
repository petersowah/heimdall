<?php

namespace PeterSowah\Heimdall\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'started_at' => $this->started_at->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'duration_minutes' => $this->resolved_at
                ? (int) $this->started_at->diffInMinutes($this->resolved_at)
                : null,
            'details' => $this->details,
        ];
    }
}
