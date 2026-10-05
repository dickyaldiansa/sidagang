@extends('layouts.app')
@section('title','Input Stok Mingguan')
@section('header','Input Stok')

@push('styles')
<style>
    .stock-header{background:linear-gradient(135deg,#0b3b65,#126da9);color:#fff;border:0}.stock-header .form-label{color:#cde4f4}.stock-progress{height:7px;background:#e7edf2;border-radius:20px;overflow:hidden}.stock-progress-bar{height:100%;width:0;background:linear-gradient(90deg,#1474c4,#19a66a);transition:width .2s}.stock-number{width:32px;height:32px;border-radius:9px;background:#e9f4fc;color:#176bba;display:grid;place-items:center;font-weight:800;font-size:.76rem;flex:none}.stock-row.is-complete .stock-number{background:#dcf5e8;color:#178452}.mobile-stock-actions{display:none}.stock-change-pill{display:inline-flex;padding:5px 9px;border-radius:20px;background:#eef3f7;font-size:.75rem}
    @media(max-width:767px){.desktop-stock-actions{display:none!important}.stock-header .card-body{padding:16px}.stock-entry-card{border:0;background:transparent;box-shadow:none}.stock-entry-card .card-header{padding:0 0 13px;border:0;background:transparent}.stock-entry-card .table-responsive{overflow:visible}.stock-table thead{display:none}.stock-table,.stock-table tbody{display:block}.stock-table .stock-row{display:grid;background:#fff;border:1px solid #e1e9f0!important;border-radius:16px;padding:16px;margin-bottom:12px;box-shadow:0 4px 15px rgba(9,42,75,.055)}.stock-table .stock-row>td{display:block;border:0;padding:0}.stock-table .stock-name{display:flex!important;gap:11px;align-items:flex-start;margin-bottom:14px}.stock-table .stock-unit{display:none}.stock-table .stock-old{background:#f5f8fb;border-radius:11px;padding:10px 12px!important;text-align:left!important;margin-bottom:12px}.stock-table .stock-old:before{content:"Stok minggu sebelumnya";display:block;font-size:.68rem;color:#768999;text-transform:uppercase;font-weight:750;letter-spacing:.04em;margin-bottom:2px}.stock-table .stock-input-cell:before{content:"Stok minggu ini";display:block;color:#526779;font-size:.75rem;font-weight:750;margin-bottom:6px}.stock-table .stock-input{font-size:1.12rem;font-weight:750;min-height:52px}.stock-table .stock-change{text-align:left!important;border-top:1px solid #edf1f5!important;margin-top:12px;padding-top:11px!important}.stock-table .stock-change:before{content:"Perubahan: ";font-size:.75rem;color:#738696}.mobile-stock-actions{display:block;position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid #dce5ec;padding:10px 12px calc(10px + env(safe-area-inset-bottom));z-index:1022;box-shadow:0 -10px 30px rgba(7,40,69,.12)}.mobile-stock-actions .btn{height:48px}.page{padding-bottom:105px}}
</style>
@endpush

@section('content')
<form method="post" action="{{ route('stocks.store') }}" id="stockForm" class="mobile-action-space">
    @csrf
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4"><div><h1 class="section-title">Stok Bahan Pokok Mingguan</h1><div class="section-subtitle">Isi jumlah stok tersedia. Kolom kosong tidak dihitung sebagai nol.</div></div><div class="desktop-stock-actions d-flex gap-2"><button name="intent" value="draft" class="btn btn-light border"><i class="bi bi-cloud-check me-1"></i> Simpan Draft</button><button name="intent" value="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Kirim Laporan</button></div></div>

    <div class="card stock-header mb-4"><div class="card-body"><div class="d-flex align-items-center gap-3 mb-3 d-md-none"><span class="rounded-3 d-grid bg-white bg-opacity-10" style="width:42px;height:42px;place-items:center"><i class="bi bi-box-seam fs-5"></i></span><div><strong class="d-block">Informasi Laporan</strong><small class="text-white-50">Lengkapi data laporan dan distributor</small></div></div><div class="row g-3">
        <div class="col-md-4"><label class="form-label">Tanggal Laporan</label><input type="date" name="tanggal" value="{{ old('tanggal',date('Y-m-d')) }}" class="form-control" required></div>
        <div class="col-md-8"><label class="form-label">Sumber Data</label><input name="sumber_data" class="form-control" value="{{ old('sumber_data') }}" placeholder="Contoh: Rekap distributor dan gudang"></div>
        <div class="col-md-6"><label class="form-label">Nama Distributor</label><input name="nama_distributor" class="form-control" value="{{ old('nama_distributor') }}" placeholder="Nama perusahaan atau distributor" required></div>
        <div class="col-md-6"><label class="form-label">Contact Person</label><input name="contact_person" class="form-control" value="{{ old('contact_person') }}" placeholder="Nama atau nomor kontak yang dapat dihubungi" required></div>
        <div class="col-12"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap distributor" required>{{ old('alamat') }}</textarea></div>
    </div></div></div>

    <div class="mb-3"><div class="d-flex justify-content-between small mb-2"><span class="text-muted">Progress pengisian</span><strong id="stockProgressText">0 / {{ $commodities->count() }}</strong></div><div class="stock-progress"><div class="stock-progress-bar" id="stockProgressBar"></div></div></div>

    <div class="card stock-entry-card">
        <div class="card-header"><div class="row align-items-center g-2"><div class="col-md-5"><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input id="stockCommoditySearch" class="form-control" placeholder="Cari nama komoditas..." aria-label="Cari nama komoditas" autocomplete="off"></div></div></div></div>
        <div class="table-responsive"><table class="table mb-0 stock-table" id="stockTable"><thead><tr><th>Komoditas</th><th>Satuan</th><th class="text-end">Stok Sebelumnya</th><th style="min-width:220px">Stok Minggu Ini</th><th class="text-end">Perubahan</th></tr></thead><tbody>
    @foreach($commodities as $c)
        @php $old=$previous?->details->firstWhere('komoditas_stok_id',$c->id)?->jumlah; @endphp
        <tr class="stock-row" data-name="{{ strtolower($c->nama) }}"><td class="fw-semibold stock-name"><span class="stock-number">{{ $loop->iteration }}</span><div><strong class="d-block">{{ $c->nama }}</strong><span class="text-muted small">Satuan {{ $c->satuan->kode }}</span></div></td><td class="stock-unit">{{ $c->satuan->kode }}</td><td class="text-end stock-old">{{ $old!==null?number_format($old,3,',','.').' '.$c->satuan->kode:'—' }}</td><td class="stock-input-cell"><div class="input-group"><input type="number" inputmode="decimal" step="0.001" min="0" name="jumlah[{{ $c->id }}]" class="form-control stock-input" data-old="{{ $old }}" placeholder="0"><span class="input-group-text fw-semibold">{{ $c->satuan->kode }}</span></div></td><td class="text-end stock-change"><span class="stock-change-pill">—</span></td></tr>
    @endforeach
    </tbody></table></div></div>

    <div class="mobile-stock-actions"><div class="d-flex gap-2"><button name="intent" value="draft" class="btn btn-light border flex-fill"><i class="bi bi-cloud-check me-1"></i> Simpan Draft</button><button name="intent" value="submit" class="btn btn-primary flex-fill"><i class="bi bi-send me-1"></i> Kirim</button></div></div>
</form>
@endsection

@push('scripts')
<script>
const stockInputs=[...document.querySelectorAll('.stock-input')],stockTotal=stockInputs.length;
function updateStock(input){const old=Number(input.dataset.old),value=Number(input.value),row=input.closest('.stock-row'),pill=row.querySelector('.stock-change-pill');row.classList.toggle('is-complete',input.value!=='');if(old>0&&input.value!==''){const percent=(value-old)/old*100;pill.textContent=(percent>=0?'+':'')+percent.toLocaleString('id-ID',{maximumFractionDigits:2})+'%';pill.className='stock-change-pill fw-semibold '+(percent>=0?'bg-danger-subtle text-rise':'bg-success-subtle text-fall')}else{pill.textContent='—';pill.className='stock-change-pill'}const done=stockInputs.filter(item=>item.value!=='').length;document.querySelector('#stockProgressText').textContent=`${done} / ${stockTotal}`;document.querySelector('#stockProgressBar').style.width=`${stockTotal?done/stockTotal*100:0}%`}
stockInputs.forEach((input,index)=>{input.addEventListener('input',()=>updateStock(input));input.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();stockInputs[index+1]?.focus();stockInputs[index+1]?.select()}});updateStock(input)});
document.querySelector('#stockCommoditySearch').addEventListener('input',event=>document.querySelectorAll('#stockTable .stock-row').forEach(row=>row.style.display=row.dataset.name.includes(event.target.value.toLowerCase())?'':'none'));
document.querySelector('#stockForm').addEventListener('submit',event=>{const missing=stockInputs.filter(input=>input.value==='').length;if(event.submitter?.value==='submit'&&missing&&!confirm(`Masih ada ${missing} stok yang belum diisi. Tetap kirim laporan?`))event.preventDefault()});
</script>
@endpush
