<?php

declare(strict_types=1);

namespace la\ConnectionManager;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use la\ConnectionManager\DatabaseManager;
use la\ConnectionManager\ScmMySqlConnection;

class ScmPoolServiceProvider extends ServiceProvider
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
}
