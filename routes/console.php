<?php

use App\Console\Commands\CleanupPublicPortalDemoData;
use App\Console\Commands\MigratePaymentProofsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::registerCommand(new MigratePaymentProofsCommand);
Artisan::registerCommand(new CleanupPublicPortalDemoData);
