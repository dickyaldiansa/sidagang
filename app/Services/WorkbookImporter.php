<?php

namespace App\Services;

use App\Models\KelompokKomoditas;
use App\Models\KomoditasHarga;
use App\Models\KomoditasStok;
use App\Models\LaporanHarga;
use App\Models\LaporanHargaDetail;
use App\Models\Pasar;
use App\Models\PeriodeSurveyHarga;
use App\Models\Satuan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkbookImporter
{
    public function import(string $path, ?int $userId = null): array
    {
        $workbook = IOFactory::load($path);
        $priceSheet = $workbook->getSheetByName('Harga Bapok');
        $stockSheet = $workbook->getSheetByName('Stok Bapok');

        if (! $priceSheet) {
            throw new \RuntimeException('Sheet Harga Bapok tidak ditemukan.');
        }

        $columns = $this->priceColumns($priceSheet);
        $rows = $this->commodityRows($priceSheet);
        $actor = User::find($userId) ?? User::whereHas('role', fn ($query) => $query->where('name', 'super_admin'))->firstOrFail();
        $markets = Pasar::where('is_active', true)->get()->keyBy(fn (Pasar $market) => $this->marketKey($market->nama));
        $summary = ['commodities' => count($rows), 'markets' => collect($columns)->pluck('market')->unique()->count(), 'dates' => collect($columns)->pluck('date')->unique()->count(), 'reports' => 0, 'details' => 0, 'unavailable' => 0, 'stock_commodities' => 0];

        DB::transaction(function () use ($path, $priceSheet, $stockSheet, $columns, $rows, $actor, $markets, &$summary): void {
            KomoditasHarga::whereNotIn('nama', collect($rows)->pluck('name'))->update(['is_active' => false]);
            $commodities = [];
            foreach ($rows as $row) {
                $unit = $this->unit($row['unit']);
                $group = $this->group($row['name']);
                $commodity = KomoditasHarga::updateOrCreate(
                    ['nama' => $row['name']],
                    ['kode' => $this->uniqueCommodityCode($row['number'], $row['name']), 'satuan_id' => $unit->id, 'kelompok_id' => $group->id, 'urutan' => $row['number'], 'is_active' => true]
                );
                $commodities[$row['row']] = $commodity;
            }

            foreach (collect($columns)->groupBy(fn (array $column) => $column['date'].'|'.$column['market']) as $items) {
                $meta = $items->first();
                $market = $markets->get($meta['market']);
                if (! $market) {
                    throw new \RuntimeException("Pasar {$meta['market']} tidak ditemukan pada master.");
                }

                if (! PeriodeSurveyHarga::whereDate('tanggal', $meta['date'])->exists()) {
                    PeriodeSurveyHarga::create(['tanggal' => $meta['date'], 'deadline' => Carbon::parse($meta['date'])->setTime(9, 0), 'created_by' => $actor->id, 'is_active' => true]);
                }
                $officer = User::where('pasar_id', $market->id)->first() ?? $actor;
                $report = LaporanHarga::whereDate('tanggal', $meta['date'])->where('pasar_id', $market->id)->first() ?? new LaporanHarga(['tanggal' => $meta['date'], 'pasar_id' => $market->id]);
                $report->fill(['petugas_id' => $officer->id, 'status' => 'verified', 'submitted_at' => Carbon::parse($meta['date'])->setTime(8, 0), 'verified_by' => $actor->id, 'verified_at' => Carbon::parse($meta['date'])->setTime(10, 0), 'catatan_verifikasi' => 'Import historis dari '.basename($path)]);
                $report->save();
                $summary['reports']++;

                foreach ($rows as $row) {
                    $column = $items->first();
                    $value = $priceSheet->getCell($column['coordinate'].$row['row'])->getCalculatedValue();
                    $available = is_numeric($value) && (float) $value > 0;
                    LaporanHargaDetail::updateOrCreate(
                        ['laporan_harga_id' => $report->id, 'komoditas_harga_id' => $commodities[$row['row']]->id],
                        ['harga' => $available ? (float) $value : null, 'status_data' => $available ? 'available' : 'unavailable']
                    );
                    $summary['details']++;
                    if (! $available) {
                        $summary['unavailable']++;
                    }
                }
            }

            if ($stockSheet) {
                $stockNames = collect(range(8, 19))->map(fn (int $row) => trim((string) $stockSheet->getCell("D{$row}")->getValue()))->filter();
                KomoditasStok::whereNotIn('nama', $stockNames)->update(['is_active' => false]);
                for ($row = 8; $row <= 19; $row++) {
                    $name = trim((string) $stockSheet->getCell("D{$row}")->getValue());
                    if ($name === '') {
                        continue;
                    }
                    $unit = $this->unit((string) $stockSheet->getCell("E{$row}")->getValue());
                    KomoditasStok::updateOrCreate(['nama' => $name], ['kode' => 'KS'.str_pad((string) ($row - 7), 2, '0', STR_PAD_LEFT), 'satuan_id' => $unit->id, 'urutan' => $row - 7, 'is_active' => true]);
                    $summary['stock_commodities']++;
                }
            }

            DB::table('import_logs')->insert(['filename' => basename($path), 'user_id' => $actor->id, 'total_rows' => $summary['details'], 'success_rows' => $summary['details'], 'failed_rows' => 0, 'summary' => json_encode($summary), 'created_at' => now()]);
        });

        return $summary;
    }

    private function priceColumns(Worksheet $sheet): array
    {
        $mergedMarkets = [];
        foreach ($sheet->getMergeCells() as $range) {
            [$start, $end] = Coordinate::rangeBoundaries($range);
            if ($start[1] === 6 && $end[1] === 6) {
                $name = (string) $sheet->getCell(Coordinate::stringFromColumnIndex($start[0]).'6')->getValue();
                for ($column = $start[0]; $column <= $end[0]; $column++) {
                    $mergedMarkets[$column] = $this->marketKey($name);
                }
            }
        }

        $columns = [];
        for ($column = 7; $column <= 54; $column++) {
            $header = (string) $sheet->getCell(Coordinate::stringFromColumnIndex($column).'7')->getValue();
            if (! preg_match('~(\d{2}/\d{2}/\d{4})~', $header, $matches) || ! isset($mergedMarkets[$column])) {
                continue;
            }
            $columns[] = ['coordinate' => Coordinate::stringFromColumnIndex($column), 'market' => $mergedMarkets[$column], 'date' => Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d')];
        }

        return $columns;
    }

    private function commodityRows(Worksheet $sheet): array
    {
        $rows = [];
        for ($row = 8; $row <= $sheet->getHighestRow(); $row++) {
            $number = $sheet->getCell("D{$row}")->getValue();
            $name = trim((string) $sheet->getCell("E{$row}")->getValue());
            if (! is_numeric($number) || $name === '') {
                continue;
            }
            $rows[] = ['row' => $row, 'number' => (int) $number, 'name' => $name, 'unit' => trim((string) $sheet->getCell("F{$row}")->getValue())];
        }

        return $rows;
    }

    private function marketKey(string $name): string
    {
        $name = Str::lower($name);

        return match (true) {
            str_contains($name, 'tos 3000') => 'tos3000',
            str_contains($name, 'pujabahari') => 'pujabahari',
            str_contains($name, 'mega legenda') => 'megalegenda',
            str_contains($name, 'botania 2') => 'botania2',
            default => Str::slug($name),
        };
    }

    private function unit(string $code): Satuan
    {
        $code = Str::lower(trim($code));
        $names = ['kg' => 'Kilogram', 'ton' => 'Ton', 'ikat' => 'Ikat', 'lt' => 'Liter', 'liter' => 'Liter', 'papan' => 'Papan', 'ekor' => 'Ekor', 'bks' => 'Bungkus', 'gr' => 'Gram'];

        return Satuan::firstOrCreate(['kode' => $code], ['nama' => $names[$code] ?? Str::title($code), 'is_active' => true]);
    }

    private function group(string $name): KelompokKomoditas
    {
        $nameLower = Str::lower($name);
        $rules = ['BERAS' => ['beras'], 'KEDELAI' => ['kedelai', 'tempe', 'tahu'], 'CABAI' => ['cabai'], 'BAWANG' => ['bawang'], 'GULA' => ['gula'], 'MINYAK' => ['minyak'], 'TELUR' => ['telur'], 'DAGING' => ['daging', 'ayam'], 'IKAN' => ['ikan', 'udang'], 'SAYURAN' => ['wortel', 'tomat', 'kentang', 'bayam', 'sawi', 'kangkung', 'ketimun', 'kacang panjang', 'ketela'], 'BUAH' => ['pisang', 'jeruk', 'mangga', 'nanas', 'semangka', 'apel', 'melon', 'pepaya'], 'KACANG' => ['kacang']];
        foreach ($rules as $code => $words) {
            foreach ($words as $word) {
                if (str_contains($nameLower, $word)) {
                    return KelompokKomoditas::firstOrCreate(['kode' => $code], ['nama' => Str::title(Str::lower($code)), 'urutan' => 99, 'is_active' => true]);
                }
            }
        }

        return KelompokKomoditas::firstOrCreate(['kode' => 'LAINNYA'], ['nama' => 'Lainnya', 'urutan' => 99, 'is_active' => true]);
    }

    private function uniqueCommodityCode(int $number, string $name): string
    {
        $existing = KomoditasHarga::where('nama', $name)->first();
        if ($existing) {
            return $existing->kode;
        }
        $base = 'KH'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);

        return KomoditasHarga::where('kode', $base)->exists() ? 'XLS'.str_pad((string) $number, 3, '0', STR_PAD_LEFT) : $base;
    }
}
