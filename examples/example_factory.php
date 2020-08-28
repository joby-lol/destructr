<?php

use Destructr\Factory;

class ExampleFactory extends Factory {
    /**
     * Example factory with a different schema, to index on random_data, but not
     * by dso_type.
     * 
     * Also uses a different column name for dso.id
     */
    protected $schema = [
        'dso.id' => [
            'name'=>'dso_id_other_name',
            'type'=>'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true,
            'primary' => true
        ],
        'dso.deleted' => [
            'name'=>'dso_deleted',
            'type'=>'INT',
            'index'=>'BTREE'
        ],
        'random_data' => [
            'name'=>'random_data',
            'type'=>'VARCHAR(100)',
            'index'=>'BTREE'
        ]
    ];
}