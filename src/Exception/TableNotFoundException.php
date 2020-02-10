<?php


namespace Drift\DBAL\Exception;

/**
 * Class TableNotFoundException
 */
class TableNotFoundException extends DBALException
{
    /**
     * Create by table name
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