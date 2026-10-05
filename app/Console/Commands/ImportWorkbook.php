<?php

namespace App\Console\Commands;

use App\Services\WorkbookImporter;
use Illuminate\Console\Command;

class ImportWorkbook extends Command
{
    protected $signature = 'sidagang:import-workbook {path : Lokasi file XLSX} {--user= : ID user pencatat import}';

    protected $description = 'Import workbook historis Harga dan Stok Bapok';

    public function handle(WorkbookImporter $importer): int
    {
        $path = $this->argument('path');
        if (! is_file($path)) {
            $this->error('File tidak ditemukan: '.$path);

            return self::FAILURE;
        }

        $this->info('Membaca '.basename($path).' ...');
        $summary = $importer->import($path, $this->option('user') ? (int) $this->option('user') : null);
        $this->table(['Data', 'Jumlah'], collect($summary)->map(fn ($value, $key) => [$key, $value])->values()->all());
        $this->info('Import selesai tanpa duplikasi laporan.');

        return self::SUCCESS;
    }
}
