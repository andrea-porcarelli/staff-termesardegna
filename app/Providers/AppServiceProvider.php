<?php

namespace App\Providers;

use App\Models\Intervention;
use App\Observers\InterventionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Intervention::observe(InterventionObserver::class);
    }
}
