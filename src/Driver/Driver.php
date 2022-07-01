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

namespace Drift\DBAL\Driver;

use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Credentials;
use Drift\DBAL\Result;
use React\Promise\PromiseInterface;

/**
 * Interface Driver.
 */
interface Driver
{
    /**
     * Attempts to create a connection with the database.
     *
     * @param Credentials $credentials
     */
    public function connect(Credentials $credentials);

    /**
     * Make query.
     *
     * @param string $sql
     * @param array  $parameters
     *
     * @return PromiseInterface<Result>
     */
    public function query(
        string $sql,
        array $parameters
    ): PromiseInterface;

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $table
     * @param array        $values
     *
     * @return PromiseInterface
     */
    public function insert(
        QueryBuilder $queryBuilder,
        string $table,
        array $values
    ): PromiseInterface;

    /**
     * @return void
     */
    public function close(): void;

    public function startTransaction(): PromiseInterface;

    public function commitTransaction(): PromiseInterface;

    public function rollbackTransaction(): PromiseInterface;
}
