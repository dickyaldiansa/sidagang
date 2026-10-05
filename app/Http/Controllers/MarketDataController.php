<?php

namespace App\Http\Controllers;

use App\Models\PedagangPasar;
use App\Models\PengelolaPasar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MarketDataController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $status = null;
        $pengelola = PengelolaPasar::withCount(['pedagang as jumlah_tenant_aktif' => fn ($q) => $q->where('is_active', true)->where('status', 'verified')])
            ->with('pembuat')->when($search, fn ($q) => $q->where(fn ($x) => $x->where('nama_perusahaan', 'like', "%{$search}%")->orWhere('alamat', 'like', "%{$search}%")->orWhere('kontak_person', 'like', "%{$search}%")))
            ->latest()->paginate(10, ['*'], 'pengelola_page')->withQueryString();
        $pedagang = PedagangPasar::with(['pengelola', 'pembuat'])
            ->when($search, fn ($q) => $q->where(fn ($x) => $x->where('nama_toko', 'like', "%{$search}%")->orWhere('penanggung_jawab', 'like', "%{$search}%")->orWhere('no_telepon', 'like', "%{$search}%")->orWhere('bidang_usaha', 'like', "%{$search}%")))
            ->latest()->paginate(10, ['*'], 'pedagang_page')->withQueryString();
        $pengelolaOptions = PengelolaPasar::where('is_active', true)->orderBy('nama_perusahaan')->get();
        $suggestions = collect()->merge(PengelolaPasar::pluck('nama_perusahaan'))->merge(PedagangPasar::pluck('nama_toko'))->filter()->unique()->sort()->values();
        $businessTypes = DB::table('bidang_usaha_pasar')->where('is_active', true)->orderBy('nama')->pluck('nama');
        $editingManager = $request->filled('edit_manager') ? PengelolaPasar::findOrFail($request->integer('edit_manager')) : null;
        $editingMerchant = $request->filled('edit_merchant') ? PedagangPasar::findOrFail($request->integer('edit_merchant')) : null;
        foreach ([$editingManager, $editingMerchant] as $editing) {
            if ($editing) {
                abort_unless($request->user()->isAdmin() || $editing->created_by === $request->user()->id, 403);
            }
        }
        $managerEditRows = $pengelola->getCollection()->map(fn ($row) => ['id' => $row->id, 'allowed' => $request->user()->isAdmin() || $row->created_by === $request->user()->id])->values();
        $merchantEditRows = $pedagang->getCollection()->map(fn ($row) => ['id' => $row->id, 'allowed' => $request->user()->isAdmin() || $row->created_by === $request->user()->id])->values();

        return view('market-data.index', compact('pengelola', 'pedagang', 'pengelolaOptions', 'search', 'status', 'suggestions', 'businessTypes', 'editingManager', 'editingMerchant', 'managerEditRows', 'merchantEditRows'));
    }

    public function storeManager(Request $request)
    {
        $data = $request->validate(['nama_perusahaan' => 'required|string|max:255', 'alamat' => 'required|string|max:2000', 'no_telepon' => 'required|string|max:50', 'kontak_person' => 'required|string|max:255', 'kapasitas_tenant' => 'nullable|integer|min:0']);
        PengelolaPasar::create($data + ['created_by' => $request->user()->id, 'status' => 'verified', 'verified_by' => $request->user()->id, 'verified_at' => now()]);

        return back()->with('success', 'Data pengelola berhasil disimpan.');
    }

    public function updateManager(Request $request, PengelolaPasar $manager)
    {
        abort_unless($request->user()->isAdmin() || $manager->created_by === $request->user()->id, 403);
        $data = $request->validate(['nama_perusahaan' => 'required|string|max:255', 'alamat' => 'required|string|max:2000', 'no_telepon' => 'required|string|max:50', 'kontak_person' => 'required|string|max:255', 'kapasitas_tenant' => 'nullable|integer|min:0']);
        $manager->update($data + ['status' => 'verified', 'verified_by' => $request->user()->id, 'verified_at' => now()]);

        return redirect()->route('market-data.index')->with('success', 'Data pengelola berhasil diperbarui.');
    }

    public function storeMerchant(Request $request)
    {
        $data = $this->merchantData($request);
        PedagangPasar::create($data + ['created_by' => $request->user()->id, 'status' => 'verified', 'verified_by' => $request->user()->id, 'verified_at' => now()]);

        return back()->with('success', 'Data pedagang berhasil disimpan.');
    }

    public function updateMerchant(Request $request, PedagangPasar $merchant)
    {
        abort_unless($request->user()->isAdmin() || $merchant->created_by === $request->user()->id, 403);
        $merchant->update($this->merchantData($request, $merchant->id) + ['status' => 'verified', 'verified_by' => $request->user()->id, 'verified_at' => now()]);

        return redirect()->route('market-data.index')->with('success', 'Data pedagang berhasil diperbarui.');
    }

    public function verify(Request $request, string $type, int $id)
    {
        $data = $request->validate(['action' => 'required|in:verify,reject', 'note' => 'nullable|string|max:2000|required_if:action,reject']);
        $model = $type === 'manager' ? PengelolaPasar::findOrFail($id) : PedagangPasar::findOrFail($id);
        $model->update(['status' => $data['action'] === 'verify' ? 'verified' : 'rejected', 'verified_by' => $request->user()->id, 'verified_at' => now(), 'catatan_verifikasi' => $data['note'] ?? null]);

        return back()->with('success', 'Status data Bidang Pasar berhasil diperbarui.');
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $model = $type === 'manager' ? PengelolaPasar::findOrFail($id) : PedagangPasar::findOrFail($id);
        abort_unless($request->user()->isAdmin() || ($model->created_by === $request->user()->id && $model->status !== 'verified'), 403);
        $model->delete();

        return back()->with('success', 'Data Bidang Pasar berhasil dihapus.');
    }

    public function excel()
    {
        $spreadsheet = new Spreadsheet;
        $managerSheet = $spreadsheet->getActiveSheet();
        $managerSheet->setTitle('Data Pengelola');
        $managers = PengelolaPasar::withCount(['pedagang as tenant_aktif' => fn ($q) => $q->where('is_active', true)->where('status', 'verified')])->orderBy('nama_perusahaan')->get();
        $managerSheet->fromArray(['DATA PENGELOLA PASAR'], null, 'A1');
        $managerSheet->fromArray(['No', 'Nama Perusahaan', 'Alamat', 'No Telepon', 'Kontak Person', 'Kapasitas Tenant', 'Tenant Aktif'], null, 'A3');
        foreach ($managers as $index => $row) {
            $excelRow = $index + 4;
            $managerSheet->fromArray([$index + 1, $row->nama_perusahaan, $row->alamat, null, $row->kontak_person, $row->kapasitas_tenant, $row->tenant_aktif], null, "A{$excelRow}");
            $managerSheet->setCellValueExplicit("D{$excelRow}", (string) $row->no_telepon, DataType::TYPE_STRING);
        }
        $this->styleSheet($managerSheet, 'G', $managers->count() + 3);

        $merchantSheet = $spreadsheet->createSheet();
        $merchantSheet->setTitle('Data Pedagang');
        $merchants = PedagangPasar::with('pengelola')->orderBy('nama_toko')->get();
        $merchantSheet->fromArray(['DATA PEDAGANG PASAR'], null, 'A1');
        $merchantSheet->fromArray(['No', 'Pengelola', 'Nama Toko', 'Penanggung Jawab', 'No Telepon', 'Bidang Usaha', 'Nomor Kios', 'Alamat'], null, 'A3');
        foreach ($merchants as $index => $row) {
            $excelRow = $index + 4;
            $merchantSheet->fromArray([$index + 1, $row->pengelola->nama_perusahaan, $row->nama_toko, $row->penanggung_jawab, null, $row->bidang_usaha, $row->nomor_kios, $row->alamat], null, "A{$excelRow}");
            $merchantSheet->setCellValueExplicit("E{$excelRow}", (string) $row->no_telepon, DataType::TYPE_STRING);
        }
        $this->styleSheet($merchantSheet, 'H', $merchants->count() + 3);
        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'data-bidang-pasar-'.now()->format('Y-m-d').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function styleSheet($sheet, string $lastColumn, int $lastRow): void
    {
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray(['font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '176D51']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]]);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '24966D']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]]);
        if ($lastRow >= 4) {
            $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D7E5DF']]], 'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true]]);
            $sheet->setAutoFilter("A3:{$lastColumn}{$lastRow}");
        }
        $sheet->getStyle("A3:{$lastColumn}3")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D7E5DF');
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->freezePane('A4');
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.4)->setBottom(0.5)->setLeft(0.4);
    }

    private function merchantData(Request $request, ?int $id = null): array
    {
        return $request->validate(['pengelola_pasar_id' => 'required|exists:pengelola_pasar,id', 'nama_toko' => ['required', 'string', 'max:255', Rule::unique('pedagang_pasar')->where(fn ($q) => $q->where('pengelola_pasar_id', $request->pengelola_pasar_id)->where('no_telepon', $request->no_telepon))->ignore($id)], 'penanggung_jawab' => 'required|string|max:255', 'no_telepon' => 'required|string|max:50', 'bidang_usaha' => 'required|string|max:255', 'nomor_kios' => 'nullable|string|max:100', 'alamat' => 'nullable|string|max:2000']);
    }
}
