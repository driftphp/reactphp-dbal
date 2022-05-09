<?php

namespace Drift\DBAL;

class ConnectionPoolOptions extends ConnectionOptions
{
    public const DEFAULT_NUMBER_OF_CONNECTIONS = 1;

    private int $numberOfConnections;

    /**
     * @param int $numberOfConnections
     * @param int $keepAliveIntervalSec
     */
    public function __construct(
        int $numberOfConnections = self::DEFAULT_NUMBER_OF_CONNECTIONS,
        int $keepAliveIntervalSec = self::DEFAULT_KEEP_ALIVE_INTERVAL_SEC
    ) {
        $this->numberOfConnections = $numberOfConnections;
        parent::__construct($keepAliveIntervalSec);
    }

    /**
     * @return int
     */
    public function getNumberOfConnections(): int
    {
        return $this->numberOfConnections;
    }

    /**
     * @param int $numberOfConnections
     */
    public function setNumberOfConnections(int $numberOfConnections): void
    {
        $this->numberOfConnections = $numberOfConnections;
    }
}