<?php

namespace PeterSowah\Heimdall\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PeterSowah\Heimdall\Models\Check;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Services\Checkers\SslCheckerService;
use PeterSowah\Heimdall\Services\Notifications\EmailNotificationService;
use PeterSowah\Heimdall\Services\Notifications\SlackNotificationService;
use PeterSowah\Heimdall\Services\Notifications\TelegramNotificationService;

class RunSslCheck implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Domain $domain) {}

    public function handle(
        SslCheckerService $checker,
        SlackNotificationService $slack,
        TelegramNotificationService $telegram,
        EmailNotificationService $email
    ): void {
        if (! $this->domain->is_active) {
            return;
        }

        $result = $checker->check($this->domain);

        Check::create([
            'domain_id' => $this->domain->id,
            'type' => 'ssl',
            'status' => $result['status'],
            'checked_at' => now(),
            'value' => $result['value'],
            'message' => $result['message'],
            'raw_data' => $result['raw_data'],
        ]);

        if ($this->domain->notify_ssl && in_array($result['status'], ['warning', 'critical', 'error'])) {
            $alertType = "ssl_{$result['status']}";
            $fields = $result['value'] !== null ? ['Days Remaining' => $result['value']] : [];

            $slack->send($this->domain, $alertType, $result['message'], $fields);
            $telegram->send($this->domain, $alertType, $result['message'], $fields);
            $email->send($this->domain, $alertType, $result['message'], $fields);
        }
    }
}
