<?php

namespace PeterSowah\Heimdall\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestChecks = $this->whenLoaded('latestChecks', function () {
            return CheckResource::collection($this->latestChecks)->keyBy('type');
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'check_interval_minutes' => $this->check_interval_minutes,
            'notify_ssl' => $this->notify_ssl,
            'notify_domain_expiry' => $this->notify_domain_expiry,
            'notify_uptime' => $this->notify_uptime,
            'notify_dns' => $this->notify_dns,
            'created_at' => $this->created_at->toIso8601String(),
            'latest_checks' => $latestChecks,
            'open_incidents_count' => $this->whenLoaded('incidents', function () {
                return $this->incidents->where('status', 'open')->count();
            }),
        ];
    }
}
