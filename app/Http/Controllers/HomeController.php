<?php

namespace App\Http\Controllers;

use App\Models\KomoditasHarga;
use App\Models\KomoditasStok;
use App\Models\LaporanHarga;
use App\Models\Pasar;

class HomeController extends Controller
{
    public function index()
    {
        $latestSurvey = LaporanHarga::where('status', 'verified')->max('tanggal');
        $stats = [
            'commodities' => KomoditasHarga::where('is_active', true)->count(),
            'markets' => Pasar::where('is_active', true)->count(),
            'stockCommodities' => KomoditasStok::where('is_active', true)->count(),
            'reports' => LaporanHarga::where('status', 'verified')->count(),
        ];

        return view('home', compact('latestSurvey', 'stats'));
    }
}
