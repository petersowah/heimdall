<?php

namespace PeterSowah\Heimdall\Services\Checkers;

use Exception;
use Illuminate\Support\Facades\Http;
use PeterSowah\Heimdall\Models\Domain;

class UptimeCheckerService
{
    public function check(Domain $domain): array
    {
        try {
            $startTime = microtime(true);

            $response = Http::timeout(10)
                ->withoutVerifying()
                ->get("https://{$domain->name}");

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $httpStatus = $response->status();

            $status = match (true) {
                $httpStatus >= 500 => 'critical',
                $httpStatus >= 400 => 'critical',
                $httpStatus >= 300 || $responseTimeMs > 3000 => 'warning',
                default => 'ok',
            };

            return [
                'status' => $status,
                'value' => $responseTimeMs,
                'message' => "HTTP {$httpStatus} in {$responseTimeMs}ms",
                'raw_data' => [
                    'http_status' => $httpStatus,
                    'response_time_ms' => $responseTimeMs,
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'value' => null,
                'message' => "Request failed: {$e->getMessage()}",
                'raw_data' => ['error' => $e->getMessage()],
            ];
        }
    }
}
