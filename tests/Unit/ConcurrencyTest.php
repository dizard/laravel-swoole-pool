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

    public function testCorrectGetConnections()
    {
        Runtime::enableCoroutine();
        for ($i = 1; $i < 10; $i++) {
            go(function () use ($i) {
                $res1 = \DB::select('SELECT CONNECTION_ID()');
                $res2 = \DB::select('SELECT SLEEP(1), CONNECTION_ID()');
                $this->assertSame($res1[0]->{'CONNECTION_ID()'}, $res2[0]->{'CONNECTION_ID()'});
            });
        }
        Event::wait();

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
}
