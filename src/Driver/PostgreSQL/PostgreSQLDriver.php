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

use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\Driver;
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
class PostgreSQLDriver implements Driver
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
     * MysqlDriver constructor.
     *
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
            }, function (ErrorException $exception) {
                $errorResponse = $exception->getErrorResponse();
                $message = $exception->getMessage();
                $code = 0;
                foreach ($errorResponse->getErrorMessages() as $messageLine) {
                    if ('C' === $messageLine['type']) {
                        $code = $messageLine['message'];
                    }
                }

                throw $this->doctrineDriver->convertException($message, PlainDriverException::createFromMessageEndErrorCode($message, (string) $code));
            }, function () use (&$results, $deferred) {
                $deferred->resolve($results);
            });

        return $deferred
            ->promise()
            ->then(function ($results) {
                return new Result($results);
            });
    }
}
