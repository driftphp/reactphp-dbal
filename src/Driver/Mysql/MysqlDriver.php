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

namespace Drift\DBAL\Driver\Mysql;

use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\API\MySQL\ExceptionConverter;
use Doctrine\DBAL\Query;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\AbstractDriver;
use Drift\DBAL\Driver\Exception as DoctrineException;
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
class MysqlDriver extends AbstractDriver
{
    private Factory $factory;
    private ConnectionInterface $connection;
    private EmptyDoctrineMysqlDriver $doctrineDriver;
    private ExceptionConverterInterface $exceptionConverter;

    /**
     * MysqlDriver constructor.
     *
     * @param LoopInterface      $loop
     * @param ConnectorInterface $connector
     */
    public function __construct(LoopInterface $loop, ConnectorInterface $connector = null)
    {
        $this->doctrineDriver = new EmptyDoctrineMysqlDriver();
        $this->factory = is_null($connector)
            ? new Factory($loop)
            : new Factory($loop, $connector);
        $this->exceptionConverter = new ExceptionConverter();
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
                return new Result(
                    $queryResult->resultRows,
                    $queryResult->insertId,
                    $queryResult->affectedRows
                );
            })
            ->otherwise(function (Exception $exception) use (&$sql, &$parameters) {
                throw $this->exceptionConverter->convert(new DoctrineException($exception->getMessage(), null, $exception->getCode()), new Query($sql, $parameters, []));
            });
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->connection->close();
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
