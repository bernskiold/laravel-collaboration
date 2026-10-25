<?php

namespace Bernskiold\LaravelCollaboration\Tests;

use Bernskiold\LaravelCollaboration\LaravelCollaborationServiceProvider;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\Note;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\Post;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\User;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\RecordPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([
            'post' => Post::class,
            'note' => Note::class,
        ]);

        Gate::policy(Post::class, RecordPolicy::class);
        Gate::policy(Note::class, RecordPolicy::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActivitylogServiceProvider::class,
            LaravelCollaborationServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        $config->set('database.default', 'testing');
        $config->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $config->set('auth.providers.users.model', User::class);
        $config->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
