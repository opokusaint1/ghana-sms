<?php

declare(strict_types=1);

namespace GhanaSms\Laravel;

use GhanaSms\Laravel\Channels\SmsChannel;
use GhanaSms\SmsManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/sms.php', 'sms');

        $this->app->singleton(SmsManager::class, fn ($app) => new SmsManager($app['config']->get('sms')));
        $this->app->alias(SmsManager::class, 'sms');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/sms.php' => config_path('sms.php'),
        ], 'sms-config');

        // Lets notifications use 'sms' in via(): return ['sms'];
        Notification::resolved(function ($service): void {
            $service->extend('sms', fn ($app) => $app->make(SmsChannel::class));
        });
    }
}
