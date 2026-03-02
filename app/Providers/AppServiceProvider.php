<?php

namespace App\Providers;

use App\AI\AiClientInterface;
use App\AI\Clients\FakeAiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiClientInterface::class, FakeAiClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
