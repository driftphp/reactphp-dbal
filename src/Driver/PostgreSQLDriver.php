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
use Drift\DBAL\Result;
use PgAsync\Client;
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
            }, null, function () use (&$results, $deferred) {
                $deferred->resolve($results);
            });

        return $deferred
            ->promise()
            ->then(function ($results) {
                return new Result($results);
            });
    }
}
