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

namespace Drift\DBAL\Tests;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\SQLite\SQLiteDriver;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class SQLiteConnectionTest.
 */
class SQLiteConnectionTest extends ConnectionTest
{
    /**
     * {@inheritdoc}
     */
    public function getConnection(LoopInterface $loop): Connection
    {
        $mysqlPlatform = new SqlitePlatform();

        return Connection::createConnected(new SQLiteDriver(
            $loop
        ), new Credentials(
            '',
            '',
            'root',
            'root',
            ':memory:'
        ), $mysqlPlatform);
    }

    /**
     * Create database and table.
     *
     * @param Connection $connection
     *
     * @return PromiseInterface
     */
    protected function createInfrastructure(Connection $connection): PromiseInterface
    {
        return $connection
            ->queryBySQL('CREATE TABLE test (id VARCHAR(255) PRIMARY KEY, field1 VARCHAR(255), field2 VARCHAR (255))')
            ->then(function () use ($connection) {
                return $connection;
            });
    }

    /**
     * Drop infrastructure.
     *
     * @param Connection $connection
     *
     * @return PromiseInterface
     */
    protected function dropInfrastructure(Connection $connection): PromiseInterface
    {
        return $connection
            ->queryBySQL('DROP TABLE test')
            ->then(function () use ($connection) {
                return $connection;
            }, function (TableNotFoundException $exception) use ($connection) {
                // Silent pass

                return $connection;
            });
    }

    /**
     * Get native query
     *
     * @return PromiseInterface
     */
    protected function getNativeQuery(): string
    {
        return 'select * from test t where t.id = ? and t.field2 = ? LIMIT 1';
    }
}
