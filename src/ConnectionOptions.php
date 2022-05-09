<?php

namespace Drift\DBAL;

class ConnectionOptions
{
    public const DEFAULT_KEEP_ALIVE_INTERVAL_SEC = 0; // 0 means disabled

    private int $keepAliveIntervalSec;

    /**
     * @param int $keepAliveIntervalSec
     */
    public function __construct(int $keepAliveIntervalSec = self::DEFAULT_KEEP_ALIVE_INTERVAL_SEC)
    {
        $this->keepAliveIntervalSec = $keepAliveIntervalSec;
    }

    /**
     * @return int
     */
    public function getKeepAliveIntervalSec(): int
    {
        return $this->keepAliveIntervalSec;
    }

    /**
     * @param int $keepAliveIntervalSec
     */
    public function setKeepAliveIntervalSec(int $keepAliveIntervalSec): void
    {
        $this->keepAliveIntervalSec = $keepAliveIntervalSec;
    }
}