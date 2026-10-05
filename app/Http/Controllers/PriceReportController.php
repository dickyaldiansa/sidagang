<?php

namespace App\Http\Controllers;

use App\Models\KomoditasHarga;
use App\Models\LaporanHarga;
use App\Models\LaporanHargaDetail;
use App\Models\Pasar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = LaporanHarga::with(['pasar', 'petugas'])
            ->when($request->user()->role->name === 'petugas_pasar', fn ($query) => $query->where('pasar_id', $request->user()->pasar_id))
            ->latest('tanggal')->latest()->paginate(15);

        return view('prices.index', compact('reports'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $markets = Pasar::where('is_active', 1)->when($user->pasar_id, fn ($q) => $q->whereKey($user->pasar_id))->orderBy('urutan')->get();
        abort_unless($user->hasPermission('prices.create'), 403);
        $selectedMarket = (int) ($request->market_id ?: $user->pasar_id ?: $markets->first()?->id);
        $selectedDate = $request->date ?: now()->toDateString();
        $report = $selectedMarket && $selectedDate ? LaporanHarga::with('details')->where('pasar_id', $selectedMarket)->whereDate('tanggal', $selectedDate)->first() : null;
        $previous = $selectedMarket && $selectedDate
            ? LaporanHarga::with('details')->where('pasar_id', $selectedMarket)->where('status', 'verified')->whereDate('tanggal', '<', $selectedDate)->latest('tanggal')->first()
            : null;
        $commodities = KomoditasHarga::with(['satuan', 'kelompok'])->where('is_active', 1)->orderBy('urutan')->get();

        return view('prices.form', compact('markets', 'selectedMarket', 'selectedDate', 'report', 'previous', 'commodities'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('prices.create'), 403);
        $data = $request->validate(['tanggal' => 'required|date', 'pasar_id' => 'required|exists:pasar,id', 'harga' => 'array', 'harga.*' => 'nullable|numeric|min:0', 'status_data' => 'array', 'status_data.*' => 'nullable|in:available,unavailable,not_surveyed', 'intent' => 'required|in:draft,submit']);
        if ($request->user()->pasar_id && $request->user()->pasar_id != $data['pasar_id']) {
            abort(403);
        }
        $result = DB::transaction(function () use ($data, $request) {
            $report = LaporanHarga::whereDate('tanggal', $data['tanggal'])
                ->where('pasar_id', $data['pasar_id'])
                ->lockForUpdate()
                ->first();

            if (! $report) {
                $report = LaporanHarga::create([
                    'tanggal' => $data['tanggal'],
                    'pasar_id' => $data['pasar_id'],
                    'petugas_id' => $request->user()->id,
                    'status' => 'draft',
                ]);
            }
            if (in_array($report->status, ['submitted', 'verified'])) {
                return ['report' => $report, 'already_submitted' => true];
            }
            foreach (KomoditasHarga::where('is_active', 1)->pluck('id') as $id) {
                $status = $data['status_data'][$id] ?? 'available';
                $harga = $status === 'available' ? ($data['harga'][$id] ?? null) : null;
                LaporanHargaDetail::updateOrCreate(['laporan_harga_id' => $report->id, 'komoditas_harga_id' => $id], ['harga' => $harga, 'status_data' => $status]);
            }
            $report->update($data['intent'] === 'submit' ? ['status' => 'submitted', 'submitted_at' => now(), 'catatan_verifikasi' => null] : ['status' => 'draft']);

            return ['report' => $report, 'already_submitted' => false];
        });

        if ($result['already_submitted']) {
            return redirect()->route('prices.show', $result['report'])->with('success', 'Laporan ini sudah pernah dikirim dan tidak diproses ulang.');
        }

        return redirect()->route('prices.index')->with('success', $data['intent'] === 'submit' ? 'Laporan berhasil dikirim untuk verifikasi.' : 'Draft berhasil disimpan.');
    }

    public function show(LaporanHarga $report)
    {
        if (request()->user()->role->name === 'petugas_pasar') {
            abort_unless($report->pasar_id === request()->user()->pasar_id, 403);
        }
        $report->load(['pasar', 'petugas', 'details.komoditas.satuan']);
        $previous = LaporanHarga::with('details')->where('pasar_id', $report->pasar_id)->where('status', 'verified')->where('tanggal', '<', $report->tanggal)->latest('tanggal')->first();

        return view('prices.show', compact('report', 'previous'));
    }

    public function verify(Request $request, LaporanHarga $report)
    {
        abort_unless($request->user()->hasPermission('prices.verify'), 403);
        $data = $request->validate(['action' => 'required|in:verify,reject', 'note' => 'nullable|string|max:2000|required_if:action,reject']);
        $report->update($data['action'] === 'verify' ? ['status' => 'verified', 'verified_by' => $request->user()->id, 'verified_at' => now(), 'catatan_verifikasi' => $data['note'] ?? null] : ['status' => 'rejected', 'rejected_by' => $request->user()->id, 'rejected_at' => now(), 'catatan_verifikasi' => $data['note']]);

        return redirect()->route('prices.index')->with('success', $data['action'] === 'verify' ? 'Laporan terverifikasi.' : 'Laporan ditolak dan dikembalikan kepada petugas.');
    }
}
