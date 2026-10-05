<?php

require dirname(__DIR__).'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? null;
if (! $path || ! is_file($path)) {
    fwrite(STDERR, "Workbook tidak ditemukan.\n");
    exit(1);
}

$workbook = IOFactory::load($path);
foreach ($workbook->getWorksheetIterator() as $sheet) {
    echo "\n=== {$sheet->getTitle()} ({$sheet->getHighestRow()} x {$sheet->getHighestColumn()}) ===\n";
    echo 'Merged: '.implode(', ', $sheet->getMergeCells())."\n";
    for ($row = 1; $row <= $sheet->getHighestRow(); $row++) {
        $values = [];
        foreach ($sheet->getRowIterator($row, $row)->current()->getCellIterator() as $cell) {
            $value = $cell->getValue();
            if ($value !== null && $value !== '') {
                $values[] = $cell->getCoordinate().'='.str_replace(["\r", "\n"], ' ', (string) $value);
            }
        }
        if ($values !== []) {
            echo implode(' | ', $values)."\n";
        }
    }
}
