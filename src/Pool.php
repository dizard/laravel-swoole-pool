<?php

namespace la\ConnectionManager;

use Illuminate\Database\ConnectionInterface;
use la\ConnectionManager\Enum\ConnectionState;
use la\ConnectionManager\Exceptions\NoConnectionsAvailableException;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine;

class Pool
{
    protected array $coroutineConnections = [];
    protected array $connections = [];
    protected Channel $pool;

    public function __construct(
        protected DatabaseManager $databaseManager,
        protected string $name,
        protected int $min_connections = 0,
        protected int $max_connections = 20,
        protected float $connect_timeout = 10.0,
        protected float $wait_timeout = 3.0,
    ) {
        $this->pool = new Channel($max_connections);
    }


    /**
     * @throws NoConnectionsAvailableException
     */
    public function get(): ConnectionInterface
    {
        $cId = Coroutine::getCid();
        if (array_key_exists($cId, $this->coroutineConnections)) {
            return $this->coroutineConnections[$cId];
        }

        if ($this->pool->isEmpty()) {
            throw new Exceptions\NoConnectionsAvailableException();
        }

        $connection = $this->databaseManager->createConnection($this->name);
        $this->pool->push($connection);

        $connection = $this->databaseManager->createConnection($this->name);
        $this->coroutineConnections[$cId] = $connection;
        Coroutine::defer(function () use ($cId) {
            unset($this->coroutineConnections[$cId]);
        });

        return $connection;
    }
}
