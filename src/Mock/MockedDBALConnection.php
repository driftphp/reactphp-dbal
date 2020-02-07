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

namespace Drift\DBAL\Mock;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\ParameterType;
use Exception;

/**
 * Class MockedDBALConnection.
 */
class MockedDBALConnection extends Connection
{
    /**
     * {@inheritdoc}
     */
    public function prepare($prepareString)
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type = ParameterType::STRING)
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement)
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
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
     * @param string                 $query  the SQL query to execute
     * @param mixed[]                $params the parameters to bind to the query, if any
     * @param int[]|string[]         $types  the types the previous parameters are in
     * @param QueryCacheProfile|null $qcp    the query cache profile, optional
     *
     * @return Driver\ResultStatement the executed statement
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
     * @param string         $query  the SQL query
     * @param mixed[]        $params the query parameters
     * @param int[]|string[] $types  the parameter types
     *
     * @return int the number of affected rows
     *
     * @throws DBALException
     */
    public function executeUpdate($query, array $params = [], array $types = [])
    {
        throw new Exception('Mocked method. Unable to be used');
    }
}
