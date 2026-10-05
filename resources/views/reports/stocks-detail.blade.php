@extends('layouts.app')
@section('title','Rekap Bulanan Stok')
@section('header','Laporan')

@push('styles')
<style>
    .stock-report-meta dt{color:#6c7d8c;font-size:.72rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase}.stock-report-meta dd{margin:0;font-weight:600}.stock-report-page+.stock-report-page{margin-top:1.5rem}@media print{.stock-report-page+.stock-report-page{break-before:page;margin-top:0}.stock-report-card{border:0!important;box-shadow:none!important}.stock-report-card .card-header{background:#fff!important}}
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4 no-print"><div><h1 class="section-title">Rekap Bulanan Stok</h1><div class="section-subtitle">Rincian stok per laporan distributor berdasarkan rentang tanggal.</div></div><div class="d-flex gap-2"><a class="btn btn-success" href="{{ route('reports.stocks.csv',['start_date'=>$startDate,'end_date'=>$endDate]) }}"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel</a><button onclick="print()" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i> Print / PDF</button></div></div>

<form class="card mb-4 no-print"><div class="card-body"><div class="row align-items-end g-3"><div class="col-md-3"><label class="form-label">Tanggal Awal</label><input type="date" name="start_date" value="{{ $startDate }}" class="form-control" required></div><div class="col-md-3"><label class="form-label">Tanggal Akhir</label><input type="date" name="end_date" value="{{ $endDate }}" class="form-control" required></div><div class="col-md-2"><button class="btn btn-primary w-100">Tampilkan</button></div></div></div></form>

@forelse($reports as $report)
    <section class="card stock-report-card stock-report-page">
        <div class="card-header text-center"><strong class="d-block">DINAS PERINDUSTRIAN DAN PERDAGANGAN</strong><strong>KOTA BATAM KEPULAUAN RIAU</strong><div class="small text-muted mt-1">Laporan Monitoring Stok Bahan Pokok Bidang Perdagangan</div><span class="badge mt-2 {{ $report->status === 'verified' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $report->status === 'verified' ? 'Terverifikasi' : 'Dikirim - belum diverifikasi' }}</span></div>
        <div class="card-body border-bottom"><dl class="row g-3 stock-report-meta mb-0"><div class="col-md-3"><dt>Tanggal Laporan</dt><dd>{{ $report->tanggal->locale('id')->translatedFormat('d F Y') }}</dd></div><div class="col-md-3"><dt>Sumber Data</dt><dd>{{ $report->sumber_data ?: '-' }}</dd></div><div class="col-md-3"><dt>Nama Distributor</dt><dd>{{ $report->nama_distributor ?: '-' }}</dd></div><div class="col-md-3"><dt>Contact Person</dt><dd>{{ $report->contact_person ?: '-' }}</dd></div><div class="col-12"><dt>Alamat</dt><dd>{{ $report->alamat ?: '-' }}</dd></div></dl></div>
        <div class="table-responsive"><table class="table table-bordered table-sm mb-0"><thead><tr><th class="text-center" style="width:55px">No</th><th>Nama Komoditas</th><th>Satuan</th><th class="text-end">Jumlah Stok</th></tr></thead><tbody>@forelse($report->details as $detail)<tr><td class="text-center">{{ $loop->iteration }}</td><td class="fw-semibold">{{ $detail->komoditas?->nama ?: '-' }}</td><td>{{ $detail->komoditas?->satuan?->kode ?: '-' }}</td><td class="text-end">{{ $detail->jumlah !== null ? number_format($detail->jumlah,3,',','.') : '-' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada rincian stok.</td></tr>@endforelse</tbody></table></div>
    </section>
@empty
    <div class="card"><div class="card-body text-center text-muted py-5">Belum ada laporan stok dikirim pada rentang tanggal ini.</div></div>
@endforelse
@endsection
