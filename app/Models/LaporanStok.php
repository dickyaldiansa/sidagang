<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanStok extends Model
{
    protected $table = 'laporan_stok';

    protected $guarded = [];

    protected $casts = ['tanggal' => 'date', 'submitted_at' => 'datetime', 'verified_at' => 'datetime'];

    public function details()
    {
        return $this->hasMany(LaporanStokDetail::class);
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
