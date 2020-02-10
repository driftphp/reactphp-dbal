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

namespace Drift\DBAL\Exception;

/**
 * Class TableNotFoundException.
 */
class TableNotFoundException extends DBALException
{
    /**
     * Create by table name.
     *
     * @param string $tableName
     *
     * @return TableNotFoundException
     */
    public static function createByTableName(string $tableName)
    {
        return new TableNotFoundException(sprintf('Table %s not found', $tableName));
    }
}
