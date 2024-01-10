<?php

namespace la\ConnectionManager\Tests\Unit;

use Illuminate\Support\Facades\DB;
use la\ConnectionManager\Exceptions\NoConnectionsAvailableException;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Swoole\Event;
use Swoole\Runtime;
use Workbench\App\Models\User;


class ConcurrencyTest extends TestCase
{
    use WithWorkbench;

    /**
     * Tests if long running queries successfully run concurrently.
     *
     * @return void
     */
    public function testConcurrentSleepQueries(): void
    {
        Runtime::enableCoroutine();

        $timeStarted = microtime(true);

        // complete x10 1s sleep queries concurrently
        // should take ~1s to execute
        for ($i = 0; $i < 10; $i++) {
            go(function () use ($i) {
                $this->app['db']
                    ->connection()
                    ->table(null)
                    ->select(DB::raw('SLEEP(1)'))
                    ->get();
            });
        }

        Event::wait();

        $timeFinished = microtime(true);

        // asserting that the execution of all 10 queries took under 1.1s
        $this->assertSame(
            bccomp("1.1", (string)($timeFinished - $timeStarted), 3),
            1
        );
    }

    /**
     * Tests if an exception is thrown when there are not enough
     * connections for several long running queries.
     *
     * @return void
     */
    public function testConcurrentQueriesWhenNotEnoughConnections(): void
    {
        Runtime::enableCoroutine();

        $exception = false;

        // attempt to complete x11 1s sleep queries concurrently
        // there are only 10 connections available to use at once
        for ($i = 0; $i < 100; $i++) {
            go(function () use ($i, &$exception) {
                try {
                    if (! $exception) {
                        $this->app['db']
                            ->connection()
                            ->table(null)
                            ->select(DB::raw('SLEEP(1)'))
                            ->get();
                    }
                } catch (NoConnectionsAvailableException $e) {
                    $exception = true;
                }
            });
        }
        Event::wait();

        // asserting that no connections were available to perform at least one of the queries
        $this->assertTrue($exception);
    }

    /**
     * Tests if Eloquent models can save contents concurrently.
     *
     * @return void
     */
    public function testConcurrentModelSaveQueries(): void
    {
        Runtime::enableCoroutine();

        // create 10 users at once
        for ($i = 0; $i < 10; $i++) {
            go(function () {
                $user = new User();
                $user->name = 'Zac';
                try {
                    $user->save();
                } catch (NoConnectionsAvailableException $e) {
                    // user didnt save, fail
                }
                // check that the user was created
                $this->assertNotNull($user->id);
            });
        }

        Event::wait();
    }

    /**
     * Tests if Eloquent models can select contents concurrently.
     *
     * @return void
     */
    public function testConcurrentModelSelectQueries(): void
    {
        Runtime::enableCoroutine();

        // run x10 select queries
        for ($i = 0; $i < 10; $i++) {
            go(static function () {
                $selectUser = User::where('id', 1)->first();
            });
        }

        Event::wait();

//        // since they all ran at once, there should be 10 connections in the pool
//        $this->assertCount(10, $this->app->get('db')->getConnections());
//
//        // ...which are now idle - they have been used
//        $this->assertCount(10, $this->app->get('db')->getIdleConnections());
    }

}
