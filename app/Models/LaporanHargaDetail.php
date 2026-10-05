<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanHargaDetail extends Model
{
    protected $table = 'laporan_harga_detail';

    protected $guarded = [];

    protected $casts = ['harga' => 'decimal:2'];

    public function komoditas()
    {
        return $this->belongsTo(KomoditasHarga::class, 'komoditas_harga_id');
    }

    public function laporan()
    {
        return $this->belongsTo(LaporanHarga::class, 'laporan_harga_id');
    }
}
