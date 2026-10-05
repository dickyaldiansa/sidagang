<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$sourcePath = __DIR__.'/../database/backups/database-before-mysql-20261002.sqlite';

if (! is_file($sourcePath)) {
    throw new RuntimeException("SQLite backup tidak ditemukan: {$sourcePath}");
}

$source = new PDO('sqlite:'.$sourcePath);
$source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$target = DB::connection()->getPdo();

if (DB::connection()->getDriverName() !== 'mysql') {
    throw new RuntimeException('Koneksi tujuan harus MySQL.');
}

$tables = $source->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations' ORDER BY name")
    ->fetchAll(PDO::FETCH_COLUMN);

$quote = static fn (string $identifier): string => '`'.str_replace('`', '``', $identifier).'`';
$target->exec('SET FOREIGN_KEY_CHECKS=0');
$target->beginTransaction();

try {
    // Beberapa migrasi membuat data bawaan. MySQL ini baru dibuat untuk
    // menerima snapshot SQLite, sehingga data bawaan tersebut harus diganti.
    foreach ($tables as $table) {
        $target->exec('DELETE FROM '.$quote($table));
    }

    foreach ($tables as $table) {
        $rows = $source->query('SELECT * FROM '.$quote($table))->fetchAll(PDO::FETCH_ASSOC);

        if ($rows === []) {
            continue;
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map($quote, $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $statement = $target->prepare("INSERT INTO {$quote($table)} ({$columnList}) VALUES ({$placeholders})");

        foreach ($rows as $row) {
            $statement->execute(array_values($row));
        }

        fwrite(STDOUT, "{$table}: ".count($rows)." baris\n");
    }

    $target->commit();
    $target->exec('SET FOREIGN_KEY_CHECKS=1');
} catch (Throwable $exception) {
    if ($target->inTransaction()) {
        $target->rollBack();
    }

    $target->exec('SET FOREIGN_KEY_CHECKS=1');
    throw $exception;
}
