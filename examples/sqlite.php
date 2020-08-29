<?php

use Destructr\Factory;

include __DIR__ . '/../vendor/autoload.php';

/*
SQLite drivers can be created by the default factory.
A charset of UTF8 should be specified, to avoid character encoding
issues.
 */
$driver = \Destructr\DriverFactory::factory(
    'sqlite:' . __DIR__ . '/example.sqlite'
);

/*
Creates a factory using the table 'example_table', and creates
the necessary table. Note that prepareEnvironment() can safely be called
multiple times.
 */
include __DIR__ . '/example_factory.php';
$factory = new Factory($driver, 'example_table');
$factory->prepareEnvironment();
$factory->updateEnvironment();

/*
The following can be uncommented to insert dummy records
into the given table.
 */
// ini_set('max_execution_time','0');
// for($i = 0; $i < 10; $i++) {
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
$search->where('${random_data} LIKE :q');
$result = $search->execute(['q'=>'%ab%']);
foreach($result as $r) {
    var_dump($r->get());
    $r['random_data_2'] = md5(rand());
    $r->update();
}

/*
Search by dso.id, which is much faster because it's indexed
 */
// $search = $factory->search();
// $search->where('${dso.id} = :q');
// $result = $search->execute(['q'=>'rw7nivub9bhhh3t4']);
