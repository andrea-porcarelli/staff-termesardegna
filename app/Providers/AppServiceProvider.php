<?php

namespace App\Providers;

use App\Models\Intervention;
use App\Observers\InterventionObserver;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Carbon::setLocale('it');

        Intervention::observe(InterventionObserver::class);
    }
}
