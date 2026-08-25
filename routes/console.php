<?php

use App\Jobs\NotifyOverdueTasksJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new NotifyOverdueTasksJob)
    ->dailyAt('08:00')
    ->withoutOverlapping();
