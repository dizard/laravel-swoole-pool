<?php

declare(strict_types=1);

namespace la\ConnectionManager;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager as BaseDatabaseManager;
use la\ConnectionManager\Exceptions\NoConnectionsAvailableException;

class DatabaseManager extends BaseDatabaseManager
{
    protected $connections = [];
    /**
     * @var array<string, DBPool>
     */
    protected array $pools = [];

    public function __construct($app, $factory)
    {
        parent::__construct($app, $factory);

        foreach (config('database.connections') as $name => $connection) {
            $params = $connection['pool'] ?? [];
            $params['databaseManager'] = $this;
            $params['name'] = $name;
            $this->pools[$name] = new DBPool(...$params);
        }
    }

    /**
     * @param $name
     * @return DBPool
     */
    public function getPool($name = null):DBPool
    {
        [$database,] = $this->parseConnectionName($name);
        $name = $name ?: $database;

        return $this->pools[$name];
    }

    /**
     * @throws NoConnectionsAvailableException
     */
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
