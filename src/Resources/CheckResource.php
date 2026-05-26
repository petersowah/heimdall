<?php

namespace PeterSowah\Heimdall\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'checked_at' => $this->checked_at->toIso8601String(),
            'value' => $this->value,
            'message' => $this->message,
        ];
    }
}
