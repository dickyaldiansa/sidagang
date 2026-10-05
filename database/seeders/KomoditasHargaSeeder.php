<?php

namespace Database\Seeders;

use App\Models\KelompokKomoditas;
use App\Models\KomoditasHarga;
use App\Models\KomoditasStok;
use App\Models\Satuan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KomoditasHargaSeeder extends Seeder
{
    public function run(): void
    {
        $commodities = [
            ['Beras Premium', 'kg', 'BERAS'],
            ['Beras Medium', 'kg', 'BERAS'],
            ['Gula Pasir — Gula Kristal Putih', 'kg', 'GULA'],
            ['Minyak Goreng — Kemasan', 'liter', 'MINYAK'],
            ['Minyak Goreng — Curah', 'liter', 'MINYAK'],
            ['Tepung Terigu', 'kg', 'LAINNYA'],
            ['Daging Sapi Beku', 'kg', 'DAGING'],
            ['Daging Ayam Ras Beku', 'kg', 'DAGING'],
            ['Telur Ayam Ras', 'kg', 'TELUR'],
            ['Cabai Merah', 'kg', 'CABAI'],
            ['Cabai Rawit', 'kg', 'CABAI'],
            ['Bawang Merah', 'kg', 'BAWANG'],
            ['Bawang Putih', 'kg', 'BAWANG'],
            ['Kedelai', 'kg', 'KEDELAI'],
            ['Kentang', 'kg', 'SAYURAN'],
            ['Ikan Tongkol', 'kg', 'IKAN'],
            ['Tomat', 'kg', 'SAYURAN'],
            ['Bawang Bombay', 'kg', 'BAWANG'],
            ['Wortel', 'kg', 'SAYURAN'],
        ];

        DB::transaction(function () use ($commodities): void {
            $names = collect($commodities)->pluck(0);
            $obsoleteIds = KomoditasHarga::whereNotIn('nama', $names)->pluck('id');

            // Detail lama harus dihapus lebih dahulu karena berelasi ke master komoditas.
            DB::table('laporan_harga_detail')->whereIn('komoditas_harga_id', $obsoleteIds)->delete();
            KomoditasHarga::whereIn('id', $obsoleteIds)->delete();

            foreach ($commodities as $index => [$name, $unitCode, $groupCode]) {
                $unit = Satuan::firstOrCreate(
                    ['kode' => $unitCode],
                    ['nama' => $unitCode === 'kg' ? 'Kilogram' : 'Liter', 'is_active' => true]
                );
                $group = KelompokKomoditas::where('kode', $groupCode)->firstOrFail();

                KomoditasHarga::updateOrCreate(
                    ['nama' => $name],
                    [
                        'kode' => 'KH'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                        'satuan_id' => $unit->id,
                        'kelompok_id' => $group->id,
                        'urutan' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }

            $obsoleteStockIds = KomoditasStok::whereNotIn('nama', $names)->pluck('id');
            DB::table('laporan_stok_detail')->whereIn('komoditas_stok_id', $obsoleteStockIds)->delete();
            KomoditasStok::whereIn('id', $obsoleteStockIds)->delete();

            foreach ($commodities as $index => [$name, $unitCode]) {
                $unit = Satuan::where('kode', $unitCode)->firstOrFail();

                KomoditasStok::updateOrCreate(
                    ['nama' => $name],
                    [
                        'kode' => 'KS'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                        'satuan_id' => $unit->id,
                        'urutan' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
