<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.app', function ($view) {
            $unreadNotificationCount = Auth::check()
                ? Notification::query()->forUser(Auth::id())->unread()->count()
                : 0;

            $view->with('unreadNotificationCount', $unreadNotificationCount);
        });
    }
}