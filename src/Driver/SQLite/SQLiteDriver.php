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
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\AbstractDriver;
use Drift\DBAL\Driver\PlainDriverException;
use Drift\DBAL\Result;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use RuntimeException;

/**
 * Class SQLiteDriver.
 */
class SQLiteDriver extends AbstractDriver
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var DatabaseInterface
     */
    private $database;

    /**
     * @var EmptyDoctrineSQLiteDriver
     */
    private $doctrineDriver;

    /**
     * SQLiteDriver constructor.
     *
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->doctrineDriver = new EmptyDoctrineSQLiteDriver();
        $this->factory = new Factory($loop);
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
            ->otherwise(function (RuntimeException $exception) {
                $message = $exception->getMessage();

                throw $this->doctrineDriver->convertException($message, PlainDriverException::createFromMessageAndErrorCode($message, (string) $exception->getCode()));
            });
    }
}
