<?php
include __DIR__ . '/../vendor/autoload.php';

/*
SQLite drivers can be created by the default factory.
A charset of UTF8 should be specified, to avoid character encoding
issues.
*/
$driver = \Destructr\DriverFactory::factory(
    'sqlite:'.__DIR__.'/example.sqlite'
);

/*
Creates a factory using the table 'example_table', and creates
the necessary table. Note that createTable() can safely be called
multiple times.
*/
$factory = new \Destructr\Factory($driver, 'example_table');
$factory->createTable();

/*
The following can be uncommented to insert 1,000 dummy records
into the given table.
*/
// ini_set('max_execution_time','0');
// for($i = 0; $i < 1000; $i++) {
//     $obj = $factory->create(
//         [
//             'dso.type'=>'foobar',
//             'random_data' => md5(rand())
//         ]
//     );
//     $obj->insert();
// }

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
