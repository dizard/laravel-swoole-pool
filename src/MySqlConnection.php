<?php
declare(strict_types=1);

namespace la\ConnectionManager;

use Closure;
use Illuminate\Database\MySqlConnection as BaseMySqlConnection;
use Illuminate\Database\QueryException;
use la\ConnectionManager\Enum\ConnectionState;
use la\ConnectionManager\Exceptions\ConnectionInUseException;
use la\ConnectionManager\Exceptions\NoConnectionsAvailableException;
use Swoole\Coroutine;

class MySqlConnection extends BaseMySqlConnection
{
    /**
     * The database manager instance.
     *
     * @var DatabaseManager|null
     */
    protected ?DatabaseManager $databaseManager;

    /**
     * Gets the database manager if present.
     *
     * @return DatabaseManager|null
     */
    public function getDatabaseManager(): ?DatabaseManager
    {
        return $this->databaseManager;
    }

    public function setDatabaseManager(DatabaseManager $databaseManager): self
    {
        $this->databaseManager = $databaseManager;
        return $this;
    }

    public function proxyRun($query, $bindings, Closure $callback)
    {
        return parent::run($query, $bindings, $callback->bindTo($this));
    }



    /**
     * Run a SQL statement and log its execution context.
     *
     * @param string $query
     * @param array $bindings
     * @param Closure $callback
     * @return mixed
     *
     * @throws ConnectionInUseException
     * @throws NoConnectionsAvailableException
     */
    protected function run($query, $bindings, Closure $callback): mixed
    {
        if (!$manager = $this->getDatabaseManager()) {
            throw new ConnectionInUseException();
        }
        return $manager->connection($this->getName())->proxyRun($query, $bindings, $callback);
    }
}
