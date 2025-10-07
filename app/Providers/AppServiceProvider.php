<?php

namespace App\Providers;
use Livewire\Livewire;
use App\Http\Livewire\CrossTrace;

use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (class_exists(Livewire::class) && class_exists(CrossTrace::class)) {
        // friendly alias (use this in Blade: @livewire('cross-trace') )
        Livewire::component('cross-trace', CrossTrace::class);

        // also register the fully-qualified style alias Livewire sometimes emits
        // (this avoids "Unable to find component: [app.http.livewire.cross-trace]" problems)
        Livewire::component('app.http.livewire.cross-trace', CrossTrace::class);
    }
    }
}
