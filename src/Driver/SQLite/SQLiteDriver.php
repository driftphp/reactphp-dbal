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

namespace Drift\DBAL\Driver\SQLite;

use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Factory;
use Clue\React\SQLite\Result as SQLiteResult;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\API\SQLite\ExceptionConverter;
use Doctrine\DBAL\Query;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\AbstractDriver;
use Drift\DBAL\Driver\Exception as DoctrineException;
use Drift\DBAL\Result;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use RuntimeException;

/**
 * Class SQLiteDriver.
 */
class SQLiteDriver extends AbstractDriver
{
    private Factory $factory;
    private DatabaseInterface $database;
    private EmptyDoctrineSQLiteDriver $doctrineDriver;
    private ExceptionConverterInterface $exceptionConverter;

    /**
     * SQLiteDriver constructor.
     *
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->doctrineDriver = new EmptyDoctrineSQLiteDriver();
        $this->factory = new Factory($loop);
        $this->exceptionConverter = new ExceptionConverter();
    }

    /**
     * {@inheritdoc}
     */
    public function connect(Credentials $credentials, array $options = [])
    {
        $this->database = $this
            ->factory
            ->openLazy($credentials->getDbName());
    }

    /**
     * {@inheritdoc}
     */
    public function query(
        string $sql,
        array $parameters
    ): PromiseInterface {
        return $this
            ->database
            ->query($sql, $parameters)
            ->then(function (SQLiteResult $sqliteResult) {
                return new Result(
                    $sqliteResult->rows,
                    $sqliteResult->insertId,
                    $sqliteResult->changed
                );
            })
            ->otherwise(function (RuntimeException $exception) use (&$sql, &$parameters) {
                throw $this->exceptionConverter->convert(new DoctrineException($exception->getMessage()), new Query($sql, $parameters, []));
            });
    }
}
