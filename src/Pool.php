<?php

namespace la\ConnectionManager;

use Illuminate\Database\ConnectionInterface;
use la\ConnectionManager\Exceptions\NoConnectionsAvailableException;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine;

class Pool
{
    protected array $coroutineConnections = [];
    protected array $connections = [];
    protected Channel $pool;
    protected int $currentConnections = 0;

    public function __construct(
        protected DatabaseManager $databaseManager,
        protected string $name,
        protected int $min_connections = 0,
        protected int $max_connections = 5,
        protected float $connect_timeout = 10.0,
        protected float $wait_timeout = 5.0,
    ) {
        $this->pool = new Channel($max_connections);
    }


    public function release(ConnectionInterface $connection): void
    {
        $this->pool->push($connection);
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
        // если мы не в корутине и рантайме
        if ($cId === -1) {
            return $this->coroutineConnections[$cId] = $this->databaseManager->createConnection($this->name);
        }


        if ($this->currentConnections < $this->max_connections) {
            $connection = $this->databaseManager->createConnection($this->name);
            $this->currentConnections++;
            $this->pool->push($connection);
        }


        if (($connection = $this->pool->pop($this->wait_timeout))===false) {
            throw new Exceptions\NoConnectionsAvailableException();
        }
        $this->coroutineConnections[$cId] = $connection;
        Coroutine::defer(function () use ($cId, &$connection) {
            unset($this->coroutineConnections[$cId]);
            $this->release($connection);
        });
        return $connection;
    }
}
