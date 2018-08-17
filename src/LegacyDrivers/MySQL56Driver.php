<?php
/* Destructr | https://gitlab.com/byjoby/destructr | MIT License */
namespace Destructr\LegacyDrivers;

/**
 * What this driver supports: MySQL 5.6, as long as you have permissions to
 * create user-defined functions
 *
 * Also, this driver does flatten its JSON data, so complex unstructured data
 * will not be straight compatible with modern drivers. You'll need to run a
 * migration tool to unflatten and resave everything.
 *
 * Complex queries on JSON fields will almost certainly fail in edge cases.
 * This should work for most basic uses though.
 */
class MySQL56Driver extends AbstractLegacyDriver
{
    public function createTable(string $table, array $virtualColumns) : bool
    {
        $this->createLegacyUDF();
        return parent::createTable($table, $virtualColumns);
    }

    public function createLegacyUDF()
    {
        $drop = $this->pdo->exec('DROP FUNCTION IF EXISTS `destructr_json_extract`;');
        $create = $this->pdo->exec(file_get_contents(__DIR__.'/destructr_json_extract.sql'));
    }

    protected function expandPath(string $path) : string
    {
        $path = str_replace('.', '|', $path);
        return "destructr_json_extract(`json_data`,'$.{$path}')";
    }
}
