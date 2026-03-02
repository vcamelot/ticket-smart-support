<?php

namespace App\Providers;

use App\AI\AiClientInterface;
use App\AI\Clients\FakeAiClient;
use App\AI\Clients\GeminiAiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiClientInterface::class, function ($app) {
            if (config('ai.provider') === 'gemini') {
                return new GeminiAiClient(
                    config('ai.gemini.api_key', ''),
                    config('ai.gemini.model'),
                    config('ai.gemini.base_url')
                );
            }

            return new FakeAiClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
