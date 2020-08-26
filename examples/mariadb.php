<?php
include __DIR__ . '/../vendor/autoload.php';

$driver = \Destructr\DriverFactory::factoryFromPDO(
    new \PDO('mysql:server=localhost;port=3307;dbname=destructr', 'root'),
    'mariadb'
);

$factory = new \Destructr\Factory($driver, 'example_table');
$factory->createTable();

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

$search = $factory->search();

// $search->where('${random_data} = :q');
// $search->execute(['q'=>'rw7nivub9bhhh3t4']);

$search->where('${dso.id} = :q');
$search->execute(['q'=>'rw7nivub9bhhh3t4']);

// foreach($search->execute() as $dso) {
//     var_dump($dso->get());
// }