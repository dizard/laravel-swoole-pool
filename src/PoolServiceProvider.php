<?php

declare(strict_types=1);

namespace la\ConnectionManager;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class PoolServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->registerConnectionResolver();
    }

    protected function registerConnectionResolver(): void
    {
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });
    }

    public function provides(): array
    {
        return ['db'];
    }
}
