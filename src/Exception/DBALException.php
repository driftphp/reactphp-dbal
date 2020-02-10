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

use Exception;

/**
 * Class DBALException.
 */
class DBALException extends Exception
{
    /**
     * Create generic.
     *
     * @param string $reason
     *
     * @return DBALException
     */
    public static function createGeneric(string $reason)
    {
        return new DBALException($reason);
    }
}
