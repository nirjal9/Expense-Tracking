<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PaymentNotification\AutoCategorizationService;
use App\Services\PaymentNotification\ExpenseCreationService;
use App\Services\PaymentNotification\EmailParserService;
use App\Services\PaymentNotification\SMSParserService;
use App\Services\PaymentNotification\GmailService;
use App\Services\PaymentNotification\PaymentNotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Payment Notification Services
        $this->app->singleton(AutoCategorizationService::class);
        $this->app->singleton(ExpenseCreationService::class);
        $this->app->singleton(EmailParserService::class);
        $this->app->singleton(SMSParserService::class);
        $this->app->singleton(GmailService::class);
        $this->app->singleton(PaymentNotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
