<?php


namespace Drift\DBAL\Driver;


use Doctrine\DBAL\Query\QueryBuilder;
use React\Promise\PromiseInterface;

abstract class AbstractDriver
{
    abstract public function query(string $sql, array $parameters): PromiseInterface;

    public function insert(QueryBuilder $queryBuilder, string $table, array $values): PromiseInterface
    {
        $queryBuilder = $this->createInsertQuery($queryBuilder, $table, $values);
        return $this->query($queryBuilder->getSQL(), $queryBuilder->getParameters());
    }

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
