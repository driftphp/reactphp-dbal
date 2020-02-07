<?php


namespace Drift\DBAL\Mock;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;


class MockedDriver implements Driver
{
    /**
     * @inheritDoc
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        // TODO: Implement connect() method.
    }

    /**
     * @inheritDoc
     */
    public function getDatabasePlatform()
    {
        // TODO: Implement getDatabasePlatform() method.
    }

    /**
     * @inheritDoc
     */
    public function getSchemaManager(Connection $conn)
    {
        // TODO: Implement getSchemaManager() method.
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        // TODO: Implement getName() method.
    }

    /**
     * @inheritDoc
     */
    public function getDatabase(Connection $conn)
    {
        // TODO: Implement getDatabase() method.
    }
}