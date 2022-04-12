<?php

namespace Drift\DBAL;

interface ConnectionPoolInterface
{
    /**
     * Get the Pool's connection workers
     *
     * @return ConnectionWorker[]
     */
    public function getWorkers(): array;
}
