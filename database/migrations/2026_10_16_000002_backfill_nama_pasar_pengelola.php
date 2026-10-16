<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $markets = [
            'Koperasi Pengelola Pasar Jodoh' => 'Pasar Jodoh',
            'CV Pengelola Pasar Batu Aji' => 'Pasar Batu Aji',
        ];

        foreach ($markets as $company => $market) {
            DB::table('pengelola_pasar')
                ->where('nama_perusahaan', $company)
                ->whereNull('nama_pasar')
                ->update(['nama_pasar' => $market]);
        }
    }

    public function down(): void
    {
        DB::table('pengelola_pasar')
            ->whereIn('nama_pasar', ['Pasar Jodoh', 'Pasar Batu Aji'])
            ->update(['nama_pasar' => null]);
    }
};
