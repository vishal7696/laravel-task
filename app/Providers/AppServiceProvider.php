<?php

namespace App\Providers;

use App\Notifications\Channels\LogChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Notification::extend('log', fn ($app) => new LogChannel);
    }
}
