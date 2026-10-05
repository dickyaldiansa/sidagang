@extends('layouts.app')
@section('title','Input Harga')
@section('header','Input Harga')

@push('styles')
<style>
    .entry-progress{height:7px;background:#e9eff5;border-radius:20px;overflow:hidden}.entry-progress-bar{height:100%;background:linear-gradient(90deg,#1474c4,#19a66a);border-radius:20px;transition:width .25s}.entry-context{background:linear-gradient(135deg,#0b3b65,#126da9);color:#fff;border:0}.entry-context .form-label{color:#c9e1f2}.entry-context .form-select{border:0}.mobile-actions{display:none}.commodity-number{width:32px;height:32px;border-radius:9px;background:#eaf4fc;color:#176bba;display:grid;place-items:center;font-size:.75rem;font-weight:800;flex:none}.price-field .input-group-text{font-weight:750;color:#31526d;background:#f6f9fb}.entry-row.is-complete .commodity-number{background:#dcf5e8;color:#178452}.entry-row.is-unavailable{opacity:.75}.change-pill{display:inline-flex;padding:5px 9px;border-radius:20px;background:#eef3f7;font-size:.75rem}
    @media(max-width:767px){.desktop-actions{display:none!important}.entry-heading{margin-bottom:14px}.entry-context .card-body{padding:16px}.entry-context .row{--bs-gutter-y:.75rem}.entry-search-card{border:0;background:transparent;box-shadow:none}.entry-search-card .card-header{padding:0 0 13px;border:0;background:transparent}.entry-search-card .table-responsive{overflow:visible;max-height:none!important}.entry-table thead{display:none}.entry-table,.entry-table tbody{display:block}.entry-table .entry-row{display:grid;margin-bottom:12px;padding:16px;background:#fff;border:1px solid #e2eaf1!important;border-radius:16px;box-shadow:0 4px 15px rgba(9,42,75,.055)}.entry-table .entry-row>td{display:block;border:0;padding:0;background:transparent!important}.entry-table .commodity-cell{position:static!important;display:flex!important;align-items:flex-start;gap:11px;margin-bottom:15px}.entry-table .unit-cell{display:none}.entry-table .old-cell{background:#f5f8fb!important;border-radius:11px!important;padding:10px 12px!important;margin-bottom:12px;text-align:left!important}.entry-table .old-cell:before{content:"Harga survey sebelumnya";display:block;color:#768999;font-size:.68rem;text-transform:uppercase;font-weight:750;letter-spacing:.04em;margin-bottom:2px}.entry-table .input-cell{margin-bottom:11px}.entry-table .input-cell:before{content:"Harga sekarang";display:block;color:#526779;font-size:.75rem;font-weight:750;margin-bottom:6px}.entry-table .status-cell{margin-bottom:12px}.entry-table .status-cell:before{content:"Status data";display:block;color:#526779;font-size:.75rem;font-weight:750;margin-bottom:6px}.entry-table .change-cell{text-align:left!important;border-top:1px solid #edf1f5!important;padding-top:11px!important}.entry-table .change-cell:before{content:"Perubahan: ";font-size:.75rem;color:#738696;font-weight:500}.price-field .form-control{font-size:1.15rem;font-weight:750;min-height:52px}.price-field .input-group-text{font-size:1rem}.mobile-actions{display:block;position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid #dce5ec;padding:10px 12px calc(10px + env(safe-area-inset-bottom));z-index:1022;box-shadow:0 -10px 30px rgba(7,40,69,.12)}.mobile-actions .btn{height:48px}.copy-mobile{width:48px;flex:none}.page{padding-bottom:105px}.progress-copy-row{display:flex!important}.search-legend{display:none}.filter-submit{min-height:46px}}
</style>
@endpush

@section('content')
<form method="get" class="card entry-context mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3 d-md-none"><span class="rounded-3 d-grid bg-white bg-opacity-10" style="width:42px;height:42px;place-items:center"><i class="bi bi-shop fs-5"></i></span><div><strong class="d-block">Laporan Harga Pasar</strong><small class="text-white-50">Pilih konteks survey sebelum mengisi</small></div></div>
        <div class="row align-items-end g-3">
            <div class="col-md-4"><label class="form-label">Tanggal Survey</label><input type="date" name="date" class="form-control" value="{{ $selectedDate }}" required></div>
            <div class="col-md-4"><label class="form-label">Pasar</label><select name="market_id" class="form-select" required>@foreach($markets as $m)<option value="{{ $m->id }}" @selected($selectedMarket==$m->id)>{{ $m->nama }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-light text-primary fw-bold w-100 filter-submit"><i class="bi bi-arrow-repeat me-1"></i> Tampilkan</button></div>
        </div>
    </div>
</form>

<form method="post" action="{{ route('prices.store') }}" id="priceForm" class="mobile-action-space">
    @csrf
    <input type="hidden" name="tanggal" value="{{ $selectedDate }}">
    <input type="hidden" name="pasar_id" value="{{ $selectedMarket }}">

    <div class="d-flex justify-content-between align-items-start gap-3 entry-heading mb-3">
        <div><h1 class="section-title">Isi Harga Komoditas</h1><div class="section-subtitle">{{ $markets->firstWhere('id',$selectedMarket)?->nama }} · <span class="fw-semibold">{{ ucfirst($report?->status ?? 'laporan baru') }}</span></div></div>
        <div class="desktop-actions d-flex gap-2"><button type="button" class="btn btn-outline-primary copy-all"><i class="bi bi-copy me-1"></i> Salin Harga Sebelumnya</button><button name="intent" value="draft" class="btn btn-light border"><i class="bi bi-cloud-check me-1"></i> Simpan Draft</button><button name="intent" value="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Kirim Laporan</button></div>
    </div>

    <div class="d-none d-md-flex align-items-center gap-3 mb-3 progress-copy-row"><div class="flex-grow-1"><div class="d-flex justify-content-between small mb-1"><span class="text-muted">Kelengkapan pengisian</span><strong class="progress-text">0 / {{ $commodities->count() }}</strong></div><div class="entry-progress"><div class="entry-progress-bar" style="width:0"></div></div></div></div>
    <div class="d-md-none mb-3"><div class="d-flex justify-content-between small mb-2"><span class="text-muted">Progress pengisian</span><strong class="progress-text">0 / {{ $commodities->count() }}</strong></div><div class="entry-progress"><div class="entry-progress-bar" style="width:0"></div></div></div>

    <div class="card entry-search-card">
        <div class="card-header"><div class="row align-items-center g-2"><div class="col-md-5"><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input id="commoditySearch" class="form-control" placeholder="Cari nama komoditas..." autocomplete="off"></div></div><div class="col text-end text-muted small search-legend"><span class="badge bg-warning-subtle text-warning me-1">Kuning</span> belum diisi · <span class="badge bg-info-subtle text-info">Biru</span> berubah</div></div></div>
        <div class="table-responsive" style="max-height:65vh">
            <table class="table mb-0 sticky-table entry-table" id="entryTable"><thead><tr><th class="sticky-col" style="min-width:280px">Komoditas</th><th>Satuan</th><th class="text-end">Survey Sebelumnya</th><th style="min-width:190px">Harga Sekarang</th><th style="min-width:170px">Status Data</th><th class="text-end">Perubahan</th></tr></thead><tbody>
            @foreach($commodities as $c)
                @php $old=$previous?->details->firstWhere('komoditas_harga_id',$c->id)?->harga;$detail=$report?->details->firstWhere('komoditas_harga_id',$c->id); @endphp
                <tr class="entry-row" data-name="{{ strtolower($c->nama) }}">
                    <td class="sticky-col commodity-cell"><span class="commodity-number">{{ $loop->iteration }}</span><div><strong class="d-block">{{ $c->nama }}</strong><span class="text-muted small">{{ $c->kelompok?->nama }} · {{ $c->satuan->kode }}</span></div></td>
                    <td class="unit-cell">{{ $c->satuan->kode }}</td>
                    <td class="text-end old-cell" data-value="{{ $old }}">{{ $old!==null?'Rp'.number_format($old,0,',','.'):'—' }}</td>
                    <td class="input-cell"><div class="input-group price-field"><span class="input-group-text">Rp</span><input inputmode="numeric" pattern="[0-9]*" class="form-control price-input {{ $detail?->harga===null?'bg-warning-subtle':'' }}" name="harga[{{ $c->id }}]" value="{{ $detail?->harga!==null?number_format($detail->harga,0,'',''):'' }}" data-old="{{ $old }}" placeholder="Masukkan harga" autocomplete="off"></div></td>
                    <td class="status-cell"><select class="form-select status-select" name="status_data[{{ $c->id }}]"><option value="available" @selected(($detail?->status_data??'available')==='available')>Tersedia</option><option value="unavailable" @selected($detail?->status_data==='unavailable')>Tidak tersedia</option><option value="not_surveyed" @selected($detail?->status_data==='not_surveyed')>Tidak disurvey</option></select></td>
                    <td class="text-end change-cell"><span class="change-pill">—</span></td>
                </tr>
            @endforeach
            </tbody></table>
        </div>
    </div>

    <div class="mobile-actions"><div class="d-flex gap-2"><button type="button" class="btn btn-outline-primary copy-all copy-mobile" aria-label="Salin semua harga sebelumnya"><i class="bi bi-copy"></i></button><button name="intent" value="draft" class="btn btn-light border flex-fill"><i class="bi bi-cloud-check me-1"></i> Draft</button><button name="intent" value="submit" class="btn btn-primary flex-fill"><i class="bi bi-send me-1"></i> Kirim</button></div></div>
</form>
@endsection

@push('scripts')
<script>
const inputs=[...document.querySelectorAll('.price-input')],statusSelects=[...document.querySelectorAll('.status-select')],total=inputs.length;
function updateProgress(){let done=0;inputs.forEach((input,index)=>{const status=statusSelects[index].value,row=input.closest('.entry-row'),complete=status!=='available'||input.value!=='';if(complete)done++;row.classList.toggle('is-complete',complete);row.classList.toggle('is-unavailable',status!=='available')});document.querySelectorAll('.progress-text').forEach(e=>e.textContent=`${done} / ${total}`);document.querySelectorAll('.entry-progress-bar').forEach(e=>e.style.width=`${total?done/total*100:0}%`)}
function calc(input){const value=Number(input.value.replace(/\D/g,'')),old=Number(input.dataset.old),pill=input.closest('.entry-row').querySelector('.change-pill');input.value=input.value.replace(/\D/g,'');input.classList.toggle('bg-warning-subtle',!input.value&&!input.disabled);input.classList.toggle('bg-info-subtle',!!input.value&&value!==old);if(old>0&&input.value!==''){const percent=(value-old)/old*100;pill.textContent=(percent>0?'+':'')+percent.toLocaleString('id-ID',{maximumFractionDigits:2})+'%';pill.className='change-pill fw-semibold '+(percent>0?'bg-danger-subtle text-rise':percent<0?'bg-success-subtle text-fall':'text-stable')}else{pill.textContent='—';pill.className='change-pill'}updateProgress()}
inputs.forEach((input,index)=>{input.addEventListener('input',()=>calc(input));input.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();inputs[index+1]?.focus();inputs[index+1]?.select()}});calc(input)});
document.querySelectorAll('.copy-all').forEach(button=>button.addEventListener('click',()=>{inputs.forEach(input=>{if(input.dataset.old){input.value=Math.round(input.dataset.old);calc(input)}})}));
document.querySelector('#commoditySearch').addEventListener('input',event=>document.querySelectorAll('#entryTable .entry-row').forEach(row=>row.style.display=row.dataset.name.includes(event.target.value.toLowerCase())?'':'none'));
statusSelects.forEach((select,index)=>{const input=inputs[index];const sync=()=>{input.disabled=select.value!=='available';if(input.disabled)input.value='';calc(input)};select.addEventListener('change',sync);sync()});
document.querySelector('#priceForm').addEventListener('submit',event=>{const submit=event.submitter?.value==='submit',missing=inputs.filter((input,index)=>statusSelects[index].value==='available'&&!input.value).length;if(submit&&missing&&!confirm(`Masih ada ${missing} harga yang belum diisi. Tetap kirim laporan?`))event.preventDefault()});
</script>
@endpush
