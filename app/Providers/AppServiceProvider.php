<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectWorkload;
use App\Models\Subtask;
use App\Models\Task;
use App\Observers\DomainActivityObserver;
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

        foreach ([Client::class, Brand::class, Project::class, Task::class, Subtask::class, ProjectWorkload::class, ProjectMember::class] as $model) {
            $model::observe(DomainActivityObserver::class);
        }
    }
}
