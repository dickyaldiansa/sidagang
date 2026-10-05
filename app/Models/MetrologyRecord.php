<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MetrologyRecord extends Model { protected $table='data_metrologi'; protected $guarded=[]; protected function casts():array{return ['tanggal'=>'date','data'=>'array','is_active'=>'boolean'];} public function pembuat(){return $this->belongsTo(User::class,'created_by');} }
