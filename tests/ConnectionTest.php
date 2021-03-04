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

use function Clue\React\Block\await;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Drift\DBAL\Connection;
use Drift\DBAL\Result;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use function React\Promise\all;
use React\Promise\PromiseInterface;

/**
 * Class ConnectionTest.
 */
abstract class ConnectionTest extends TestCase
{
    /**
     * The timeout is used to prevent tests from endless waiting.
     * Consider this amount of seconds as a reasonable timeout
     * to understand that something went wrong.
     */
    private const MAX_TIMEOUT = 3;

    /**
     * @param LoopInterface $loop
     *
     * @return Connection
     */
    abstract protected function getConnection(LoopInterface $loop): Connection;

    /**
     * @param Connection $connection
     * @param bool       $autoincrementedId
     *
     * @return PromiseInterface
     */
    protected function createInfrastructure(
        Connection $connection,
        bool $autoincrementedId = false
    ): PromiseInterface {
        return $connection
            ->createTable('test', [
                'id' => $autoincrementedId ? 'integer' : 'string',
                'field1' => 'string',
                'field2' => 'string',
            ], [
                'field2' => ['notnull' => false],
            ], $autoincrementedId)
            ->otherwise(function (TableExistsException $_) use ($connection) {
                // Silent pass

                return $connection;
            })
            ->then(function (Connection $connection) {
                return $connection->truncateTable('test');
            });
    }

    /**
     * @param Connection $connection
     *
     * @return PromiseInterface
     */
    protected function dropInfrastructure(Connection $connection): PromiseInterface
    {
        return $connection
            ->dropTable('test')
            ->otherwise(function (TableNotFoundException $_) use ($connection) {
                // Silent pass

                return $connection;
            });
    }

    /**
     * @param Connection $connection
     * @param bool       $autoincrementedId
     *
     * @return PromiseInterface
     */
    protected function resetInfrastructure(
        Connection $connection,
        bool $autoincrementedId = false
    ): PromiseInterface {
        return $this
            ->dropInfrastructure($connection)
            ->then(function () use ($connection, $autoincrementedId) {
                return $this->createInfrastructure($connection, $autoincrementedId);
            });
    }

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
     * Test create table with empty criteria.
     */
    public function testCreateTableWithEmptyCriteria()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $this->expectException(InvalidArgumentException::class);
        await($connection->createTable('anothertable', []), $loop);
    }

    /**
     * Test query.
     */
    public function testQuery()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->resetInfrastructure($connection)
            ->then(function (Connection $connection) {
                return $connection
                    ->insert('test', [
                        'id' => '1',
                        'field1' => 'val1',
                        'field2' => 'val2',
                    ])
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

        await($promise, $loop, self::MAX_TIMEOUT);
    }

    /**
     * Test multiple rows.
     */
    public function testMultipleRows()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->resetInfrastructure($connection)
            ->then(function (Connection $connection) {
                return $connection
                    ->insert('test', [
                            'id' => '1',
                            'field1' => 'val11',
                            'field2' => 'val12',
                        ])
                    ->then(function () use ($connection) {
                        return $connection->insert('test', [
                            'id' => '2',
                            'field1' => 'val21',
                            'field2' => 'val22',
                        ]);
                    })
                    ->then(function () use ($connection) {
                        return $connection->insert('test', [
                            'id' => '3',
                            'field1' => 'val31',
                            'field2' => 'val32',
                        ]);
                    });
            })
            ->then(function () use ($connection) {
                $queryBuilder = $connection->createQueryBuilder();

                return $connection
                    ->query($queryBuilder
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

        await($promise, $loop, self::MAX_TIMEOUT);
    }

    /**
     * Test connection exception.
     */
    public function testTableDoesntExistException()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this->dropInfrastructure($connection)
            ->then(function (Connection $connection) {
                return $connection->insert('test', [
                    'id' => '1',
                    'field1' => 'val11',
                    'field2' => 'val12',
                ]);
            });

        $this->expectException(TableNotFoundException::class);
        await($promise, $loop);
    }

    /**
     * Test find shortcut.
     */
    public function testFindShortcut()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->resetInfrastructure($connection)
            ->then(function (Connection $connection) {
                return all([
                    $connection
                        ->insert('test', [
                            'id' => '1',
                            'field1' => 'val1',
                            'field2' => 'val1',
                        ]),
                    $connection
                        ->insert('test', [
                            'id' => '2',
                            'field1' => 'val1',
                            'field2' => 'val2',
                        ]),
                    $connection
                        ->insert('test', [
                            'id' => '3',
                            'field1' => 'valX',
                            'field2' => 'val2',
                        ]),
                ])
                ->then(function () use ($connection) {
                    return all([
                        $connection->findOneBy('test', [
                            'id' => '1',
                        ]),
                        $connection->findOneBy('test', [
                            'id' => '999',
                        ]),
                        $connection->findBy('test', [
                            'field1' => 'val1',
                        ]),
                    ]);
                })
                ->then(function (array $results) {
                    $this->assertEquals($results[0], [
                        'id' => '1',
                        'field1' => 'val1',
                        'field2' => 'val1',
                    ]);

                    $this->assertNull($results[1]);
                    $listResults = $results[2];
                    usort($listResults, function ($a1, $a2) {
                        return $a1['id'] > $a2['id'];
                    });

                    $this->assertSame($listResults, [
                        [
                            'id' => '1',
                            'field1' => 'val1',
                            'field2' => 'val1',
                        ],
                        [
                            'id' => '2',
                            'field1' => 'val1',
                            'field2' => 'val2',
                        ],
                    ]);
                });
            });

        await($promise, $loop, self::MAX_TIMEOUT);
    }

    /**
     * Test select by null.
     */
    public function testFindByNullValue()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->resetInfrastructure($connection)
            ->then(function (Connection $connection) {
                return $connection
                    ->insert('test', [
                        'id' => '1',
                        'field1' => 'val1',
                        'field2' => null,
                    ]);
            })
            ->then(function () use ($connection) {
                return all([
                    $connection->findOneBy('test', [
                        'field2' => null,
                    ]),
                    $connection->findBy('test', [
                        'field2' => null,
                    ]),
                ]);
            });

        list($result0, $result1) = await($promise, $loop, self::MAX_TIMEOUT);

        $this->assertEquals('1', $result0['id']);
        $this->assertCount(1, $result1);
    }

    /**
     * Test insert twice exists.
     */
    public function testInsertTwice()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->resetInfrastructure($connection)
            ->then(function (Connection $connection) {
                return $connection
                    ->insert('test', [
                        'id' => '1',
                        'field1' => 'val1',
                        'field2' => 'val1',
                    ])
                    ->then(function () use ($connection) {
                        return $connection->insert('test', [
                            'id' => '1',
                            'field1' => 'val1',
                            'field2' => 'val1',
                        ]);
                    });
            });

        $this->expectException(UniqueConstraintViolationException::class);
        await($promise, $loop, self::MAX_TIMEOUT);
    }

    /**
     * Test update.
     */
    public function testUpdate()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->resetInfrastructure($connection)
            ->then(function (Connection $connection) {
                return $connection->insert('test', [
                    'id' => '1',
                    'field1' => 'val1',
                    'field2' => 'val1',
                ]);
            })
            ->then(function () use ($connection) {
                return $connection->update('test', [
                    'id' => '1',
                ], [
                    'field1' => 'val3',
                ]);
            })
            ->then(function () use ($connection) {
                return $connection->findOneBy('test', ['id' => '1']);
            })
            ->then(function (array $result) {
                $this->assertEquals('val3', $result['field1']);
            });

        await($promise, $loop, self::MAX_TIMEOUT);
    }

    /**
     * Test upsert.
     */
    public function testUpsert()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->resetInfrastructure($connection)
            ->then(function (Connection $connection) {
                return $connection->insert('test', [
                    'id' => '1',
                    'field1' => 'val1',
                    'field2' => 'val2',
                ]);
            })
            ->then(function () use ($connection) {
                return all([
                    $connection->upsert(
                        'test',
                        ['id' => '1'],
                        ['field1' => 'val3']
                    ),
                    $connection->upsert(
                        'test',
                        ['id' => '2'],
                        ['field1' => 'val5', 'field2' => 'val6']
                    ),
                ]);
            })
            ->then(function () use ($connection) {
                return $connection->findBy('test');
            })
            ->then(function (array $results) {
                $this->assertEquals('1', $results[0]['id']);
                $this->assertEquals('val3', $results[0]['field1']);
                $this->assertEquals('val2', $results[0]['field2']);
                $this->assertEquals('2', $results[1]['id']);
                $this->assertEquals('val5', $results[1]['field1']);
                $this->assertEquals('val6', $results[1]['field2']);
            });

        await($promise, $loop, self::MAX_TIMEOUT);
    }

    /**
     * Test delete.
     */
    public function testDelete()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->resetInfrastructure($connection)
            ->then(function (Connection $connection) {
                return $connection->insert('test', [
                    'id' => '1',
                    'field1' => 'val1',
                    'field2' => 'val2',
                ]);
            })
            ->then(function () use ($connection) {
                return $connection->delete(
                    'test',
                    ['id' => '1']
                );
            })
            ->then(function () use ($connection) {
                return $connection->findOneBy('test', [
                    'id' => '1',
                ]);
            })
            ->then(function ($result) {
                $this->assertNull($result);
            });

        await($promise, $loop, self::MAX_TIMEOUT);
    }

    /**
     * Test get last inserted id.
     */
    public function testGetLastInsertedId()
    {
        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->resetInfrastructure($connection, true)
            ->then(function (Connection $connection) {
                return $connection->insert('test', [
                    'field1' => 'val1',
                    'field2' => 'val2',
                ]);
            })
            ->then(function (Result $result) {
                $this->assertEquals(1, $result->getLastInsertedId());
            })
            ->then(function () use ($connection) {
                return all([
                    $connection->insert('test', [
                        'field1' => 'val3',
                        'field2' => 'val4',
                    ]),
                    $connection->insert('test', [
                        'field1' => 'val5',
                        'field2' => 'val6',
                    ]),
                ]);
            })
            ->then(function (array $results) {
                $this->assertEquals(2, $results[0]->getLastInsertedId());
                $this->assertEquals(3, $results[1]->getLastInsertedId());
            })
            ->then(function () use ($connection) {
                return $connection->insert('test', [
                    'field1' => 'val7',
                    'field2' => 'val8',
                ]);
            })
            ->then(function (Result $result) {
                $this->assertEquals(4, $result->getLastInsertedId());
            });

        await($promise, $loop, self::MAX_TIMEOUT);
    }

    /**
     * Test affected rows.
     */
    public function testAffectedRows()
    {
        if ($this instanceof PostgreSQLConnectionTest) {
            $this->markTestSkipped('This feature is not implemented in the Postgres client');
        }

        $loop = $this->createLoop();
        $connection = $this->getConnection($loop);
        $promise = $this
            ->resetInfrastructure($connection, true)
            ->then(function (Connection $connection) {
                return $connection->insert('test', [
                    'field1' => 'val1',
                    'field2' => 'val2',
                ]);
            })
            ->then(function (Result $result) use ($connection) {
                $this->assertEquals(1, $result->getAffectedRows());

                return $connection->insert('test', [
                    'field1' => 'val1',
                    'field2' => 'val4',
                ]);
            })
            ->then(function () use ($connection) {
                return $connection->update('test', [
                    'field1' => 'val1',
                ], [
                    'field2' => 'new5',
                ]);
            })
            ->then(function (Result $result) use ($connection) {
                $this->assertEquals(2, $result->getAffectedRows());

                return $connection->insert('test', [
                    'field1' => 'val1',
                    'field2' => 'val8',
                ]);
            })
            ->then(function () use ($connection) {
                return $connection->delete('test', [
                    'field1' => 'val1',
                ]);
            })
            ->then(function (Result $result) {
                $this->assertEquals(3, $result->getAffectedRows());
            });

        await($promise, $loop, self::MAX_TIMEOUT);
    }
}
