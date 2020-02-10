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

use Drift\DBAL\Credentials;
use Drift\DBAL\Exception\DBALException;
use Drift\DBAL\Exception\TableNotFoundException;
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
     * MysqlDriver constructor.
     *
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
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
                $this->parseException($exception);
            }, function () use (&$results, $deferred) {
                $deferred->resolve($results);
            });

        return $deferred
            ->promise()
            ->then(function ($results) {
                return new Result($results);
            });
    }

    /**
     * Parse exception.
     *
     * @param ErrorException $exception
     *
     * @throws DBALException
     */
    private function parseException(ErrorException $exception)
    {
        $message = $exception->getMessage();
        $match = null;

        if (
            preg_match('~^ERROR: table "(.*?)" does not exist.*$~', $message, $match) ||
            preg_match('~^ERROR: relation "(.*?)" does not exist.*$~', $message, $match)
        ) {
            $tableName = $match[1];

            throw TableNotFoundException::createByTableName($tableName);
        }

        throw DBALException::createGeneric($message);
    }
}
