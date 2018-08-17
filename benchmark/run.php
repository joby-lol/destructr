<?php
/**
 * This benchmarking script uses the same connection settings as the unit tests,
 * so if you've got that working it should work too.
 */
include_once __DIR__.'/../vendor/autoload.php';

use Digraph\Destructr\Factory;
use Digraph\Destructr\Drivers\MySQLDriver;
use Digraph\Destructr\LegacyDrivers\SQLiteDriver;
use Digraph\Destructr\LegacyDrivers\MySQL56Driver;

const OPS_PER = 1000;
$dsos = [];

$out = [];
$out[] = 'Date: '.date('c');
$out[] = 'Machine: '.gethostname();
$out[] = 'Ops per: '.OPS_PER;
$out[] = 'Each result is the average number of milliseconds it took to complete each operation. Lower is better.';
$out[] = '';
foreach (drivers_list() as $class => $config) {
    $driver = new $class(@$config['dsn'], @$config['username'], @$config['password'], @$config['options']);
    $factory = new Factory($driver, $config['table']);
    $factory->createTable();
    benchmark_empty($factory);
    $out[] = $class;
    $out[] = benchmark_insert($factory);
    $out[] = benchmark_update($factory);
    $out[] = benchmark_search_vcol($factory);
    $out[] = benchmark_search_json($factory);
    $out[]= '';
}
$out[] = '';

file_put_contents(__DIR__.'/results.txt', implode(PHP_EOL, $out));

/**
 * The classes and connection settings for benchmarking
 */
function drivers_list()
{
    @unlink(__DIR__.'/test.sqlite');
    $out = [];
    $out[MySQLDriver::class] = [
        'table' => 'benchmark57',
        'dsn' => 'mysql:host=127.0.0.1;dbname=phpunit',
        'username' => 'travis'
    ];
    $out[MySQL56Driver::class] = [
        'table' => 'benchmark56',
        'dsn' => 'mysql:host=127.0.0.1;dbname=phpunit',
        'username' => 'travis'
    ];
    $out[SQLiteDriver::class] = [
        'table' => 'benchmark',
        'dsn' => 'sqlite:'.__DIR__.'/test.sqlite'
    ];
    return $out;
}

/**
 * Empties a table before beginning
 */
function benchmark_empty(&$factory)
{
    global $dsos;
    $dsos = [];
    foreach ($factory->search()->execute([], null) as $o) {
        $o->delete(true);
    }
}

/**
 * Benchmark insert operations
 */
function benchmark_insert(&$factory)
{
    global $dsos;
    $start = microtime(true);
    for ($i=0; $i < OPS_PER; $i++) {
        $dsos[$i] = $factory->create(
            [
                'dso.id'=>'benchmark-'.$i,
                'dso.type'=>'benchmark-'.($i%2?'odd':'even'),
                'benchmark.mod'=>($i%2?'odd':'even')
            ]
        );
        $dsos[$i]->insert();
    }
    $end = microtime(true);
    $per = round(($end-$start)*100000/OPS_PER)/100;
    return 'insert: '.$per.'ms';
}

/**
 * Benchmark update operations
 */
function benchmark_update(&$factory)
{
    global $dsos;
    $start = microtime(true);
    for ($i=0; $i < OPS_PER; $i++) {
        $dsos[$i]['benchmark.int'] = $i;
        $dsos[$i]['benchmark.string'] = 'benchmark-'.$i;
        $dsos[$i]->update();
    }
    $end = microtime(true);
    $per = round(($end-$start)*100000/OPS_PER)/100;
    return 'update: '.$per.'ms';
}

/**
 * Benchmark searching on a vcol
 */
function benchmark_search_vcol(&$factory)
{
    $start = microtime(true);
    for ($i=0; $i < OPS_PER; $i++) {
        $search = $factory->search();
        $search->where('${dso.type} = :type');
        $search->execute([':type'=>'benchmark-odd']);
    }
    $end = microtime(true);
    $per = round(($end-$start)*100000/OPS_PER)/100;
    return 'search vcol: '.$per.'ms';
}

/**
 * Benchmark searching on a JSON value
 */
function benchmark_search_json(&$factory)
{
    $start = microtime(true);
    for ($i=0; $i < OPS_PER; $i++) {
        $search = $factory->search();
        $search->where('${benchmark.mod} = :type');
        $search->execute([':type'=>'even']);
    }
    $end = microtime(true);
    $per = round(($end-$start)*100000/OPS_PER)/100;
    return 'search json: '.$per.'ms';
}
