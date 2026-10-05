<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanHarga extends Model
{
    protected $table = 'laporan_harga';

    protected $guarded = [];

    protected $casts = ['tanggal' => 'date', 'submitted_at' => 'datetime', 'verified_at' => 'datetime'];

    public function pasar()
    {
        return $this->belongsTo(Pasar::class);
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function details()
    {
        return $this->hasMany(LaporanHargaDetail::class);
    }
}
