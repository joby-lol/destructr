<?php
/**
 * This file demonstrates some basic uses of Destructr, from the creation
 * of a connection and factory, through to creating, inserting, updating,
 * deleting, and querying data.
 */
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
multiple times. updateEnvironment shouldn't be used this way in production,
as if it is called more than once per second during a schema change, errors
may be introduced.
 */
include __DIR__ . '/example_factory.php';
$factory = new ExampleFactory($driver, 'example_table');
$factory->prepareEnvironment();
$factory->updateEnvironment();

/*
Inserting a record
 */
$obj = $factory->create(
    [
        'dso.type'=>'foobar',
        'random_data' => md5(rand())
    ]
);
$obj->insert();

/*
Search by random data field, which is indexed due to the
ExampleFactory class' $schema property.
 */
$search = $factory->search();
$search->where('${random_data} LIKE :q');
$result = $search->execute(['q'=>'ab%']);
foreach($result as $r) {
    var_dump($r->get());
}
