<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup giornaliero del DB (con retention 10 giorni gestita dal command stesso).
Schedule::command('db:backup')
    ->dailyAt('03:00')
    ->onOneServer()
    ->withoutOverlapping();

// Riepilogo manutenzioni del giorno precedente, consegnato via email ogni mattina.
Schedule::command('maintenance:daily-report --mail=direzione@termesardegna.it')
    ->dailyAt('07:00')
    ->timezone('Europe/Rome')
    ->onOneServer()
    ->withoutOverlapping();
