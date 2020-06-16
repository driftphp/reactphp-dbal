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

namespace Drift\DBAL\Driver\Mysql;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Exception;

/**
 * Class EmptyDoctrineMysqlDriver.
 */
final class EmptyDoctrineMysqlDriver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
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
