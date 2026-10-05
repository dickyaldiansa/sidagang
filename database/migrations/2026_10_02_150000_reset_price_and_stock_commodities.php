<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $items = [
            ['Beras medium', 'kg'], ['Gula pasir curah', 'kg'], ['Cabe merah keriting', 'kg'], ['Cabe rawit hijau', 'kg'],
            ['Bawang merah', 'kg'], ['Bawang putih', 'kg'], ['Minyak goreng minyakita', 'liter'], ['Daging sapi beku', 'kg'],
            ['Daging ayam ras', 'kg'], ['Telur ayam ras', 'kg'], ['Tepung terigu', 'kg'], ['Susu bubuk cap bendera (400-500 gram)/box', 'box'],
            ['Susu balita SGM 400 gr/box', 'box'], ['Udang', 'kg'], ['Ikan benggol', 'kg'], ['Mie Instan (Per 1 Bungkus)', 'bks'],
            ['Tempe/kg', 'kg'], ['Tahu Mentah/kg', 'kg'], ['Pisang (Barangan per Kg)', 'kg'], ['Jeruk', 'kg'],
        ];

        DB::transaction(function () use ($items) {
            DB::table('satuan')->updateOrInsert(['kode' => 'box'], ['nama' => 'Box', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            $unitIds = DB::table('satuan')->whereIn('kode', ['kg', 'liter', 'bks', 'box'])->pluck('id', 'kode');

            foreach ([
                ['table' => 'komoditas_harga', 'detail' => 'laporan_harga_detail', 'foreign' => 'komoditas_harga_id', 'prefix' => 'HRG'],
                ['table' => 'komoditas_stok', 'detail' => 'laporan_stok_detail', 'foreign' => 'komoditas_stok_id', 'prefix' => 'STK'],
            ] as $target) {
                $existing = DB::table($target['table'])->get()->keyBy(fn ($row) => mb_strtolower($row->nama));
                $keepIds = [];

                foreach ($items as $index => [$name, $unit]) {
                    $record = $existing->get(mb_strtolower($name));
                    $values = ['nama' => $record?->nama ?? $name, 'satuan_id' => $unitIds[$unit], 'urutan' => $index + 1, 'is_active' => true, 'updated_at' => now()];
                    if ($target['table'] === 'komoditas_harga') $values['het_ha'] = null;

                    if ($record) {
                        DB::table($target['table'])->where('id', $record->id)->update($values);
                        $keepIds[] = $record->id;
                    } else {
                        $keepIds[] = DB::table($target['table'])->insertGetId($values + ['kode' => $target['prefix'].str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), 'created_at' => now()]);
                    }
                }

                $legacyIds = DB::table($target['table'])->whereNotIn('id', $keepIds)->pluck('id');
                if ($legacyIds->isNotEmpty()) {
                    DB::table($target['detail'])->whereIn($target['foreign'], $legacyIds)->delete();
                    DB::table($target['table'])->whereIn('id', $legacyIds)->delete();
                }
            }
        });
    }

    public function down(): void
    {
        // Pemulihan master dan histori dummy dilakukan melalui file backup sebelum migrasi.
    }
};
