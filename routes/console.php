<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('saas:check-trial-expiry')->daily();
Schedule::command('saas:health-score')->daily();
Schedule::command('saas:retention-alerts')->daily();
Schedule::command('saas:winback-emails')->daily();
