<?php

namespace App\Http\Controllers;

use App\Models\KomoditasStok;
use App\Models\LaporanStok;
use App\Models\LaporanStokDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockReportController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $reports = LaporanStok::with('petugas')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nama_distributor', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%")
                        ->orWhere('sumber_data', 'like', "%{$search}%")
                        ->orWhereHas('petugas', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('tanggal')
            ->paginate(15)
            ->withQueryString();
        $suggestions = LaporanStok::query()
            ->select('nama_distributor', 'contact_person')
            ->get()
            ->flatMap(fn (LaporanStok $report) => [$report->nama_distributor, $report->contact_person])
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->sort()
            ->values();

        return view('stocks.index', compact('reports', 'search', 'suggestions'));
    }

    public function create()
    {
        abort_unless(request()->user()->hasPermission('stocks.create'), 403);
        $commodities = KomoditasStok::with('satuan')->where('is_active', 1)->orderBy('urutan')->get();
        $previous = LaporanStok::with('details')->where('status', 'verified')->latest('tanggal')->first();

        return view('stocks.form', compact('commodities', 'previous'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('stocks.create'), 403);
        $data = $request->validate([
            'tanggal' => 'required|date',
            'sumber_data' => 'nullable|string|max:255',
            'nama_distributor' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'alamat' => 'required|string|max:2000',
            'jumlah' => 'array',
            'jumlah.*' => 'nullable|numeric|min:0',
            'intent' => 'required|in:draft,submit',
        ]);
        DB::transaction(function () use ($data, $request) {
            $date = now()->parse($data['tanggal']);
            $r = LaporanStok::create([
                'tanggal' => $date,
                'minggu_ke' => $date->weekOfMonth,
                'bulan' => $date->month,
                'tahun' => $date->year,
                'petugas_id' => $request->user()->id,
                'sumber_data' => $data['sumber_data'] ?? null,
                'nama_distributor' => $data['nama_distributor'],
                'contact_person' => $data['contact_person'],
                'alamat' => $data['alamat'],
                'status' => $data['intent'] === 'submit' ? 'submitted' : 'draft',
                'submitted_at' => $data['intent'] === 'submit' ? now() : null,
            ]);
            foreach (KomoditasStok::where('is_active', 1)->pluck('id') as $id) {
                LaporanStokDetail::create(['laporan_stok_id' => $r->id, 'komoditas_stok_id' => $id, 'jumlah' => $data['jumlah'][$id] ?? null]);
            }
        });

        return redirect()->route('stocks.index')->with('success', 'Laporan stok berhasil disimpan.');
    }

    public function show(LaporanStok $report)
    {
        $report->load(['petugas', 'details.komoditas.satuan']);
        $previous = LaporanStok::with('details')->where('status', 'verified')->where('tanggal', '<', $report->tanggal)->latest('tanggal')->first();

        return view('stocks.show', compact('report', 'previous'));
    }

    public function verify(Request $request, LaporanStok $report)
    {
        abort_unless($request->user()->hasPermission('stocks.verify'), 403);
        $d = $request->validate(['action' => 'required|in:verify,reject', 'note' => 'nullable|string|required_if:action,reject']);
        $report->update($d['action'] === 'verify' ? ['status' => 'verified', 'verified_by' => $request->user()->id, 'verified_at' => now(), 'catatan_verifikasi' => $d['note'] ?? null] : ['status' => 'rejected', 'rejected_by' => $request->user()->id, 'rejected_at' => now(), 'catatan_verifikasi' => $d['note']]);

        return redirect()->route('stocks.index')->with('success','Status verifikasi stok diperbarui.');
    }
}
