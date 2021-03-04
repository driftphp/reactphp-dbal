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
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Exception;

/**
 * Class MockedDBALConnection.
 */
class MockedDBALConnection extends Connection
{
    /**
     * Prepares an SQL statement.
     *
     * @param string $sql the SQL statement to prepare
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function prepare(string $sql): Statement
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * BC layer for a wide-spread use-case of old DBAL APIs.
     *
     * @deprecated This API is deprecated and will be removed after 2022
     */
    public function query(string $sql): Result
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
     * BC layer for a wide-spread use-case of old DBAL APIs.
     *
     * @deprecated This API is deprecated and will be removed after 2022
     */
    public function exec(string $sql): int
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
     * @param string                                                               $sql    SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function executeQuery(
        string $sql,
        array $params = [],
        $types = [],
        ?QueryCacheProfile $qcp = null
    ): Result {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * BC layer for a wide-spread use-case of old DBAL APIs.
     *
     * @deprecated This API is deprecated and will be removed after 2022
     *
     * @param array<mixed>           $params The query parameters
     * @param array<int|string|null> $types  The parameter types
     */
    public function executeUpdate(string $sql, array $params = [], array $types = []): int
    {
        throw new Exception('Mocked method. Unable to be used');
    }
}
