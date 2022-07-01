<?php

/*
 * This file is part of the DriftPHP Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\DBAL;

use Closure;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Driver\Driver;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use SplObjectStorage;

use function React\Promise\resolve;

/**
 * Class ConnectionPool.
 */
class ConnectionPool implements Connection, ConnectionPoolInterface
{

    /**
     * @var SplObjectStorage|array<\Drift\DBAL\Connection, \Drift\DBAL\ConnectionWorker> $connections
     */
    private SplObjectStorage $connections;

    /**
     * @var SplObjectStorage|Deferred[] $deferreds
     */
    private SplObjectStorage $deferreds;

    /**
     * Connection constructor.
     *
     * @param ConnectionWorker[] $workers
     */
    private function __construct(array $workers)
    {
        $this->connections = new SplObjectStorage;

        foreach ($workers as $worker) {
            $this->connections->attach($worker->getConnection(), $worker);
        }

        $this->deferreds = new SplObjectStorage;
    }

    /**
     * Create new connection.
     *
     * @param Driver $driver
     * @param Credentials $credentials
     * @param AbstractPlatform $platform
     * @param ConnectionOptions|null $options
     *
     * @return Connection
     */
    public static function create(
        Driver $driver,
        Credentials $credentials,
        AbstractPlatform $platform,
        ?ConnectionOptions $options = null
    ): Connection {
        $numberOfConnections = $credentials->getConnections() ??
            ConnectionPoolOptions::DEFAULT_NUMBER_OF_CONNECTIONS;
        if ($options instanceof ConnectionPoolOptions) {
            $numberOfConnections = $options->getNumberOfConnections();
        }

        // Since using transactions with a single connection in an asynchronous environment
        // probably doesn't do what you want it to do* we explicitly disallow it.

        if ($numberOfConnections <= 1) {
            return SingleConnection::create(
                $driver,
                $credentials,
                $platform,
                $options
            );
        }

        $workers = [];
        for ($i = 0; $i < $numberOfConnections; ++$i) {
            $workers[] = new ConnectionWorker(
                SingleConnection::create(
                    clone $driver,
                    $credentials,
                    $platform,
                    $options,
                    true
                ), $i
            );
        }

        return new self($workers);
    }

    /**
     * Create new connection.
     *
     * @param Driver $driver
     * @param Credentials $credentials
     * @param AbstractPlatform $platform
     * @param ConnectionOptions|null $options
     *
     * @return Connection
     */
    public static function createConnected(
        Driver $driver,
        Credentials $credentials,
        AbstractPlatform $platform,
        ?ConnectionOptions $options = null
    ): Connection {
        $connection = self::create($driver, $credentials, $platform, $options);
        $connection->connect($options);

        return $connection;
    }

    /**
     * @return string
     */
    public function getDriverNamespace(): string
    {
        $connections = clone $this->connections;
        $worker = $connections->current();
        return $worker->getDriverNamespace();
    }

    /**
     * @param bool $increaseJobs
     *
     * @return PromiseInterface<ConnectionWorker>
     */
    private function bestConnectionWorker(
        bool $increaseJobs = false
    ): PromiseInterface {
        $minJobs = 1000000000;
        $bestConnection = null;

        foreach ($this->connections as $connection) {
            $worker = $this->connections->getInfo();
            if ($worker->getJobs() < $minJobs && !$worker->isLeased()) {
                $minJobs = $worker->getJobs();
                $bestConnection = $worker;
            }
        }

        if ($bestConnection !== null) {
            if ($increaseJobs) {
                $bestConnection->startJob();
            }

            return resolve($bestConnection);
        }

        fwrite(STDOUT, 'no more workers' . PHP_EOL);

        $deferred = new Deferred;
        $this->deferreds->attach($deferred);

        if ($increaseJobs) {
            $deferred->promise()->then(fn(ConnectionWorker $w) => $w->startJob());
        }

        return $deferred->promise();
    }

    /**
     * @param callable $callable
     *
     * @return PromiseInterface
     */
    private function executeInBestConnection(callable $callable): PromiseInterface
    {
        $worker = null;

        return $this->bestConnectionWorker(true)
            ->then(function (ConnectionWorker $connectionWorker) use ($callable, &$worker) {
                $worker = $connectionWorker;
                return $callable($worker->getConnection());
            })
            ->then(function ($whatever) use (&$worker) {
                $worker->stopJob();
                return $whatever;
            });
    }

    /**
     * Connect.
     */
    public function connect(?ConnectionOptions $options = null)
    {
        foreach ($this->connections as $connection) {
            $connection->connect($options);
        }
    }

    /**
     * Close.
     */
    public function close()
    {
        foreach ($this->connections as $connection) {
            $connection->close();
        }
    }

    /**
     * Creates QueryBuilder.
     *
     * @return QueryBuilder
     *
     * @throws DBALException
     */
    public function createQueryBuilder(): QueryBuilder
    {
        // We clone the worker storage because we probably can't rely on
        // SplObjectStorage::current(...) working reliably in an
        // asynchronous environment.
        $workers = clone $this->connections;
        $workers->rewind();

        return $workers->current()
            ->createQueryBuilder();
    }

    /**
     * Query by query builder.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return PromiseInterface<Result>
     */
    public function query(QueryBuilder $queryBuilder): PromiseInterface
    {
        return $this->executeInBestConnection(function (Connection $connection) use ($queryBuilder) {
            return $connection->query($queryBuilder);
        });
    }

    /**
     * Query by sql and parameters.
     *
     * @param string $sql
     * @param array $parameters
     *
     * @return PromiseInterface<Result>
     */
    public function queryBySQL(string $sql, array $parameters = []): PromiseInterface
    {
        return $this->executeInBestConnection(function (Connection $connection) use ($sql, $parameters) {
            return $connection->queryBySQL($sql, $parameters);
        });
    }

    /**
     * Execute, sequentially, an array of sqls.
     *
     * @param string[] $sqls
     *
     * @return PromiseInterface<Connection>
     */
    public function executeSQLs(array $sqls): PromiseInterface
    {
        return $this->executeInBestConnection(function (Connection $connection) use ($sqls) {
            return $connection->executeSQLs($sqls);
        });
    }

    /**
     * Execute an schema.
     *
     * @param Schema $schema
     *
     * @return PromiseInterface<Connection>
     */
    public function executeSchema(Schema $schema): PromiseInterface
    {
        return $this->executeInBestConnection(function (Connection $connection) use ($schema) {
            return $connection->executeSchema($schema);
        });
    }

    /**
     * Shortcuts.
     */

    /**
     * Find one by.
     *
     * connection->findOneById('table', ['id' => 1]);
     *
     * @param string $table
     * @param array $where
     *
     * @return PromiseInterface<array|null>
     */
    public function findOneBy(
        string $table,
        array $where
    ): PromiseInterface {
        return $this->executeInBestConnection(function (Connection $connection) use ($table, $where) {
            return $connection->findOneBy($table, $where);
        });
    }

    /**
     * Find by.
     *
     * connection->findBy('table', ['id' => 1]);
     *
     * @param string $table
     * @param array $where
     *
     * @return PromiseInterface<array>
     */
    public function findBy(
        string $table,
        array $where = []
    ): PromiseInterface {
        return $this->executeInBestConnection(function (Connection $connection) use ($table, $where) {
            return $connection->findBy($table, $where);
        });
    }

    /**
     * @param string $table
     * @param array $values
     *
     * @return PromiseInterface
     */
    public function insert(
        string $table,
        array $values
    ): PromiseInterface {
        return $this->executeInBestConnection(function (Connection $connection) use ($table, $values) {
            return $connection->insert($table, $values);
        });
    }

    /**
     * @param string $table
     * @param array $values
     *
     * @return PromiseInterface
     *
     * @throws InvalidArgumentException
     */
    public function delete(
        string $table,
        array $values
    ): PromiseInterface {
        return $this->executeInBestConnection(function (Connection $connection) use ($table, $values) {
            return $connection->delete($table, $values);
        });
    }

    /**
     * @param string $table
     * @param array $id
     * @param array $values
     *
     * @return PromiseInterface
     *
     * @throws InvalidArgumentException
     */
    public function update(
        string $table,
        array $id,
        array $values
    ): PromiseInterface {
        return $this->executeInBestConnection(function (Connection $connection) use ($table, $id, $values) {
            return $connection->update($table, $id, $values);
        });
    }

    /**
     * @param string $table
     * @param array $id
     * @param array $values
     *
     * @return PromiseInterface
     *
     * @throws InvalidArgumentException
     */
    public function upsert(
        string $table,
        array $id,
        array $values
    ): PromiseInterface {
        return $this->executeInBestConnection(function (Connection $connection) use ($table, $id, $values) {
            return $connection->upsert($table, $id, $values);
        });
    }

    /**
     * Table related shortcuts.
     */

    /**
     * Easy shortcut for creating tables. Fields is just a simple key value,
     * being the key the name of the field, and the value the type. By default,
     * Varchar types have length 255.
     *
     * First field is considered as primary key.
     *
     * @param string $name
     * @param array $fields
     * @param array $extra
     * @param bool $autoincrementId
     *
     * @return PromiseInterface<Connection>
     *
     * @throws InvalidArgumentException
     * @throws TableExistsException
     */
    public function createTable(
        string $name,
        array $fields,
        array $extra = [],
        bool $autoincrementId = false
    ): PromiseInterface {
        return $this->executeInBestConnection(function (Connection $connection) use (
            $name,
            $fields,
            $extra,
            $autoincrementId
        ) {
            return $connection->createTable($name, $fields, $extra, $autoincrementId);
        });
    }

    /**
     * @param string $name
     *
     * @return PromiseInterface<Connection>
     *
     * @throws TableNotFoundException
     */
    public function dropTable(string $name): PromiseInterface
    {
        return $this->executeInBestConnection(function (Connection $connection) use ($name) {
            return $connection->dropTable($name);
        });
    }

    /**
     * @param string $name
     *
     * @return PromiseInterface<Connection>
     *
     * @throws TableNotFoundException
     */
    public function truncateTable(string $name): PromiseInterface
    {
        return $this->executeInBestConnection(function (Connection $connection) use ($name) {
            return $connection->truncateTable($name);
        });
    }

    /**
     * Get the Pool's connection workers
     *
     * @return ConnectionWorker[]
     */
    public function getConnections(): array
    {
        $workers = [];

        $connections = clone $this->connections;
        foreach ($connections as $connection) {
            $workers[] = $connections->getInfo();
        }

        return $workers;
    }

    public function startTransaction(): PromiseInterface
    {
        return $this->bestConnectionWorker()
            ->then(function (ConnectionWorker $worker) {
                $worker->setLeased(true);

                $connection = $worker->getConnection();

                if (!$connection instanceof SingleConnection) {
                    throw new RuntimeException('connection must be instance of ' . SingleConnection::class);
                }

                $connection->startTransaction();

                return resolve($connection);
            });
    }

    public function commitTransaction(SingleConnection $connection): PromiseInterface
    {
        return $connection->commitTransaction($connection)
            ->then(Closure::fromCallable([$this, 'releaseConnection']));
    }

    public function rollbackTransaction(SingleConnection $connection): PromiseInterface
    {
        return $connection->rollbackTransaction($connection)
            ->always(Closure::fromCallable([$this, 'releaseConnection']));
    }

    private function releaseConnection(SingleConnection $connection): PromiseInterface
    {
        if (count($this->deferreds) === 0) {
            /** @var \Drift\DBAL\ConnectionWorker $worker */
            $worker = $this->connections[$connection];
            $worker->setLeased(false);
            return resolve();
        }

        $deferred = $this->deferreds->current();
        $this->deferreds->detach($deferred);

        $deferred->resolve($this->connections[$connection]);
        return resolve();
    }

}
