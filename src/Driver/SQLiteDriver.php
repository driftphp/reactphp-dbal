<?php


namespace Drift\DBAL\Driver;

use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Factory;
use Doctrine\DBAL\Query\QueryBuilder;
use Drift\DBAL\Credentials;
use Drift\DBAL\Result;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Clue\React\SQLite\Result as SQLiteResult;

/**
 * Class SQLiteDriver
 */
class SQLiteDriver implements Driver
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
     * SQLiteDriver constructor.
     *
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->factory = new Factory($loop);
    }

    /**
     * @inheritDoc
     */
    public function connect(Credentials $credentials, array $options = [])
    {
        $this->database = $this
            ->factory
            ->openLazy($credentials->getDbName());
    }

    /**
     * @inheritDoc
     */
    public function query(
        string $sql,
        array $parameters
    ): PromiseInterface
    {
        return $this
            ->database
            ->query($sql, $parameters)
            ->then(function(SQLiteResult $sqliteResult) {
                return new Result($sqliteResult->rows);
            });
    }
}