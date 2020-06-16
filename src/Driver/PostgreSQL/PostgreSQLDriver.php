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

namespace Drift\DBAL\Driver\PostgreSQL;

use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\AbstractDriver;
use Drift\DBAL\Driver\PlainDriverException;
use Drift\DBAL\Result;
use PgAsync\Client;
use PgAsync\ErrorException;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Class PostgreSQLDriver.
 */
class PostgreSQLDriver extends AbstractDriver
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var EmptyDoctrinePostgreSQLDriver
     */
    private $doctrineDriver;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->doctrineDriver = new EmptyDoctrinePostgreSQLDriver();
        $this->loop = $loop;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(Credentials $credentials, array $options = [])
    {
        $this->client = new Client([
            'host' => $credentials->getHost(),
            'port' => $credentials->getPort(),
            'user' => $credentials->getUser(),
            'password' => $credentials->getPassword(),
            'database' => $credentials->getDbName(),
        ], $this->loop);
    }

    /**
     * {@inheritdoc}
     */
    public function query(
        string $sql,
        array $parameters
    ): PromiseInterface {
        /**
         * We should fix the parametrization.
         */
        $i = 1;
        $sql = preg_replace_callback('~\?~', function ($_) use (&$i) {
            return '$'.$i++;
        }, $sql);

        $results = [];
        $deferred = new Deferred();

        $this
            ->client
            ->executeStatement($sql, $parameters)
            ->subscribe(function ($row) use (&$results) {
                $results[] = $row;
            }, function (ErrorException $exception) use ($deferred) {
                $errorResponse = $exception->getErrorResponse();
                $message = $exception->getMessage();
                $code = 0;
                foreach ($errorResponse->getErrorMessages() as $messageLine) {
                    if ('C' === $messageLine['type']) {
                        $code = $messageLine['message'];
                    }
                }

                $exception = $this
                    ->doctrineDriver
                    ->convertException(
                        $message,
                        PlainDriverException::createFromMessageAndErrorCode(
                            $message,
                            (string) $code
                        ));

                $deferred->reject($exception);
            }, function () use (&$results, $deferred) {
                $deferred->resolve($results);
            });

        return $deferred
            ->promise()
            ->then(function ($results) {
                return new Result($results, null, null);
            });
    }

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
        $query = 'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME = ?';

        return $this
            ->query($query, [$table])
            ->then(function (Result $response) use ($queryBuilder) {
                $allRows = $response->fetchAllRows();
                $fields = array_map(function ($item) {
                    return $item['column_name'];
                }, $allRows);

                // When there are no fields, means that the table does not exist
                // To make the normal behavior, we make a simple query and let
                // the DBAL do the job (no last_inserted_it is expected here

                $returningPart = empty($fields)
                    ? ''
                    : ' RETURNING '.implode(',', $fields);

                return $this
                    ->query($queryBuilder->getSQL().$returningPart, $queryBuilder->getParameters())
                    ->then(function (Result $result) use ($fields) {
                        return 0 === count($fields)
                            ? new Result()
                            : new Result([], \intval($result->fetchFirstRow()[$fields[0]]), 1);
                    });
            });
    }
}
