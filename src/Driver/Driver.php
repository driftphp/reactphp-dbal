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

    public function insert(QueryBuilder $queryBuilder, string $table, array $values): PromiseInterface;
}
