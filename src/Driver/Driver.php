<?php


namespace Drift\DBAL\Driver;

use Drift\DBAL\Credentials;
use React\Promise\PromiseInterface;

/**
 * Interface Driver
 */
interface Driver
{
    /**
     * Attempts to create a connection with the database.
     *
     * @param Credentials $credentials
     * @param array $options
     *
     * @return PromiseInterface
     */
    public function connect(Credentials $credentials);

    /**
     * Make query
     *
     * @param string $sql
     * @param array $parameters
     *
     * @return PromiseInterface
     */
    public function query(
        string $sql,
        array $parameters
    ): PromiseInterface;
}