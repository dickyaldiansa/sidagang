<?php

namespace App\Http\Controllers;

use App\Models\LaporanHarga;
use App\Models\LaporanHargaDetail;
use App\Models\LaporanStok;
use App\Models\LaporanStokDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function latestPrice()
    {
        $date = LaporanHarga::where('status', 'verified')->max('tanggal');
        $data = LaporanHargaDetail::query()->join('laporan_harga', 'laporan_harga.id', '=', 'laporan_harga_detail.laporan_harga_id')->join('komoditas_harga', 'komoditas_harga.id', '=', 'laporan_harga_detail.komoditas_harga_id')->where('laporan_harga.status', 'verified')->whereDate('laporan_harga.tanggal', $date)->whereNotNull('harga')->groupBy('komoditas_harga.id', 'komoditas_harga.nama')->select('komoditas_harga.id', 'komoditas_harga.nama', DB::raw('AVG(harga) as rata_rata'), DB::raw('MIN(harga) as terendah'), DB::raw('MAX(harga) as tertinggi'))->get();

        return response()->json(compact('date', 'data'));
    }

    public function priceTrend(Request $request)
    {
        $q = LaporanHargaDetail::query()->join('laporan_harga', 'laporan_harga.id', '=', 'laporan_harga_detail.laporan_harga_id')->join('pasar', 'pasar.id', '=', 'laporan_harga.pasar_id')->where('laporan_harga.status', 'verified')->when($request->integer('commodity_id'), fn ($x, $id) => $x->where('komoditas_harga_id', $id))->whereNotNull('harga')->orderBy('tanggal')->select('tanggal', 'pasar.nama as pasar', 'harga');

        return response()->json($q->limit(500)->get());
    }

    public function compare(Request $request)
    {
        $date = $request->date ?: LaporanHarga::where('status', 'verified')->max('tanggal');

        return response()->json(LaporanHargaDetail::query()->join('laporan_harga', 'laporan_harga.id', '=', 'laporan_harga_detail.laporan_harga_id')->join('pasar', 'pasar.id', '=', 'laporan_harga.pasar_id')->where('laporan_harga.status', 'verified')->whereDate('tanggal', $date)->when($request->integer('commodity_id'), fn ($q, $id) => $q->where('komoditas_harga_id', $id))->select('pasar.nama as pasar', 'komoditas_harga_id', 'harga')->get());
    }

    public function latestStock()
    {
        $date = LaporanStok::where('status', 'verified')->max('tanggal');

        return response()->json(['date' => $date, 'data' => LaporanStokDetail::with('komoditas.satuan')->whereHas('laporan', fn ($q) => $q->where('status', 'verified')->whereDate('tanggal', $date))->get()]);
    }

    public function stockTrend(Request $request)
    {
        return response()->json(LaporanStokDetail::query()->join('laporan_stok', 'laporan_stok.id', '=', 'laporan_stok_detail.laporan_stok_id')->where('laporan_stok.status', 'verified')->when($request->integer('commodity_id'), fn ($q,$id) => $q->where('komoditas_stok_id',$id))->orderBy('tanggal')->select('tanggal','komoditas_stok_id','jumlah')->limit(500)->get());
    }
}
