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

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Driver\Driver;
use React\Promise\PromiseInterface;

/**
 * Class ConnectionPool.
 */
class ConnectionPool implements Connection, ConnectionPoolInterface
{
    private array $connections;

    /**
     * Connection constructor.
     *
     * @param ConnectionWorker[] $connections
     */
    private function __construct(array $connections)
    {
        $this->connections = $connections;
    }

    /**
     * Create new connection.
     *
     * @param Driver           $driver
     * @param Credentials      $credentials
     * @param AbstractPlatform $platform
     *
     * @return Connection
     */
    public static function create(
        Driver $driver,
        Credentials $credentials,
        AbstractPlatform $platform
    ): Connection {
        $numberOfConnections = $credentials->getConnections();
        if ($numberOfConnections <= 1) {
            return SingleConnection::create(
                $driver,
                $credentials,
                $platform
            );
        }

        $connections = [];
        for ($i = 0; $i < $numberOfConnections; ++$i) {
            $connections[] = new ConnectionWorker(
                SingleConnection::create(
                    clone $driver,
                    $credentials,
                    $platform
                ), $i
            );
        }

        return new self($connections);
    }

    /**
     * Create new connection.
     *
     * @param Driver           $driver
     * @param Credentials      $credentials
     * @param AbstractPlatform $platform
     *
     * @return Connection
     */
    public static function createConnected(
        Driver $driver,
        Credentials $credentials,
        AbstractPlatform $platform
    ): Connection {
        $connection = self::create($driver, $credentials, $platform);
        $connection->connect();

        return $connection;
    }

    /**
     * @return string
     */
    public function getDriverNamespace(): string
    {
        return $this
            ->bestConnection()
            ->getConnection()
            ->getDriverNamespace();
    }

    /**
     * @param bool $increaseJobs
     *
     * @return ConnectionWorker
     */
    private function bestConnection(bool $increaseJobs = false): ConnectionWorker
    {
        $minJobs = 1000000000;
        $minJobsConnection = null;
        foreach ($this->connections as $i => $connection) {
            if ($connection->getJobs() < $minJobs) {
                $minJobs = $connection->getJobs();
                $minJobsConnection = $i;
            }
        }

        if ($increaseJobs) {
            $this->connections[$minJobsConnection]->startJob();
        }

        return $this->connections[$minJobsConnection];
    }

    /**
     * @return ConnectionWorker
     */
    private function firstConnection(): ConnectionWorker
    {
        return $this->connections[0];
    }

    /**
     * @param callable $callable
     *
     * @return PromiseInterface
     */
    private function executeInBestConnection(callable $callable): PromiseInterface
    {
        $connectionWorker = $this->bestConnection(true);

        return $callable($connectionWorker->getConnection())
            ->then(function ($whatever) use ($connectionWorker) {
                $connectionWorker->stopJob();

                return $whatever;
            });
    }

    /**
     * Connect.
     */
    public function connect()
    {
        foreach ($this->connections as $connection) {
            $connection->getConnection()->connect();
        }
    }

    /**
     * Close.
     */
    public function close()
    {
        foreach ($this->connections as $connection) {
            $connection->getConnection()->close();
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
        return $this
            ->firstConnection()
            ->getConnection()
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
     * @param array  $parameters
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
     * @param array  $where
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
     * @param array  $where
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
     * @param array  $values
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
     * @param array  $values
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
     * @param array  $id
     * @param array  $values
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
     * @param array  $id
     * @param array  $values
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
     * @param array  $fields
     * @param array  $extra
     * @param bool   $autoincrementId
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
        return $this->executeInBestConnection(function (Connection $connection) use ($name, $fields, $extra, $autoincrementId) {
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
    public function getWorkers(): array
    {
        return $this->connections;
    }
}
