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
use React\EventLoop\LoopInterface;
use React\MySQL\ConnectionInterface;
use React\MySQL\Exception;
use React\MySQL\Factory;
use React\MySQL\QueryResult;
use React\Promise\PromiseInterface;
use React\Socket\ConnectorInterface;

/**
 * Class MysqlDriver.
 */
class MysqlDriver implements Driver
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * MysqlDriver constructor.
     *
     * @param LoopInterface      $loop
     * @param ConnectorInterface $connector
     */
    public function __construct(LoopInterface $loop, ConnectorInterface $connector = null)
    {
        $this->factory = is_null($connector)
            ? new Factory($loop)
            : new Factory($loop, $connector);
    }

    /**
     * {@inheritdoc}
     */
    public function connect(Credentials $credentials, array $options = [])
    {
        $this->connection = $this
            ->factory
            ->createLazyConnection($credentials->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function query(
        string $sql,
        array $parameters
    ): PromiseInterface {
        return $this
            ->connection
            ->query($sql, $parameters)
            ->then(function (QueryResult $queryResult) {
                return new Result($queryResult->resultRows);
            })
            ->otherwise(function (Exception $exception) {
                $this->parseException($exception);
            });
    }

    /**
     * Parse exception.
     *
     * @param Exception $exception
     *
     * @throws DBALException
     */
    private function parseException(Exception $exception)
    {
        $message = $exception->getMessage();
        $match = null;

        if (
            preg_match('~^Unknown table \'(.*?)\'$~', $message, $match) ||
            preg_match('~^Table \'(.*?)\' doesn\'t exist$~', $message, $match)
        ) {
            $tableName = $match[1];
            $tableParts = explode('.', $tableName, 2);
            $tableName = end($tableParts);

            throw TableNotFoundException::createByTableName($tableName);
        }

        throw DBALException::createGeneric($message);
    }
}
