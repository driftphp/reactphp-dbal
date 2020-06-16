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
use React\Promise\PromiseInterface;

/**
 * Class AbstractDriver.
 */
abstract class AbstractDriver implements Driver
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $table
     * @param array        $values
     *
     * @return PromiseInterface
     */
    public function insert(QueryBuilder $queryBuilder, string $table, array $values): PromiseInterface
    {
        $queryBuilder = $this->createInsertQuery($queryBuilder, $table, $values);

        return $this->query($queryBuilder->getSQL(), $queryBuilder->getParameters());
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $table
     * @param array        $values
     *
     * @return QueryBuilder
     */
    protected function createInsertQuery(QueryBuilder $queryBuilder, string $table, array $values): QueryBuilder
    {
        return $queryBuilder->insert($table)
            ->values(array_combine(
                array_keys($values),
                array_fill(0, count($values), '?')
            ))
            ->setParameters(array_values($values));
    }
}
