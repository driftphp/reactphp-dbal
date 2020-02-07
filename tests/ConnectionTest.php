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

namespace Drift\DBAL\Tests;

use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use function Clue\React\Block\await;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class ConnectionTest.
 */
abstract class ConnectionTest extends TestCase
{
    /**
     * @param LoopInterface $loop
     *
     * @return Connection
     */
    abstract protected function getConnection(LoopInterface $loop): Connection;

    /**
     * Create database and table.
     *
     * @param Connection $connection
     *
     * @return PromiseInterface
     */
    abstract protected function createInfrastructure(Connection $connection): PromiseInterface;

    /**
     * Drop infrastructure.
     *
     * @param Connection $connection
     *
     * @return PromiseInterface
     */
    abstract protected function dropInfrastructure(Connection $connection): PromiseInterface;

    /**
     * Create loop Loop.
     */
    protected function createLoop()
    {
        return Factory::create();
    }

    /**
     * Test that query builder works properly.
     */
    public function testQueryBuilder()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $sql = $connection
            ->createQueryBuilder()
            ->select('*')
            ->from('user', 'u')
            ->where('u.id = :id')
            ->setParameter('id', 3)
            ->setMaxResults(1)
            ->getSQL();

        $this->assertEquals('SELECT * FROM user u WHERE u.id = :id LIMIT 1', $sql);
    }

    /**
     * Test query.
     */
    public function testQuery()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->createInfrastructure($connection)
            ->then(function (Connection $connection) {
                return $connection
                    ->insert('test', [
                        'id' => '1',
                        'field1' => '?',
                        'field2' => '?',
                    ], ['val1', 'val2'])
                    ->then(function () use ($connection) {
                        return $connection
                            ->query($connection
                                ->createQueryBuilder()
                                ->select('*')
                                ->from('test', 't')
                                ->where('t.id = ?')
                                ->setParameters(['1'])
                                ->setMaxResults(1)
                            );
                    })
                    ->then(function (Result $result) {
                        $this->assertEquals($result->fetchFirstRow(), [
                            'id' => '1',
                            'field1' => 'val1',
                            'field2' => 'val2',
                        ]);
                    });
            });

        await($promise, $loop);
    }

    /**
     * Test multiple rows.
     *
     * @group lol
     */
    public function testMultipleRows()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->createInfrastructure($connection)
            ->then(function (Connection $connection) {
                return $connection
                    ->insert('test', [
                            'id' => '?',
                            'field1' => '?',
                            'field2' => '?',
                        ], ['1', 'val11', 'val12'])
                    ->then(function () use ($connection) {
                        return $connection->insert('test', [
                            'id' => '?',
                            'field1' => '?',
                            'field2' => '?',
                        ], ['2', 'val21', 'val22']);
                    })
                    ->then(function () use ($connection) {
                        return $connection->insert('test', [
                            'id' => '?',
                            'field1' => '?',
                            'field2' => '?',
                        ], ['3', 'val31', 'val32']);
                    });
            })
            ->then(function () use ($connection) {
                $queryBuilder = $connection->createQueryBuilder();

                return $connection->query($queryBuilder
                    ->select('*')
                    ->from('test', 't')
                    ->where($queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq('t.id', '?'),
                        $queryBuilder->expr()->eq('t.id', '?')
                    ))
                    ->setParameters(['1', '2']));
            })
            ->then(function (Result $result) {
                $this->assertCount(2, $result->fetchAllRows());
                $this->assertEquals(2, $result->fetchCount());
            });

        await($promise, $loop);
    }
}
