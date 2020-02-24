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
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\PostgreSQL\PostgreSQLDriver;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class PostgreSQLConnectionTest.
 */
class PostgreSQLConnectionTest extends ConnectionTest
{
    /**
     * {@inheritdoc}
     */
    public function getConnection(LoopInterface $loop): Connection
    {
        $mysqlPlatform = new PostgreSqlPlatform();

        return Connection::createConnected(new PostgreSQLDriver($loop), new Credentials(
            '127.0.0.1',
            '5432',
            'root',
            'root',
            'test'
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
            ->queryBySQL('CREATE TABLE IF NOT EXISTS test (id VARCHAR PRIMARY KEY, field1 VARCHAR, field2 VARCHAR)')
            ->then(function () use ($connection) {
                return $connection
                    ->queryBySQL('TRUNCATE TABLE test')
                    ->then(function () use ($connection) {
                        return $connection;
                    });
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
            })
            ->otherwise(function (TableNotFoundException $exception) use ($connection) {
                // Silent pass

                return $connection;
            });
    }
}
