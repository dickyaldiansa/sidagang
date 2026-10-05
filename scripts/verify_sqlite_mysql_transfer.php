<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$source = new PDO('sqlite:'.__DIR__.'/../database/backups/database-before-mysql-20261002.sqlite');
$target = DB::connection()->getPdo();

$tables = $source->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations' ORDER BY name")
    ->fetchAll(PDO::FETCH_COLUMN);
$quote = static fn (string $identifier): string => '`'.str_replace('`', '``', $identifier).'`';
$mismatches = [];

foreach ($tables as $table) {
    $sqliteCount = (int) $source->query('SELECT COUNT(*) FROM '.$quote($table))->fetchColumn();
    $mysqlCount = (int) $target->query('SELECT COUNT(*) FROM '.$quote($table))->fetchColumn();

    if ($sqliteCount !== $mysqlCount) {
        $mismatches[] = "{$table}: SQLite={$sqliteCount}, MySQL={$mysqlCount}";
    }
}

if ($mismatches !== []) {
    fwrite(STDERR, implode(PHP_EOL, $mismatches).PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'OK: '.count($tables)." tabel data memiliki jumlah baris yang sama.\n");
