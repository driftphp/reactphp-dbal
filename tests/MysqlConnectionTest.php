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

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\MysqlDriver;
use Drift\DBAL\Exception\TableNotFoundException;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class MysqlConnectionTest.
 */
class MysqlConnectionTest extends ConnectionTest
{
    /**
     * {@inheritdoc}
     */
    protected function getConnection(LoopInterface $loop): Connection
    {
        $mysqlPlatform = new MySqlPlatform();

        return Connection::createConnected(new MysqlDriver(
            $loop
        ), new Credentials(
            '127.0.0.1',
            '3306',
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
            ->queryBySQL('CREATE TABLE IF NOT EXISTS test (id VARCHAR(255) PRIMARY KEY, field1 VARCHAR(255), field2 VARCHAR (255))')
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
            }, function(TableNotFoundException $exception) {
                // Silent pass
            });
    }
}
