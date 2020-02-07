<?php

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
 * Class Connection
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
     * @param Driver $driver
     * @param Credentials $credentials
     * @param AbstractPlatform $platform
     */
    private function __construct(
        Driver $driver,
        Credentials $credentials,
        AbstractPlatform $platform
    )
    {
        $this->driver = $driver;
        $this->credentials = $credentials;
        $this->platform = $platform;
    }

    /**
     * Create new connection
     *
     * @param Driver $driver
     * @param Credentials $credentials
     * @param AbstractPlatform $platform
     *
     * @return Connection
     */
    public static function create(
        Driver $driver,
        Credentials $credentials,
        AbstractPlatform $platform
    )
    {
        return new self($driver, $credentials, $platform);
    }

    /**
     * Create new connection
     *
     * @param Driver $driver
     * @param Credentials $credentials
     * @param AbstractPlatform $platform
     *
     * @return Connection
     */
    public static function createConnected(
        Driver $driver,
        Credentials $credentials,
        AbstractPlatform $platform
    )
    {
        $connection = new self($driver, $credentials, $platform);
        $connection->connect();

        return $connection;
    }

    /**
     * Connect
     */
    public function connect()
    {
        $this
            ->driver
            ->connect($this->credentials);
    }

    /**
     * Creates QueryBuilder
     *
     * @return QueryBuilder
     *
     * @throws DBALException
     */
    public function createQueryBuilder() : QueryBuilder
    {
        return new QueryBuilder(
            new MockedDBALConnection([
                'platform' => $this->platform
            ], new MockedDriver())
        );
    }

    /**
     * Query by query builder
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return PromiseInterface
     */
    public function query(QueryBuilder $queryBuilder) : PromiseInterface
    {
        return $this->queryBySQL(
            $queryBuilder->getSQL(),
            $queryBuilder->getParameters()
        );
    }

    /**
     * Query by sql and parameters
     *
     * @param string $sql
     * @param array $parameters
     *
     * @return PromiseInterface
     */
    public function queryBySQL(string $queryBuilder, array $parameters = []) : PromiseInterface
    {
        return $this
            ->driver
            ->query($queryBuilder, $parameters);
    }

    /**
     * Insert
     *
     * @return PromiseInterface
     */
    public function insert(
        string $table,
        array $values,
        array $parameters
    ) : PromiseInterface
    {
        $queryBuilder = $this
            ->createQueryBuilder()
            ->insert($table)
            ->values($values)
            ->setParameters($parameters);

        return $this->query($queryBuilder);
    }

    /**
     * Update
     *
     * @return PromiseInterface
     *
     * @throws InvalidArgumentException
     */
    public function deleteById(
        string $table,
        array $id,
        array $parameters
    ) : PromiseInterface
    {
        if (empty($id)) {
            throw InvalidArgumentException::fromEmptyCriteria();
        }

        $queryBuilder = $this
            ->createQueryBuilder()
            ->delete($table)
            ->where($id)
            ->setParameters($parameters);

        return $this->query($queryBuilder);
    }
}