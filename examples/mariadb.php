<?php
include __DIR__ . '/../vendor/autoload.php';

/*
Constructing MariaDB drivers should be done using factoryFromPDO,
so that they use the MariaDB driver instead of the MySQL driver.
*/
$driver = \Destructr\DriverFactory::factoryFromPDO(
    new \PDO(
        'mysql:server=localhost;port=3307;dbname=destructr',
        'root'
    ),
    'mariadb'
);

/*
Creates a factory using the table 'example_table', and creates
the necessary table. Note that prepareEnvironment() can safely be called
multiple times.
*/
$factory = new \Destructr\Factory($driver, 'example_table');
$factory->prepareEnvironment();

/*
The following can be uncommented to insert 1,000 dummy records
into the given table.
*/
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
