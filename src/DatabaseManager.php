<?php

namespace la\ConnectionManager;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager as BaseDatabaseManager;
use la\ConnectionManager\Enum\ConnectionState;

class DatabaseManager extends BaseDatabaseManager
{
    protected $connections = [];
    /**
     * @var array<string, Pool>
     */
    protected array $pools = [];

    public function __construct($app, $factory)
    {
        parent::__construct($app, $factory);

        foreach (config('database.connections') as $name => $connection) {
            $params = $connection['params'] ?? [];
            $params['databaseManager'] = $this;
            $params['name'] = $name;
            $this->pools[$name] = new Pool(...$params);
        }
    }

    public function getIdleConnections(string $name, int $id=null): array
    {
        return array_filter(
            $this->connections[$name] ?? [],
            static fn (MySqlConnection $connection) => $connection->state === ConnectionState::NOT_IN_USE && ($connection->coId === $id || $connection->coId === null)
        );
    }

    public function connection($name = null): ConnectionInterface
    {
        [$database, $type] = $this->parseConnectionName($name);
        $name = $name ?: $database;

        return $this->pools[$name]->get();
    }

    public function createConnection($name = null): ConnectionInterface
    {
        [$database, $type] = $this->parseConnectionName($name);
        return $this->configure(
            $this->makeConnection($database), $type
        );
    }
}
