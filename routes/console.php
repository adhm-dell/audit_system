<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled Commands ──
Schedule::command('installments:mark-overdue')->daily()->at('00:30');
Schedule::command('notifications:check-thresholds')->daily()->at('01:00');
