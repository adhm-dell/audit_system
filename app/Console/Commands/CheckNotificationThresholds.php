<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckNotificationThresholds extends Command
{
    protected $signature = 'notifications:check-thresholds';
    protected $description = 'Check notification thresholds and create alerts for low balance, upcoming/overdue installments';

    public function handle(NotificationService $service): int
    {
        $this->info('Checking notification thresholds...');

        $service->checkThresholds();

        $this->info('Notification check completed.');

        return self::SUCCESS;
    }
}
