<?php


namespace Drift\DBAL\Exception;

use Exception;

/**
 * Class DBALException
 */
class DBALException extends Exception
{
    /**
     * Create generic
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