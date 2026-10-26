<?php

namespace MujahidAbbas\LaravelUserTags\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;
use MujahidAbbas\LaravelUserTags\UserTagsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            UserTagsServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    public function getEnvironmentSetUp($app): void
    {
        // Use SQLite in-memory database for tests, unless DB_CONNECTION picks
        // another configured connection (CI sets mysql with the DB_* variables)
        $app['config']->set('database.default', env('DB_CONNECTION', 'testing'));
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Configure the user model for testing
        $app['config']->set('user-tags.user_model', User::class);
    }
}
