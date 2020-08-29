<?php

use Destructr\Factory;

class ExampleFactory extends Factory
{
    /**
     * Example factory with a different schema, to index on random_data for faster searching
     */
    protected $schema = [
        'dso.id' => [
            'name' => 'dso_id', //column name to be used
            'type' => 'VARCHAR(16)', //column type
            'index' => 'BTREE', //whether/how to index
            'unique' => true, //whether column should be unique
            'primary' => true, //whether column should be the primary key
        ],
        'dso.type' => [
            'name' => 'dso_type',
            'type' => 'VARCHAR(30)',
            'index' => 'BTREE',
        ],
        'dso.deleted' => [
            'name' => 'dso_deleted',
            'type' => 'BIGINT',
            'index' => 'BTREE',
        ],
        'random_data' => [
            'name' => 'random_data',
            'type' => 'VARCHAR(64)',
            'index' => 'BTREE',
        ],
    ];
}
