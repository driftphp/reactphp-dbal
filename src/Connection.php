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
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Drift\DBAL\Driver\Driver;
use Drift\DBAL\Mock\MockedDBALConnection;
use Drift\DBAL\Mock\MockedDriver;
use function React\Promise\map;
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
     * Execute, sequentially, an array of sqls.
     *
     * @param string[] $sqls
     *
     * @return PromiseInterface<Connection>
     */
    public function executeSQLs(array $sqls): PromiseInterface
    {
        return
            map($sqls, function (string $sql) {
                return $this->queryBySQL($sql);
            })
            ->then(function () {
                return $this;
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
        return $this
            ->executeSQLs($schema->toSql($this->platform))
            ->then(function () {
                return $this;
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

        $queryBuilder = $this->createQueryBuilder();

        return $this->driver->insert($queryBuilder, $table, $values);
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
        if (empty($values)) {
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
        if (empty($fields)) {
            throw InvalidArgumentException::fromEmptyCriteria();
        }

        $schema = new Schema();
        $table = $schema->createTable($name);
        foreach ($fields as $field => $type) {
            $extraField = (
                array_key_exists($field, $extra) &&
                is_array($extra[$field])
            ) ? $extra[$field] : [];

            if (
                'string' == $type &&
                !array_key_exists('length', $extraField)
            ) {
                $extraField = array_merge(
                    $extraField,
                    ['length' => 255]
                );
            }

            $table->addColumn($field, $type, $extraField);
        }

        $id = array_key_first($fields);
        $table->setPrimaryKey([$id]);
        $table->getColumn($id)->setAutoincrement($autoincrementId);

        return $this->executeSchema($schema);
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
        return $this
            ->queryBySQL("DROP TABLE $name")
            ->then(function () {
                return $this;
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
        $truncateTableQuery = $this
            ->platform
            ->getTruncateTableSQL($name);

        return $this
            ->queryBySQL($truncateTableQuery)
            ->then(function () {
                return $this;
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
            if (\is_null($value)) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->isNull($field)
                );
                continue;
            }

            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq($field, '?')
            );

            $params[] = $value;
        }

        $queryBuilder->setParameters($params);
    }
}
