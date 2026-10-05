<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KomoditasStok extends Model
{
    protected $table = 'komoditas_stok';

    protected $guarded = [];

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }
}
