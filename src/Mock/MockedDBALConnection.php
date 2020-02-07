<?php

namespace Drift\DBAL\Mock;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\ParameterType;
use Exception;

/**
 * Class MockedDBALConnection
 */
class MockedDBALConnection extends Connection
{
    /**
     * @inheritDoc
     */
    public function prepare($prepareString)
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * @inheritDoc
     */
    public function query()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * @inheritDoc
     */
    public function quote($input, $type = ParameterType::STRING)
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * @inheritDoc
     */
    public function exec($statement)
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * @inheritDoc
     */
    public function lastInsertId($name = null)
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * @inheritDoc
     */
    public function rollBack()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * @inheritDoc
     */
    public function errorCode()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * @inheritDoc
     */
    public function errorInfo()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * Executes an, optionally parametrized, SQL query.
     *
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string                 $query  The SQL query to execute.
     * @param mixed[]                $params The parameters to bind to the query, if any.
     * @param int[]|string[]         $types  The types the previous parameters are in.
     * @param QueryCacheProfile|null $qcp    The query cache profile, optional.
     *
     * @return Driver\ResultStatement The executed statement.
     *
     * @throws DBALException
     */
    public function executeQuery($query, array $params = [], $types = [], ?QueryCacheProfile $qcp = null)
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
     * and returns the number of affected rows.
     *
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * @param string         $query  The SQL query.
     * @param mixed[]        $params The query parameters.
     * @param int[]|string[] $types  The parameter types.
     *
     * @return int The number of affected rows.
     *
     * @throws DBALException
     */
    public function executeUpdate($query, array $params = [], array $types = [])
    {
        throw new Exception('Mocked method. Unable to be used');
    }
}