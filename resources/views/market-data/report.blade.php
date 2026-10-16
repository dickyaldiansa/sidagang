@extends('layouts.market')
@section('title','Laporan Pasar') @section('header','Laporan Bidang Pasar')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><h1 class="section-title">Laporan Data Pasar</h1><div class="section-subtitle">Daftar nama toko yang dikelompokkan berdasarkan pasar.</div></div><div class="d-flex gap-2"><a href="{{ route('market-data.index') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i> Data Pasar</a>@if(auth()->user()->hasPermission('market_data.export'))<a href="{{ route('market-data.excel', ['pasar' => $marketId]) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i> Unduh Laporan</a>@endif</div></div>

<form method="get" class="card mb-4"><div class="card-body"><div class="row g-3 align-items-end"><div class="col-md-6"><label class="form-label" for="pasar">Pasar</label><select class="form-select" id="pasar" name="pasar"><option value="">Semua Pasar</option>@foreach($marketOptions as $market)<option value="{{ $market->id }}" @selected($marketId === $market->id)>{{ $market->nama_pasar ?: $market->nama_perusahaan }}</option>@endforeach</select></div><div class="col-auto"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Tampilkan Laporan</button></div>@if($marketId)<div class="col-auto"><a href="{{ route('market-data.report') }}" class="btn btn-light border">Reset</a></div>@endif</div></div></form>

@forelse($markets as $market)
<section class="card mb-4"><div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2"><div><strong>{{ $market->nama_pasar ?: $market->nama_perusahaan }}</strong><div class="small text-muted mt-1">{{ $market->alamat }}</div></div><span class="badge text-bg-success">{{ $market->pedagang->count() }} toko</span></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>No.</th><th>Nama Toko</th><th>Alamat</th><th>Nomor Kios</th><th>Bidang Usaha</th><th>Penanggung Jawab</th><th>Telepon</th></tr></thead><tbody>@forelse($market->pedagang as $merchant)<tr><td>{{ $loop->iteration }}</td><td class="fw-semibold">{{ $merchant->nama_toko }}</td><td>{{ $merchant->alamat ?: '—' }}</td><td>{{ $merchant->nomor_kios ?: '—' }}</td><td>{{ $merchant->bidang_usaha }}</td><td>{{ $merchant->penanggung_jawab }}</td><td>{{ $merchant->no_telepon }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada data toko pada pasar ini.</td></tr>@endforelse</tbody></table></div></section>
@empty
<div class="card"><div class="card-body text-center text-muted py-5">Data pasar tidak ditemukan.</div></div>
@endforelse
@push('scripts')
@include('market-data._navigation')
@endpush
@endsection
