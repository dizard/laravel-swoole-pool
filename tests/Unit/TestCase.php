<?php

declare(strict_types=1);

namespace la\ConnectionManager\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use la\ConnectionManager\PoolServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Swoole\Event;
use Swoole\Runtime;
use la\ConnectionManager\DatabaseManager;

class TestCase extends BaseTestCase
{
    /**
     * @throws \ErrorException
     */
    protected function setUp(): void
    {
        Runtime::enableCoroutine();

        go(function() {
            parent::setUp();

            Schema::dropIfExists('users');

            Schema::create('users', static function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });
        });

        Event::wait();
    }

    protected function getPackageProviders($app): array
    {
        return [PoolServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->singleton('db', fn ($app) => new DatabaseManager($app, $app['db.factory']));

        $connections = [
            'mysql' => [
                'driver'    => 'mysql',
                'host'      => env('DB_HOST'),
                'port'      => env('DB_PORT'),
                'database'  => env('DB_DATABASE'),
                'username'  => env('DB_USER'),
                'password'  => env('DB_PASS'),
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
                'pool' => [
                    'min_connections' => 1,
                    'max_connections' => 10,
                    'connect_timeout' => 10.0,
                    'wait_timeout' => 3.0,
                ]
            ]
        ];
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections', $connections);
    }
}
