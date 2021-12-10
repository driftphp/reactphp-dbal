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

namespace Drift\DBAL;

/**
 * Class ConnectionWorker.
 */
class ConnectionWorker
{
    private Connection $connection;
    private int $id;
    private int $jobs;

    /**
     * @param Connection $connection
     * @param int        $id
     */
    public function __construct(Connection $connection, int $id)
    {
        $this->connection = $connection;
        $this->id = $id;
        $this->jobs = 0;
    }

    public function startJob()
    {
        ++$this->jobs;
    }

    public function stopJob()
    {
        --$this->jobs;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getJobs(): int
    {
        return $this->jobs;
    }
}
