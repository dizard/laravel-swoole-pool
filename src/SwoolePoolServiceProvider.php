<?php

declare(strict_types=1);

namespace la\ConnectionManager;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class SwoolePoolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConnectionResolver();
    }

    protected function registerConnectionResolver(): void
    {
        $this->app->singleton('dbswoole', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        Connection::resolverFor('mysql', static function (
            Closure $connection,
            string  $database,
            string  $prefix,
            array   $config
        ) {
            return (new MySqlConnection(
                $connection,
                $database,
                $prefix,
                $config,
            ))->setDatabaseManager(resolve('dbswoole'));
        });
    }
}
