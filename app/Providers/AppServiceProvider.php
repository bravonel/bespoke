<?php

namespace App\Providers;

use App\Services\AI\AiProvider;
use App\Services\AI\AiSpeechProvider;
use App\Services\AI\OpenAiProvider;
use App\Services\AI\OpenAiSpeechProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiProvider::class, OpenAiProvider::class);
        $this->app->bind(AiSpeechProvider::class, OpenAiSpeechProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));
    }
}
