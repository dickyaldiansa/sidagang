<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KomoditasHarga extends Model
{
    protected $table = 'komoditas_harga';

    protected $guarded = [];

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }

    public function kelompok()
    {
        return $this->belongsTo(KelompokKomoditas::class, 'kelompok_id');
    }
}
