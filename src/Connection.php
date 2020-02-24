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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Driver\Driver;
use Drift\DBAL\Mock\MockedDBALConnection;
use Drift\DBAL\Mock\MockedDriver;
use React\Promise\PromiseInterface;

/**
 * Class Connection.
 */
class Connection
{
    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var Credentials
     */
    private $credentials;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * Connection constructor.
     *
     * @param Driver           $driver
     * @param Credentials      $credentials
     * @param AbstractPlatform $platform
     */
    private function __construct(
        Driver $driver,
        Credentials $credentials,
        AbstractPlatform $platform
    ) {
        $this->driver = $driver;
        $this->credentials = $credentials;
        $this->platform = $platform;
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
    ) {
        return new self($driver, $credentials, $platform);
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
    ) {
        $connection = new self($driver, $credentials, $platform);
        $connection->connect();

        return $connection;
    }

    /**
     * Connect.
     */
    public function connect()
    {
        $this
            ->driver
            ->connect($this->credentials);
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
        return new QueryBuilder(
            new MockedDBALConnection([
                'platform' => $this->platform,
            ], new MockedDriver())
        );
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
        return $this->queryBySQL(
            $queryBuilder->getSQL(),
            $queryBuilder->getParameters()
        );
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
        return $this
            ->driver
            ->query($sql, $parameters);
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
        return $this
            ->getResultByWhereClause($table, $where)
            ->then(function (Result $result) {
                return $result->fetchFirstRow();
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
        return $this
            ->getResultByWhereClause($table, $where)
            ->then(function (Result $result) {
                return $result->fetchAllRows();
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
        $queryBuilder = $this
            ->createQueryBuilder()
            ->insert($table)
            ->values(array_combine(
                array_keys($values),
                array_fill(0, count($values), '?')
            ))
            ->setParameters(array_values($values));

        return $this->query($queryBuilder);
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
    public function delete(
        string $table,
        array $id,
        array $values
    ): PromiseInterface {
        if (empty($id)) {
            throw InvalidArgumentException::fromEmptyCriteria();
        }

        $queryBuilder = $this
            ->createQueryBuilder()
            ->delete($table);

        $this->applyWhereClausesFromArray($queryBuilder, $values);

        return $this->query($queryBuilder);
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
        if (empty($id)) {
            throw InvalidArgumentException::fromEmptyCriteria();
        }

        $queryBuilder = $this
            ->createQueryBuilder()
            ->update($table);

        $parameters = $queryBuilder->getParameters();
        foreach ($values as $field => $value) {
            $queryBuilder->set($field, '?');
            $parameters[] = $value;
        }
        $queryBuilder->setParameters($parameters);
        $this->applyWhereClausesFromArray($queryBuilder, $id);

        return $this
            ->query($queryBuilder)
            ->then(function (Result $result) {
                return $result->fetchAllRows();
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
    ) {
        return $this
            ->findOneBy($table, $id)
            ->then(function (?array $result) use ($table, $id, $values) {
                return is_null($result)
                    ? $this->insert($table, array_merge($id, $values))
                    : $this->update($table, $id, $values);
            });
    }

    /**
     * Get result by where clause.
     *
     * @param string $table
     * @param array  $where
     *
     * @return PromiseInterface<Result>
     */
    private function getResultByWhereClause(
        string $table,
        array $where
    ): PromiseInterface {
        $queryBuilder = $this
            ->createQueryBuilder()
            ->select('t.*')
            ->from($table, 't');

        $this->applyWhereClausesFromArray($queryBuilder, $where);

        return $this->query($queryBuilder);
    }

    /**
     * Apply where clauses.
     *
     * [
     *      "id" => 1,
     *      "name" => "Marc"
     * ]
     *
     * to
     *
     * [
     *      [ "id = ?", "name = ?"],
     *      [1, "Marc"]
     * ]
     *
     * @param QueryBuilder $queryBuilder
     * @param array        $array
     */
    private function applyWhereClausesFromArray(
        QueryBuilder $queryBuilder,
        array $array
    ) {
        $params = $queryBuilder->getParameters();
        foreach ($array as $field => $value) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq($field, '?')
            );
            $params[] = $value;
        }

        $queryBuilder->setParameters($params);
    }
}
