<?php

namespace PeterSowah\Heimdall\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PeterSowah\Heimdall\Models\Check;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Models\Incident;
use PeterSowah\Heimdall\Services\Checkers\UptimeCheckerService;
use PeterSowah\Heimdall\Services\Notifications\EmailNotificationService;
use PeterSowah\Heimdall\Services\Notifications\SlackNotificationService;
use PeterSowah\Heimdall\Services\Notifications\TelegramNotificationService;

class RunUptimeCheck implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Domain $domain) {}

    public function handle(
        UptimeCheckerService $checker,
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
            'type' => 'uptime',
            'status' => $result['status'],
            'checked_at' => now(),
            'value' => $result['value'],
            'message' => $result['message'],
            'raw_data' => $result['raw_data'],
        ]);

        $isDown = in_array($result['status'], ['critical', 'error']);

        if ($isDown) {
            $this->handleDowntime($result, $slack, $telegram, $email);
        } else {
            $this->handleRecovery($result, $slack, $telegram, $email);
        }
    }

    private function handleDowntime(
        array $result,
        SlackNotificationService $slack,
        TelegramNotificationService $telegram,
        EmailNotificationService $email
    ): void {
        $recentFailures = $this->domain->checks()
            ->where('type', 'uptime')
            ->where('status', 'critical')
            ->where('checked_at', '>=', now()->subMinutes(20))
            ->count();

        $openIncident = $this->domain->incidents()
            ->where('type', 'uptime')
            ->where('status', 'open')
            ->first();

        if ($recentFailures >= 3 && ! $openIncident) {
            Incident::create([
                'domain_id' => $this->domain->id,
                'type' => 'uptime',
                'status' => 'open',
                'started_at' => now(),
                'details' => $result['message'],
            ]);

            if ($this->domain->notify_uptime) {
                $slack->send($this->domain, 'uptime_critical', $result['message']);
                $telegram->send($this->domain, 'uptime_critical', $result['message']);
                $email->send($this->domain, 'uptime_critical', $result['message']);
            }
        }
    }

    private function handleRecovery(
        array $result,
        SlackNotificationService $slack,
        TelegramNotificationService $telegram,
        EmailNotificationService $email
    ): void {
        $openIncident = $this->domain->incidents()
            ->where('type', 'uptime')
            ->where('status', 'open')
            ->first();

        if ($openIncident) {
            $openIncident->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            if ($this->domain->notify_uptime) {
                $slack->send($this->domain, 'uptime_resolved', "Domain {$this->domain->name} is back online. {$result['message']}");
                $telegram->send($this->domain, 'uptime_resolved', "Domain {$this->domain->name} is back online. {$result['message']}");
                $email->send($this->domain, 'uptime_resolved', "Domain {$this->domain->name} is back online. {$result['message']}");
            }
        }
    }
}
