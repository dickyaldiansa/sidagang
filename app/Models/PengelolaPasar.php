<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengelolaPasar extends Model
{
    protected $table = 'pengelola_pasar';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'verified_at' => 'datetime'];

    public function pedagang() { return $this->hasMany(PedagangPasar::class); }
    public function pembuat() { return $this->belongsTo(User::class, 'created_by'); }
    public function verifikator() { return $this->belongsTo(User::class, 'verified_by'); }
}
