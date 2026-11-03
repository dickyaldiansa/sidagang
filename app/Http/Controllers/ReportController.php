<?php

namespace App\Http\Controllers;

use App\Models\KomoditasHarga;
use App\Models\KomoditasStok;
use App\Models\LaporanHarga;
use App\Models\LaporanStok;
use App\Models\Pasar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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

    public function excel(Request $request)
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

        $commodities = KomoditasHarga::with('satuan')->where('is_active', 1)->orderBy('urutan')->get();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);
        $period = Carbon::parse($startDate)->format('d M').' - '.Carbon::parse($endDate)->format('d M Y');

        // Sheet pertama: satu harga rata-rata per pasar untuk setiap komoditas.
        $summary = $spreadsheet->createSheet();
        $summary->setTitle('Ringkasan');
        $summaryLastColumn = Coordinate::stringFromColumnIndex(3 + max($markets->count(), 1));
        $summary->mergeCells("A1:{$summaryLastColumn}1");
        $summary->mergeCells("A2:{$summaryLastColumn}2");
        $summary->setCellValue('A1', 'REKAP HARGA BAHAN POKOK - BATAM');
        $summary->setCellValue('A2', 'Dinas Perindustrian dan Perdagangan Kota Batam | Data tersedia '.$period);
        $summary->fromArray([array_merge(['No', 'Komoditas', 'Satuan'], $markets->map(fn ($market) => preg_replace('/^Pasar\s+/i', '', $market->nama))->all())], null, 'A4');
        foreach ($commodities as $commodityIndex => $commodity) {
            $row = $commodityIndex + 5;
            $summary->fromArray([[$commodityIndex + 1, $commodity->nama, $commodity->satuan?->kode ?: '-']], null, "A{$row}");
            foreach ($markets as $marketIndex => $market) {
                $values = collect($dates)->map(fn ($date) => $matrix[$commodity->id][$market->id][$date] ?? null)->filter(fn ($value) => $value !== null);
                $summary->setCellValue(Coordinate::stringFromColumnIndex($marketIndex + 4).$row, $values->isNotEmpty() ? $values->avg() : null);
            }
        }
        $this->stylePriceWorkbookSheet($summary, $summaryLastColumn, $commodities->count() + 4, 5, 'D5', true);

        // Setiap pasar memiliki sheet sendiri dengan harga per hari, sesuai format rekap harian.
        foreach ($markets as $market) {
            $sheet = $spreadsheet->createSheet();
            $marketName = preg_replace('/^Pasar\s+/i', '', $market->nama);
            $sheet->setTitle(substr($marketName ?: 'Pasar', 0, 31));
            $lastColumn = Coordinate::stringFromColumnIndex(3 + max($dates->count(), 1));
            $sheet->mergeCells("A1:{$lastColumn}1");
            $sheet->mergeCells("A2:{$lastColumn}2");
            $sheet->setCellValue('A1', 'HARGA KOMODITAS - PASAR '.strtoupper($marketName));
            $sheet->setCellValue('A2', 'Rupiah per satuan | Data harian '.$period.' | Sel kosong = data tidak tersedia');
            $sheet->fromArray([array_merge(['No', 'Komoditas', 'Satuan'], $dates->map(fn ($date) => Carbon::parse($date)->format('d/m'))->all())], null, 'A4');
            foreach ($commodities as $commodityIndex => $commodity) {
                $row = $commodityIndex + 5;
                $sheet->fromArray([[$commodityIndex + 1, $commodity->nama, $commodity->satuan?->kode ?: '-']], null, "A{$row}");
                foreach ($dates as $dateIndex => $date) {
                    $value = $matrix[$commodity->id][$market->id][$date] ?? null;
                    if ($value !== null) {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($dateIndex + 4).$row, (float) $value);
                    }
                }
            }
            $this->stylePriceWorkbookSheet($sheet, $lastColumn, $commodities->count() + 4, 5, 'D5', false);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, "rekap-harga-{$startDate}-sd-{$endDate}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function stocks(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $reports = LaporanStok::with('details.komoditas.satuan')->whereIn('status', ['submitted', 'verified'])->whereBetween('tanggal', [$startDate, $endDate])->orderBy('tanggal')->orderBy('id')->get();
        return view('reports.stocks-detail', compact('startDate', 'endDate', 'reports'));
    }

    public function stockExcel(Request $request)
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

    private function stylePriceWorkbookSheet($sheet, string $lastColumn, int $lastRow, int $firstDataRow, string $firstPriceCell, bool $summary): void
    {
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '112E49']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => ['color' => ['rgb' => '0086A8']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '112E49']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
        $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->getColor()->setRGB('19A9E5');
        for ($row = 5; $row <= $lastRow; $row += 2) {
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C5E8F6');
        }
        $sheet->getStyle("{$firstPriceCell}:{$lastColumn}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("{$firstPriceCell}:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$firstDataRow}:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(14);
        $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);
        for ($column = 4; $column <= $lastColumnIndex; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth($summary ? 18 : 14);
        }
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getRowDimension(4)->setRowHeight(30);
        $sheet->freezePane('D5');
        $sheet->setAutoFilter("A4:{$lastColumn}{$lastRow}");
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
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
