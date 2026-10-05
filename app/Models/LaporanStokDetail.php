<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanStokDetail extends Model
{
    protected $table = 'laporan_stok_detail';

    protected $guarded = [];

    protected $casts = ['jumlah' => 'decimal:3'];

    public function komoditas()
    {
        return $this->belongsTo(KomoditasStok::class, 'komoditas_stok_id');
    }

    public function laporan()
    {
        return $this->belongsTo(LaporanStok::class, 'laporan_stok_id');
    }
}
