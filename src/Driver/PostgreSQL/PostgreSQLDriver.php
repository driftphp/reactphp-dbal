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

use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\API\PostgreSQL\ExceptionConverter;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query;
use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\AbstractDriver;
use Drift\DBAL\Driver\Exception as DoctrineException;
use Drift\DBAL\Result;
use PgAsync\Client;
use PgAsync\Connection;
use PgAsync\ErrorException;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function React\Promise\reject;

/**
 * Class PostgreSQLDriver.
 */
class PostgreSQLDriver extends AbstractDriver
{
    private Connection $connection;
    private LoopInterface $loop;
    private EmptyDoctrinePostgreSQLDriver $doctrineDriver;
    private ExceptionConverterInterface $exceptionConverter;
    private bool $isClosed = false;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->doctrineDriver = new EmptyDoctrinePostgreSQLDriver();
        $this->loop = $loop;
        $this->exceptionConverter = new ExceptionConverter();
    }

    /**
     * {@inheritdoc}
     */
    public function connect(Credentials $credentials, array $options = [])
    {
        $this->connection =
            (new Client([
                'host' => $credentials->getHost(),
                'port' => $credentials->getPort(),
                'user' => $credentials->getUser(),
                'password' => $credentials->getPassword(),
                'database' => $credentials->getDbName(),
            ], $this->loop))
            ->getIdleConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function query(
        string $sql,
        array $parameters
    ): PromiseInterface {
        if ($this->isClosed) {
            return reject(new Exception('Connection closed'));
        }

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
            ->connection
            ->executeStatement($sql, $parameters)
            ->subscribe(function ($row) use (&$results) {
                $results[] = $row;
            }, function (ErrorException $exception) use ($deferred, &$sql, &$parameters) {
                $errorResponse = $exception->getErrorResponse();
                $code = 0;
                foreach ($errorResponse->getErrorMessages() as $messageLine) {
                    if ('C' === $messageLine['type']) {
                        $code = $messageLine['message'];
                    }
                }

                $exception = $this->exceptionConverter->convert(
                    new DoctrineException($exception->getMessage(), \strval($code)),
                    new Query(
                        $sql, $parameters, []
                    )
                );

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
        if ($this->isClosed) {
            return reject(new Exception('Connection closed'));
        }

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
                            ? new Result(0, null, null)
                            : new Result([], \intval($result->fetchFirstRow()[$fields[0]]), 1);
                    });
            });
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->isClosed = true;
        $this
            ->connection
            ->disconnect();
    }

    public function startTransaction(): PromiseInterface
    {
        return $this->query('START TRANSACTION', []);
    }

    public function commitTransaction(): PromiseInterface
    {
        return $this->query('COMMIT', []);
    }

    public function rollbackTransaction(): PromiseInterface
    {
        return $this->query('ROLLBACK', []);
    }
}
