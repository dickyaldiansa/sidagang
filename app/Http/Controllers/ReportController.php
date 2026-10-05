<?php

namespace App\Http\Controllers;

use App\Models\KomoditasHarga;
use App\Models\KomoditasStok;
use App\Models\LaporanHarga;
use App\Models\LaporanStok;
use App\Models\Pasar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function priceMovement(Request $request)
    {
        $dates = LaporanHarga::where('status', 'verified')->select('tanggal')->distinct()->orderByDesc('tanggal')->get()->pluck('tanggal');
        $latest = $dates->first();
        $previous = $dates->get(1);
        $validated = $request->validate(['report_date' => ['nullable', 'date'], 'comparison_date' => ['nullable', 'date', 'different:report_date']]);
        $reportDate = $validated['report_date'] ?? $latest?->format('Y-m-d');
        $comparisonDate = $validated['comparison_date'] ?? $previous?->format('Y-m-d');
        $reports = $reportDate && $comparisonDate ? LaporanHarga::with(['details' => fn ($q) => $q->where('status_data', 'available')->whereNotNull('harga')])->where('status', 'verified')->whereIn('tanggal', [$reportDate, $comparisonDate])->get() : collect();
        $prices = []; $marketCounts = [];
        foreach ($reports as $report) foreach ($report->details as $detail) { $date = $report->tanggal->format('Y-m-d'); $prices[$date][$detail->komoditas_harga_id][] = (float) $detail->harga; $marketCounts[$date][$detail->komoditas_harga_id][] = $report->pasar_id; }
        $rows = KomoditasHarga::with('satuan')->where('is_active', true)->orderBy('urutan')->get()->map(function ($commodity) use ($prices, $marketCounts, $reportDate, $comparisonDate) {
            $currentValues = $prices[$reportDate][$commodity->id] ?? []; $comparisonValues = $prices[$comparisonDate][$commodity->id] ?? [];
            $current = count($currentValues) ? array_sum($currentValues) / count($currentValues) : null; $comparison = count($comparisonValues) ? array_sum($comparisonValues) / count($comparisonValues) : null;
            $change = $current !== null && $comparison !== null ? $current - $comparison : null; $movement = $change === null ? 'unavailable' : ($change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'));
            return (object) ['commodity' => $commodity, 'current' => $current, 'comparison' => $comparison, 'change' => $change, 'movement' => $movement, 'percentage' => $change !== null && $comparison > 0 ? abs($change / $comparison * 100) : null, 'market_count' => count(array_unique($marketCounts[$reportDate][$commodity->id] ?? []))];
        });
        $summary = ['up' => $rows->where('movement', 'up')->count(), 'down' => $rows->where('movement', 'down')->count(), 'stable' => $rows->where('movement', 'stable')->count(), 'unavailable' => $rows->where('movement', 'unavailable')->count()];
        $reportMarketCount = $reportDate ? $reports->filter(fn ($report) => $report->tanggal->format('Y-m-d') === $reportDate)->pluck('pasar_id')->unique()->count() : 0;
        return view('reports.price-movement-fixed', compact('dates', 'reportDate', 'comparisonDate', 'rows', 'summary', 'reportMarketCount'));
    }

    public function monthly(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $markets = Pasar::where('is_active', 1)->orderBy('urutan')->get();
        $reports = LaporanHarga::with('details')->where('status', 'verified')->whereBetween('tanggal', [$startDate, $endDate])->orderBy('tanggal')->get();
        $dates = $reports->pluck('tanggal')->map->format('Y-m-d')->unique()->values();
        $commodities = KomoditasHarga::with('satuan')->where('is_active', 1)->orderBy('urutan')->get();
        $matrix = [];
        foreach ($reports as $r) {
            foreach ($r->details as $d) {
                $matrix[$d->komoditas_harga_id][$r->pasar_id][$r->tanggal->format('Y-m-d')] = $d->harga;
            }
        }

        return view('reports.monthly', compact('startDate', 'endDate', 'markets', 'dates', 'commodities', 'matrix'));
    }

    public function csv(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $markets = Pasar::where('is_active', 1)->orderBy('urutan')->get();
        $reports = LaporanHarga::with('details')->where('status', 'verified')->whereBetween('tanggal', [$startDate, $endDate])->get();
        $dates = $reports->pluck('tanggal')->map->format('Y-m-d')->unique()->sort()->values();
        $matrix = [];
        foreach ($reports as $r) {
            foreach ($r->details as $d) {
                $matrix[$d->komoditas_harga_id][$r->pasar_id][$r->tanggal->format('Y-m-d')] = $d->harga;
            }
        }

        return response()->streamDownload(function () use ($markets, $dates, $matrix) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['DINAS PERINDUSTRIAN DAN PERDAGANGAN KOTA BATAM']);
            $header = ['No', 'Komoditas', 'Satuan'];
            foreach ($markets as $m) {
                foreach ($dates as $d) {
                    $header[] = $m->nama.' '.date('d/m', strtotime($d));
                }
            }$header[] = 'Rata-rata';
            fputcsv($out, $header);
            foreach (KomoditasHarga::with('satuan')->where('is_active', 1)->orderBy('urutan')->get() as $i => $c) {
                $row = [$i + 1, $c->nama, $c->satuan->kode];
                $values = [];
                foreach ($markets as $m) {
                    foreach ($dates as $d) {
                        $v = $matrix[$c->id][$m->id][$d] ?? null;
                        $row[] = $v;
                        if ($v !== null) {
                            $values[] = (float) $v;
                        }
                    }
                }$row[] = count($values) ? array_sum($values) / count($values) : null;
                fputcsv($out, $row);
            }fclose($out);
        }, "rekap-harga-{$startDate}-sd-{$endDate}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function stocks(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $reports = LaporanStok::with('details.komoditas.satuan')->whereIn('status', ['submitted', 'verified'])->whereBetween('tanggal', [$startDate, $endDate])->orderBy('tanggal')->orderBy('id')->get();
        return view('reports.stocks-detail', compact('startDate', 'endDate', 'reports'));
    }

    public function stockCsv(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $reports = LaporanStok::with('details.komoditas.satuan')->whereIn('status', ['submitted', 'verified'])->whereBetween('tanggal', [$startDate, $endDate])->orderBy('tanggal')->orderBy('id')->get();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        if ($reports->isEmpty()) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle('Belum ada data');
            $sheet->setCellValue('A1', 'REKAP STOK BAHAN POKOK');
            $sheet->setCellValue('A3', 'Belum ada laporan stok dikirim pada rentang tanggal yang dipilih.');
        }

        foreach ($reports as $index => $report) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(substr('Stok '.($index + 1), 0, 31));
            $sheet->mergeCells('A1:D1');
            $sheet->mergeCells('A2:D2');
            $sheet->setCellValue('A1', 'DINAS PERINDUSTRIAN DAN PERDAGANGAN KOTA BATAM');
            $sheet->setCellValue('A2', 'LAPORAN MONITORING STOK BAHAN POKOK');
            $sheet->fromArray([
                ['Tanggal Laporan', $report->tanggal->format('d/m/Y'), 'Status', $report->status === 'verified' ? 'Terverifikasi' : 'Dikirim'],
                ['Sumber Data', $report->sumber_data ?: '-', 'Nama Distributor', $report->nama_distributor ?: '-'],
                ['Contact Person', $report->contact_person ?: '-', 'Alamat', $report->alamat ?: '-'],
            ], null, 'A4');
            $sheet->fromArray([['No', 'Nama Komoditas', 'Satuan', 'Jumlah Stok']], null, 'A8');

            foreach ($report->details as $detailIndex => $detail) {
                $sheet->fromArray([[
                    $detailIndex + 1,
                    $detail->komoditas?->nama ?: '-',
                    $detail->komoditas?->satuan?->kode ?: '-',
                    $detail->jumlah,
                ]], null, 'A'.($detailIndex + 9));
            }

            $lastRow = max(8, $sheet->getHighestRow());
            $sheet->getStyle('A1:D2')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0B3B65']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle('A4:D6')->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9E2EA']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getStyle('A4:A6')->getFont()->setBold(true);
            $sheet->getStyle('C4:C6')->getFont()->setBold(true);
            $sheet->getStyle('A8:D8')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '126DA9']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A8:D{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            if ($lastRow >= 9) {
                $sheet->getStyle("D9:D{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.000');
                $sheet->getStyle("A9:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D9:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            $sheet->getColumnDimension('A')->setWidth(18);
            $sheet->getColumnDimension('B')->setWidth(34);
            $sheet->getColumnDimension('C')->setWidth(18);
            $sheet->getColumnDimension('D')->setWidth(36);
            $sheet->getRowDimension(1)->setRowHeight(25);
            $sheet->getRowDimension(2)->setRowHeight(22);
            $sheet->freezePane('A9');
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, "rekap-stok-{$startDate}-sd-{$endDate}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $startDate = isset($validated['start_date']) ? Carbon::parse($validated['start_date'])->toDateString() : now()->startOfMonth()->toDateString();
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date'])->toDateString() : now()->endOfMonth()->toDateString();

        return [$startDate, $endDate];
    }
}
