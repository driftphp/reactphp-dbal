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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Exception;

/**
 * Class MockedDriver
 */
class MockedDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn)
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        throw new Exception('Mocked method. Unable to be used');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(Connection $conn)
    {
        throw new Exception('Mocked method. Unable to be used');
    }
}
