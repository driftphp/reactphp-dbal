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
 * Class Connection.
 */
interface Connection
{
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
    ): Connection;

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
    ): Connection;

    /**
     * @return string
     */
    public function getDriverNamespace(): string;

    /**
     * Connect.
     */
    public function connect();

    /**
     * Close.
     */
    public function close();

    /**
     * Creates QueryBuilder.
     *
     * @return QueryBuilder
     *
     * @throws DBALException
     */
    public function createQueryBuilder(): QueryBuilder;

    /**
     * Query by query builder.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return PromiseInterface<Result>
     */
    public function query(QueryBuilder $queryBuilder): PromiseInterface;

    /**
     * Query by sql and parameters.
     *
     * @param string $sql
     * @param array  $parameters
     *
     * @return PromiseInterface<Result>
     */
    public function queryBySQL(string $sql, array $parameters = []): PromiseInterface;

    /**
     * Execute, sequentially, an array of sqls.
     *
     * @param string[] $sqls
     *
     * @return PromiseInterface<Connection>
     */
    public function executeSQLs(array $sqls): PromiseInterface;

    /**
     * Execute an schema.
     *
     * @param Schema $schema
     *
     * @return PromiseInterface<Connection>
     */
    public function executeSchema(Schema $schema): PromiseInterface;

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
    ): PromiseInterface;

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
    ): PromiseInterface;

    /**
     * @param string $table
     * @param array  $values
     *
     * @return PromiseInterface
     */
    public function insert(
        string $table,
        array $values
    ): PromiseInterface;

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
    ): PromiseInterface;

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
    ): PromiseInterface;

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
    ): PromiseInterface;

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
    ): PromiseInterface;

    /**
     * @param string $name
     *
     * @return PromiseInterface<Connection>
     *
     * @throws TableNotFoundException
     */
    public function dropTable(string $name): PromiseInterface;

    /**
     * @param string $name
     *
     * @return PromiseInterface<Connection>
     *
     * @throws TableNotFoundException
     */
    public function truncateTable(string $name): PromiseInterface;
}
