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

namespace Drift\DBAL\Driver\SQLite;

use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Exception;

/**
 * Class EmptyDoctrineSQLiteDriver.
 */
final class EmptyDoctrineSQLiteDriver extends AbstractSQLiteDriver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = []): DriverConnection
    {
        throw new Exception('Do not use this method.');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        throw new Exception('Do not use this method.');
    }
}
