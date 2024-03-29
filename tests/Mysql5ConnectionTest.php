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

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\Mysql\MysqlDriver;
use Drift\DBAL\SingleConnection;
use React\EventLoop\LoopInterface;

/**
 * Class Mysql5ConnectionTest.
 */
class Mysql5ConnectionTest extends ConnectionTest
{
    /**
     * {@inheritdoc}
     */
    protected function getConnection(LoopInterface $loop): Connection
    {
        $mysqlPlatform = new MySQLPlatform();

        return SingleConnection::createConnected(new MysqlDriver(
            $loop
        ), new Credentials(
            '127.0.0.1',
            '3306',
            'root',
            'root',
            'test'
        ), $mysqlPlatform);
    }
}
