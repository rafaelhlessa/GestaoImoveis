<?php

use App\Console\Commands\MigrateUserProfiles;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('migrate:user-profiles', function () {
    (new MigrateUserProfiles())->handle();
})->purpose('Migra os perfis antigos para a nova estrutura acumulável de perfis (profiles).');
