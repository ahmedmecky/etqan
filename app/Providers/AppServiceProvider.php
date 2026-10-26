<?php

namespace App\Providers;

use App\Support\NotifyHooks;
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
        // الإشعارات: الشكاوى · الحرارة · التفتيش الميداني · الاعتمادات
        NotifyHooks::register();
    }
}