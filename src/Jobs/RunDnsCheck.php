<?php

namespace PeterSowah\Heimdall\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PeterSowah\Heimdall\Models\Check;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Services\Checkers\DnsCheckerService;
use PeterSowah\Heimdall\Services\Notifications\EmailNotificationService;
use PeterSowah\Heimdall\Services\Notifications\SlackNotificationService;
use PeterSowah\Heimdall\Services\Notifications\TelegramNotificationService;

class RunDnsCheck implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Domain $domain) {}

    public function handle(
        DnsCheckerService $checker,
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
            'type' => 'dns',
            'status' => $result['status'],
            'checked_at' => now(),
            'value' => $result['value'],
            'message' => $result['message'],
            'raw_data' => $result['raw_data'],
        ]);

        if ($this->domain->notify_dns && in_array($result['status'], ['critical', 'error'])) {
            $alertType = "dns_{$result['status']}";

            $slack->send($this->domain, $alertType, $result['message']);
            $telegram->send($this->domain, $alertType, $result['message']);
            $email->send($this->domain, $alertType, $result['message']);
        }
    }
}
