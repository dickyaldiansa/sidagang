<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeSurveyHarga extends Model
{
    protected $table = 'periode_survey_harga';

    protected $guarded = [];

    protected $casts = ['tanggal' => 'date', 'deadline' => 'datetime', 'is_active' => 'boolean'];
}
