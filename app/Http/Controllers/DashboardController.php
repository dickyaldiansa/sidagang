<?php

namespace App\Http\Controllers;

use App\Models\KomoditasHarga;
use App\Models\KomoditasStok;
use App\Models\LaporanHarga;
use App\Models\LaporanHargaDetail;
use App\Models\LaporanStok;
use App\Models\LaporanStokDetail;
use App\Models\Pasar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role->name === 'petugas_stok' && $request->user()->hasPermission('stocks.view')) {
            return redirect()->route('stocks.index');
        }
        if ($request->user()->role->name === 'petugas_pasar' && $request->user()->hasPermission('prices.view')) {
            return redirect()->route('prices.index');
        }

        $markets = Pasar::where('is_active', true)->orderBy('urutan')->get();
        $commodities = KomoditasHarga::with('satuan')->where('is_active', true)->orderBy('urutan')->get();
        $priceDates = LaporanHarga::where('status', 'verified')->selectRaw('DATE(tanggal) as survey_date')->distinct()->orderByDesc('survey_date')->pluck('survey_date');
        $selectedDate = $request->filled('date_to') && $priceDates->contains($request->date_to) ? $request->date_to : $priceDates->first();
        $previousDate = $request->filled('date_from') && $priceDates->contains($request->date_from) ? $request->date_from : ($priceDates->take(7)->last() ?? $selectedDate);
        if ($previousDate && $selectedDate && $previousDate > $selectedDate) {
            [$previousDate, $selectedDate] = [$selectedDate, $previousDate];
        }
        $selectedCommodity = $commodities->firstWhere('id', $request->integer('commodity_id')) ?? $commodities->first();

        $currentAverages = $this->averages($selectedDate);
        $previousAverages = $this->averages($previousDate);
        $currentMarketPrices = $selectedDate && $selectedCommodity ? LaporanHargaDetail::query()
            ->join('laporan_harga', 'laporan_harga.id', '=', 'laporan_harga_detail.laporan_harga_id')
            ->where('laporan_harga.status', 'verified')
            ->whereDate('laporan_harga.tanggal', $selectedDate)
            ->where('komoditas_harga_id', $selectedCommodity->id)
            ->whereNotNull('harga')
            ->pluck('harga', 'pasar_id') : collect();
        $previousMarketPrices = $previousDate && $selectedCommodity ? LaporanHargaDetail::query()
            ->join('laporan_harga', 'laporan_harga.id', '=', 'laporan_harga_detail.laporan_harga_id')
            ->where('laporan_harga.status', 'verified')
            ->whereDate('laporan_harga.tanggal', $previousDate)
            ->where('komoditas_harga_id', $selectedCommodity->id)
            ->whereNotNull('harga')
            ->pluck('harga', 'pasar_id') : collect();

        $marketRows = $markets->map(function ($market) use ($currentMarketPrices, $previousMarketPrices) {
            $now = isset($currentMarketPrices[$market->id]) ? (float) $currentMarketPrices[$market->id] : null;
            $before = isset($previousMarketPrices[$market->id]) ? (float) $previousMarketPrices[$market->id] : null;
            $change = $now !== null && $before > 0 ? (($now - $before) / $before * 100) : null;

            return compact('market', 'now', 'before', 'change');
        });
        $availableMarketRows = $marketRows->whereNotNull('now');
        $priceSummary = [
            'average' => $availableMarketRows->avg('now'),
            'highest' => $availableMarketRows->sortByDesc('now')->first(),
            'lowest' => $availableMarketRows->sortBy('now')->first(),
        ];

        $trendDates = $priceDates->filter(fn ($date) => $previousDate && $selectedDate && $date >= $previousDate && $date <= $selectedDate)->reverse()->values();
        $trendData = $trendDates->isEmpty() ? collect() : LaporanHargaDetail::query()
            ->join('laporan_harga', 'laporan_harga.id', '=', 'laporan_harga_detail.laporan_harga_id')
            ->where('laporan_harga.status', 'verified')
            ->whereIn(DB::raw('DATE(laporan_harga.tanggal)'), $trendDates)
            ->whereNotNull('harga')
            ->groupBy('komoditas_harga_id', DB::raw('DATE(laporan_harga.tanggal)'))
            ->select('komoditas_harga_id', DB::raw('DATE(laporan_harga.tanggal) as survey_date'), DB::raw('AVG(harga) as average'))
            ->get()->groupBy('komoditas_harga_id');
        $priceCards = $commodities->map(function ($commodity) use ($trendDates, $trendData, $currentAverages, $previousAverages) {
            $points = collect($trendData->get($commodity->id, []))->keyBy('survey_date');
            $series = $trendDates->map(fn ($date) => isset($points[$date]) ? round((float) $points[$date]->average, 2) : null);
            $now = (float) ($currentAverages[$commodity->id] ?? 0);
            $before = (float) ($previousAverages[$commodity->id] ?? 0);
            $change = $before > 0 ? (($now - $before) / $before * 100) : 0;

            return compact('commodity', 'series', 'now', 'change');
        })->filter(fn ($row) => $row['series']->filter(fn ($value) => $value !== null)->isNotEmpty())->take(16)->values();

        $reported = $selectedDate ? LaporanHarga::whereDate('tanggal', $selectedDate)->whereIn('status', ['submitted', 'verified'])->count() : 0;

        $stockCommodities = KomoditasStok::with('satuan')->where('is_active', true)->orderBy('urutan')->get();
        $stockDates = LaporanStok::where('status', 'verified')->selectRaw('DATE(tanggal) as stock_date')->distinct()->orderByDesc('stock_date')->pluck('stock_date');
        $selectedStockDate = $request->filled('stock_date') && $stockDates->contains($request->stock_date) ? $request->stock_date : $stockDates->first();
        $selectedStockReport = $selectedStockDate ? LaporanStok::with('details.komoditas.satuan')->where('status', 'verified')->whereDate('tanggal', $selectedStockDate)->latest('id')->first() : null;
        $previousStockReport = $selectedStockDate ? LaporanStok::with('details')->where('status', 'verified')->whereDate('tanggal', '<', $selectedStockDate)->latest('tanggal')->first() : null;
        $stockRows = collect($selectedStockReport?->details)->map(function ($detail) use ($previousStockReport) {
            $before = (float) (collect($previousStockReport?->details)->firstWhere('komoditas_stok_id', $detail->komoditas_stok_id)?->jumlah ?? 0);
            $now = (float) $detail->jumlah;
            $change = $before > 0 ? (($now - $before) / $before * 100) : 0;

            return compact('detail', 'before', 'now', 'change');
        });
        $stockSummary = [
            'total' => $stockRows->count(),
            'up' => $stockRows->where('change', '>', .01)->count(),
            'down' => $stockRows->where('change', '<', -.01)->count(),
            'stable' => $stockRows->whereBetween('change', [-.01, .01])->count(),
        ];

        $stockTrendDates = $stockDates->take(7)->reverse()->values();
        $stockTrendData = $stockTrendDates->isEmpty() ? collect() : LaporanStokDetail::query()
            ->join('laporan_stok', 'laporan_stok.id', '=', 'laporan_stok_detail.laporan_stok_id')
            ->where('laporan_stok.status', 'verified')
            ->whereIn(DB::raw('DATE(laporan_stok.tanggal)'), $stockTrendDates)
            ->whereNotNull('jumlah')
            ->groupBy('komoditas_stok_id', DB::raw('DATE(laporan_stok.tanggal)'))
            ->select('komoditas_stok_id', DB::raw('DATE(laporan_stok.tanggal) as stock_date'), DB::raw('AVG(jumlah) as average'))
            ->get()->groupBy('komoditas_stok_id');
        $stockCards = $stockRows->map(function ($row) use ($stockTrendDates, $stockTrendData) {
            $commodity = $row['detail']->komoditas;
            $points = collect($stockTrendData->get($commodity->id, []))->keyBy('stock_date');
            $series = $stockTrendDates->map(fn ($date) => isset($points[$date]) ? round((float) $points[$date]->average, 3) : null);

            return array_merge($row, compact('commodity', 'series'));
        })->take(12)->values();

        return view('dashboard', compact(
            'markets', 'commodities', 'priceDates', 'selectedDate', 'selectedCommodity', 'previousDate', 'marketRows', 'priceSummary',
            'trendDates', 'priceCards', 'reported', 'stockCommodities', 'stockDates', 'selectedStockDate', 'selectedStockReport',
            'stockRows', 'stockSummary', 'stockTrendDates', 'stockCards'
        ));
    }

    private function averages($date)
    {
        return $date ? LaporanHargaDetail::query()
            ->join('laporan_harga', 'laporan_harga.id', '=', 'laporan_harga_detail.laporan_harga_id')
            ->where('laporan_harga.status', 'verified')->whereDate('laporan_harga.tanggal', $date)->whereNotNull('harga')
            ->groupBy('komoditas_harga_id')->select('komoditas_harga_id', DB::raw('AVG(harga) average'))
            ->pluck('average', 'komoditas_harga_id') : collect();
    }

    public function api()
    {
        $date = LaporanHarga::where('status', 'verified')->max('tanggal');

        return response()->json(['tanggal' => $date, 'pasar_melapor' => LaporanHarga::whereDate('tanggal', $date)->where('status', 'verified')->count(), 'pasar_total' => Pasar::where('is_active', 1)->count(), 'komoditas_harga' => KomoditasHarga::where('is_active', 1)->count(), 'komoditas_stok' => KomoditasStok::where('is_active', 1)->count()]);
    }
}
