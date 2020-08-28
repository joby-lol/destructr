<?php

use Destructr\Factory;

include __DIR__ . '/../vendor/autoload.php';

/*
Constructing MariaDB drivers should be done using factoryFromPDO,
so that they use the MariaDB driver instead of the MySQL driver.
*/
$driver = \Destructr\DriverFactory::factory(
    'mysql:server=localhost;port=3306;dbname=destructr',
    'root'
);

/*
Creates a factory using the table 'example_table', and creates
the necessary table. Note that prepareEnvironment() can safely be called
multiple times.
*/
include __DIR__ . '/example_factory.php';
$factory = new ExampleFactory($driver, 'example_table');
$factory->prepareEnvironment();
$factory->updateEnvironment();

/*
The following can be uncommented to insert dummy records
into the given table.
*/
for($i = 0; $i < 10; $i++) {
    $obj = $factory->create(
        [
            'dso.type'=>'foobar',
            'random_data' => md5(rand())
        ]
    );
    $obj->insert();
}

/*
Search by random data field
*/
$search = $factory->search();
$search->where('${random_data} = :q');
$result = $search->execute(['q'=>'rw7nivub9bhhh3t4']);

/*
Search by dso.id, which is much faster because it's indexed
*/
// $search = $factory->search();
// $search->where('${dso.id} = :q');
// $result = $search->execute(['q'=>'rw7nivub9bhhh3t4']);
