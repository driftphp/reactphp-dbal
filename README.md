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
        'id' => '1',
        'field1' => 'val1',
        'field2' => 'val2',
    ])
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

## Connection shortcuts

This DBAL introduce some shortcuts useful for your projects on top of Doctrine
query builder and escaping parametrization.

### Insert

Inserts a new row in a table. Needs the table and an array with fields and their
values. Returns a Promise.

```php
$connection->insert('test', [
    'id' => '1',
    'field1' => 'value1'
]);
```

### Update

Updates an existing row from a table. Needs the table, an identifier as array
and an array of fields with their values. Returns a Promise.

```php
$connection->update(
    'test',
    ['id' => '1'],
    [
        'field1' => 'value1',
        'field2' => 'value2',
    ]
);
```

### Upsert

Insert a row if not exists. Otherwise, it will update the existing row with
given values. Needs the table, an identifier as array and an array of fields
with their values. Returns a Promise.

```php
$connection->upsert(
    'test',
    ['id' => '1'],
    [
        'field1' => 'value1',
        'field2' => 'value2',
    ]
);
```

### Delete

Deletes a row if exists. Needs the table and the identifier as array. Returns a
Promise.

```php
$connection->delete('test', [
    'id' => '1'
]);
```

### Find one by

Find a row given a where clause. Needs the table and an array of fields with 
their values. Returns a Promise with, eventually, the result as array of all
found rows.

```php
$connection
    ->findOneById('test', [
        'id' => '1'
    ])
    ->then(function(?array $result) {
        if (is_null($result)) {
            // Row with ID=1 not found
        } else {
            // Row with ID=1 found.
            echo $result['id'];
        }   
    });
```

### Find by

Find all rows given an array of where clauses. Needs the table and an array of
fields with their values. Returns a Promise with, eventually, the result as
array of all found rows.

```php
$connection
    ->findBy('test', [
        'age' => '33'
    ])
    ->then(function(array $result) {
        echo 'Found ' . count($result) . ' rows'; 
    });
```

## Tests

You can run tests by running `docker-compose up` and by doing `phpunit`.
