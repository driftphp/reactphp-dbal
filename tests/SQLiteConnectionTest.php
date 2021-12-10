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

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\SQLite\SQLiteDriver;
use Drift\DBAL\SingleConnection;
use React\EventLoop\LoopInterface;

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

        return SingleConnection::createConnected(new SQLiteDriver(
            $loop
        ), new Credentials(
            '',
            '',
            'root',
            'root',
            ':memory:'
        ), $mysqlPlatform);
    }
}
