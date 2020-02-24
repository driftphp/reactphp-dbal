# DBAL for ReactPHP

This is a DBAL on top of ReactPHP SQL libraries and Doctrine QueryBuilder
implementation.

> Attention. Only for proof of concept ATM. Do not use this library on
> production until the first stable version is tagged. 

## Example

Let's create an example of what this library can really do. For this example, we
will create an adapter for Mysql, and will use Doctrine QueryBuilder to create a
new element in database and query for some rows.

Because we will use Mysql adapter, you should have installed the ReactPHP based
mysql library `react/mysql`. Because this library is on development stage, all
adapters dependencies will be loaded for testing purposes.

First of all, we need to create a Connection instance with the selected platform
driver. We will have to create as well a Credentials instance with all the
connection data.

```php
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Drift\DBAL\Connection;
use Drift\DBAL\Driver\Mysql\MysqlDriver;
use Drift\DBAL\Credentials;
use React\EventLoop\Factory as LoopFactory;

$loop = LoopFactory::create();
$mysqlPlatform = new MySqlPlatform();
$mysqlDriver = new MysqlDriver($loop);
$credentials = new Credentials(
   '127.0.0.1',
   '3306',
   'root',
   'root',
   'test'
);

$connection = Connection::createConnected(
    $mysqlDriver,
    $credentials,
    $mysqlPlatform
);
```

Once we have the connection, we can create a new register in the database by
using the Doctrine QueryBuilder or direct built-in methods. The result of all
these calls will be a Promise interface that, eventually, will return a Result
instance.

```php
use Drift\DBAL\Connection;
use Drift\DBAL\Result;

/**
* @var Connection $connection
 */
$promise = $connection
    ->insert('test', [
        'id' => '?',
        'field1' => '?',
        'field2' => '?',
    ], ['1', 'val11', 'val12'])
    ->then(function(Result $_) use ($connection) {
        $queryBuilder = $connection->createQueryBuilder();
        
        return $connection
            ->query($queryBuilder)
            ->select('*')
            ->from('test', 't')
            ->where($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('t.id', '?'),
                $queryBuilder->expr()->eq('t.id', '?')
            ))
            ->setParameters(['1', '2']);
    })
    ->then(function(Result $result) {
        $numberOfRows = $result->fetchCount();
        $firstRow = $result->fetchFirstRow();
        $allRows = $result->fetchAllRows();
    });
```

You can use, at this moment, adapters for `mysql`, `postgresql`, and `sqlite`.

## Native queries

The connection object also accepts native queries. This is a specific PostgreSQL
query on top of [Voryx/PgAsync](https://github.com/voryx/PgAsync)
implementation. By using `query`, you normalize the way parameters are
introduced, in that case. By using `queryNative`, you will use the original
format.

```php
$connection->query('select * from test where id = ?', ['1']);
$connection->queryNative('select * from test where id = $1', ['1']);
```

## Tests

You can run tests by running `docker-compose up` and by doing
`php vendor/bin/phpunit`.
