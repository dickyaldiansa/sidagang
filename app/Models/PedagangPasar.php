<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedagangPasar extends Model
{
    protected $table = 'pedagang_pasar';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'verified_at' => 'datetime'];

    public function pengelola() { return $this->belongsTo(PengelolaPasar::class, 'pengelola_pasar_id'); }
    public function pembuat() { return $this->belongsTo(User::class, 'created_by'); }
    public function verifikator() { return $this->belongsTo(User::class, 'verified_by'); }
}
