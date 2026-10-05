<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Industri extends Model { protected $table='data_ikm'; protected $guarded=[]; protected $casts=['modal_usaha'=>'decimal:2','omset'=>'decimal:2','is_active'=>'boolean']; public function pembuat(){return $this->belongsTo(User::class,'created_by');} }
