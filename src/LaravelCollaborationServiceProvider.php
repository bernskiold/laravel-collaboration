<?php

namespace Bernskiold\LaravelCollaboration;

use Bernskiold\LaravelCollaboration\Events\RecordShared;
use Bernskiold\LaravelCollaboration\Listeners\SendShareNotification;
use Composer\InstalledVersions;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class LaravelCollaborationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/collaboration.php', 'collaboration'
        );
    }

    public function boot(): void
    {
        AboutCommand::add('Laravel Collaboration', fn () => [
            'Version' => InstalledVersions::getPrettyVersion('bernskiold/laravel-collaboration'),
        ]);

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'collaboration');

        Event::listen(RecordShared::class, SendShareNotification::class);

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/collaboration.php' => config_path('collaboration.php'),
        ], 'collaboration-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'collaboration-migrations');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/collaboration'),
        ], 'collaboration-translations');
    }
}
