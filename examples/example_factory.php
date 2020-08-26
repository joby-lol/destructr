<?php

use Destructr\Factory;

class ExampleFactory extends Factory {
    /**
     * Virtual columns are only supported by modern SQL servers. Most of the
     * legacy drivers will only use the ones defined in CORE_VIRTUAL_COLUMNS,
     * but that should be handled automatically.
     */
    protected $virtualColumns = [
        'dso.id' => [
            'name'=>'dso_id',
            'type'=>'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true,
            'primary' => true
        ],
        'dso.type' => [
            'name'=>'dso_type',
            'type'=>'VARCHAR(30)',
            'index'=>'BTREE'
        ],
        'dso.deleted' => [
            'name'=>'dso_deleted',
            'type'=>'BIGINT',
            'index'=>'BTREE'
        ],
        'example.indexed' => [
            'name'=>'example_indexed',
            'type'=>'VARCHAR(100)',
            'index'=>'BTREE'
        ]
    ];
}