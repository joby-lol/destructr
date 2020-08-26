<?php
/* Destructr | https://github.com/jobyone/destructr | MIT License */
namespace Destructr\LegacyDrivers;

use Destructr\Drivers\AbstractDriver;
use Destructr\DSOInterface;
use Destructr\Factory;
use Destructr\Search;
use Flatrr\FlatArray;

/**
 * This driver is for supporting older SQL servers that don't have their own
 * JSON functions. It uses a highly suspect alternative JSON serialization and
 * user-defined function.
 *
 * There are also DEFINITELY bugs in legacy drivers. They should only be used
 * for very simple queries. These are probably also bugs that cannot be fixed.
 * Legacy drivers shouldn't really be considered "supported" per se.
 */
class AbstractLegacyDriver extends AbstractDriver
{
    const EXTENSIBLE_VIRTUAL_COLUMNS = false;

    public function select(string $table, Search $search, array $params)
    {
        $results = parent::select($table, $search, $params);
        foreach ($results as $rkey => $row) {
            $new = new FlatArray();
            foreach (json_decode($row['json_data'], true) as $key => $value) {
                $new->set(str_replace('|', '.', $key), $value);
            }
            $results[$rkey]['json_data'] = json_encode($new->get());
        }
        return $results;
    }

    protected function sql_count($args)
    {
        //extract query parts from Search and expand paths
        $where = $this->expandPaths($args['search']->where());
        //select from
        $out = ["SELECT count(dso_id) FROM `{$args['table']}`"];
        //where statement
        if ($where !== null) {
            $out[] = "WHERE ".$where;
        }
        //return
        return implode(PHP_EOL, $out).';';
    }

    protected function sql_select($args)
    {
        //extract query parts from Search and expand paths
        $where = $this->expandPaths($args['search']->where());
        $order = $this->expandPaths($args['search']->order());
        $limit = $args['search']->limit();
        $offset = $args['search']->offset();
        //select from
        $out = ["SELECT * FROM `{$args['table']}`"];
        //where statement
        if ($where !== null) {
            $out[] = "WHERE ".$where;
        }
        //order statement
        if ($order !== null) {
            $out[] = "ORDER BY ".$order;
        }
        //limit
        if ($limit !== null) {
            $out[] = "LIMIT ".$limit;
        }
        //offset
        if ($offset !== null) {
            $out[] = "OFFSET ".$offset;
        }
        //return
        return implode(PHP_EOL, $out).';';
    }

    protected function sql_ddl($args=array())
    {
        $out = [];
        $out[] = "CREATE TABLE `{$args['table']}` (";
        $lines = [];
        $lines[] = "`json_data` TEXT DEFAULT NULL";
        foreach ($args['virtualColumns'] as $path => $col) {
            $lines[] = "`{$col['name']}` {$col['type']}";
        }
        foreach ($args['virtualColumns'] as $path => $col) {
            if (@$col['unique'] && $as = @$col['index']) {
                $lines[] = "UNIQUE KEY `{$args['table']}_{$col['name']}_idx` (`{$col['name']}`) USING $as";
            } elseif ($as = @$col['index']) {
                $lines[] = "KEY `{$args['table']}_{$col['name']}_idx` (`{$col['name']}`) USING $as";
            }
        }
        $out[] = implode(','.PHP_EOL, $lines);
        $out[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        return implode(PHP_EOL, $out);
    }

    public function update(string $table, DSOInterface $dso) : bool
    {
        if (!$dso->changes() && !$dso->removals()) {
            return true;
        }
        $s = $this->getStatement(
            'setJSON',
            ['table'=>$table]
        );
        $params = $this->legacyParams($dso);
        $out = $s->execute($params);
        return $out;
    }

    protected function sql_setJSON($args)
    {
        $out = [];
        $out[] = 'UPDATE `'.$args['table'].'`';
        $out[] = 'SET';
        foreach (Factory::CORE_VIRTUAL_COLUMNS as $v) {
            $out[] = '`'.$v['name'].'` = :'.$v['name'].',';
        }
        $out[] = '`json_data` = :data';
        $out[] = 'WHERE `dso_id` = :dso_id';
        return implode(PHP_EOL, $out).';';
    }

    public function insert(string $table, DSOInterface $dso) : bool
    {
        $s = $this->getStatement(
            'insert',
            ['table'=>$table]
        );
        $params = $this->legacyParams($dso);
        return $s->execute($params);
    }

    protected function legacyParams(DSOInterface $dso)
    {
        $params = [':data' => $this->json_encode($dso->get())];
        foreach (Factory::CORE_VIRTUAL_COLUMNS as $vk => $vv) {
            $params[':'.$vv['name']] = $dso->get($vk);
        }
        return $params;
    }

    protected function sql_insert($args)
    {
        $out = [];
        $out[] = 'INSERT INTO `'.$args['table'].'`';
        $out[] = '(`json_data`,`dso_id`,`dso_type`,`dso_deleted`)';
        $out[] = 'VALUES (:data, :dso_id, :dso_type, :dso_deleted)';
        return implode(PHP_EOL, $out).';';
    }

    public function delete(string $table, DSOInterface $dso) : bool
    {
        $s = $this->getStatement(
            'delete',
            ['table'=>$table]
        );
        $out = $s->execute([
            ':dso_id' => $dso['dso.id']
        ]);
        // if (!$out) {
        //     var_dump($s->errorInfo());
        // }
        return $out;
    }

    protected function sql_delete($args)
    {
        return 'DELETE FROM `'.$args['table'].'` WHERE `dso_id` = :dso_id;';
    }

    public function json_encode($a, array &$b = null, string $prefix = '')
    {
        if ($b === null) {
            $b = [];
            $this->json_encode($a, $b, '');
            return json_encode($b);
        } else {
            if (is_array($a)) {
                foreach ($a as $ak => $av) {
                    if ($prefix == '') {
                        $nprefix = $ak;
                    } else {
                        $nprefix = $prefix.'|'.$ak;
                    }
                    $this->json_encode($av, $b, $nprefix);
                }
            } else {
                $b[$prefix] = $a;
            }
        }
    }
}
