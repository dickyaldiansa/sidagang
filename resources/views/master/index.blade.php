@extends('layouts.app')
@section('title', 'Master Data')
@section('header', 'Master Data')

@push('styles')
<style>
    .master-tabs{flex-wrap:nowrap;overflow-x:auto;padding-bottom:5px;scrollbar-width:thin}.master-tabs .nav-link{white-space:nowrap;border:1px solid #e2e8f0;color:#526170;background:#fff}.master-tabs .nav-link.active{border-color:var(--blue);background:var(--blue);color:#fff}.master-form-card{position:sticky;top:98px}.master-form-card .card-header{background:linear-gradient(135deg,#f8fbff,#fff)}.master-hint{background:#edf6ff;border:1px solid #d8eaff;border-radius:10px;padding:10px 12px;font-size:.78rem;color:#41627e}
    @media(max-width:991px){.master-form-card{position:static}}
    @media(max-width:767px){.master-table thead{display:none}.master-table,.master-table tbody,.master-table tr,.master-table td{display:block;width:100%}.master-table tr{padding:12px 14px;border-bottom:1px solid #e7edf3}.master-table td{border:0;padding:5px 0 5px 38%;position:relative;text-align:left!important;min-height:30px}.master-table td:before{content:attr(data-label);position:absolute;left:0;width:34%;font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;color:#718096;font-weight:700}.master-table td[data-label="Aksi"]{padding-left:0;padding-top:10px}.master-table td[data-label="Aksi"]:before{display:none}.master-table td[data-label="Aksi"]>div{display:flex!important}.master-table td[data-label="Aksi"] .btn{min-height:40px;min-width:44px}.master-list-card{box-shadow:none}}
</style>
@endpush

@section('content')
@php
    $tabs = [
        'market' => ['Pasar', 'bi-shop'], 'unit' => ['Satuan', 'bi-rulers'],
        'group' => ['Kelompok', 'bi-collection'], 'price' => ['Komoditas Harga', 'bi-tags'],
        'stock' => ['Komoditas Stok', 'bi-box-seam'], 'period' => ['Periode Survei', 'bi-calendar3'],
    ];
    if ($canManageUsers) $tabs['officer'] = ['Petugas', 'bi-people'];
@endphp
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div><h1 class="section-title">Master Data SIDAGANG</h1><div class="section-subtitle">Tambah, lihat, edit, nonaktifkan, dan hapus referensi sistem dari satu halaman.</div></div>
    <div class="master-hint"><i class="bi bi-shield-check me-1"></i>Data yang sudah dipakai laporan tidak dapat dihapus, tetapi tetap bisa dinonaktifkan.</div>
</div>

<ul class="nav nav-pills master-tabs gap-2 mb-4" role="tablist">
    @foreach($tabs as $key => [$label, $icon])
    <li class="nav-item"><button class="nav-link {{ $activeTab === $key ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $key }}" type="button"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</button></li>
    @endforeach
</ul>

<div class="tab-content">
    <div class="tab-pane fade {{ $activeTab === 'market' ? 'show active' : '' }}" id="market">
        @php($edit = $editing instanceof \App\Models\Pasar ? $editing : null)
        <div class="row g-4"><div class="col-lg-4"><div class="card master-form-card"><div class="card-header"><strong>{{ $edit ? 'Edit Pasar' : 'Tambah Pasar' }}</strong></div><div class="card-body">
            <form method="post" action="{{ $edit ? route('master.update', ['market', $edit->id]).'?tab=market&edit=market:'.$edit->id : route('master.store', 'market').'?tab=market' }}">@csrf @if($edit) @method('PATCH') @endif
                <label class="form-label">Kode</label><input name="kode" class="form-control mb-3" value="{{ old('kode', $edit?->kode) }}" maxlength="20" required>
                <label class="form-label">Nama Pasar</label><input name="nama" class="form-control mb-3" value="{{ old('nama', $edit?->nama) }}" required>
                <label class="form-label">Kecamatan</label><input name="kecamatan" class="form-control mb-3" value="{{ old('kecamatan', $edit?->kecamatan) }}">
                <label class="form-label">Alamat</label><textarea name="alamat" class="form-control mb-3" rows="3">{{ old('alamat', $edit?->alamat) }}</textarea>
                <div class="row g-2"><div class="col-6"><label class="form-label">Latitude</label><input type="number" step="any" name="latitude" class="form-control mb-3" value="{{ old('latitude', $edit?->latitude) }}"></div><div class="col-6"><label class="form-label">Longitude</label><input type="number" step="any" name="longitude" class="form-control mb-3" value="{{ old('longitude', $edit?->longitude) }}"></div></div>
                <div class="d-flex gap-2">@if($edit)<a href="{{ route('master.index',['tab'=>'market']) }}" class="btn btn-light flex-fill">Batal</a>@endif<button class="btn btn-primary flex-fill"><i class="bi bi-save me-1"></i>{{ $edit ? 'Simpan Perubahan' : 'Tambah Pasar' }}</button></div>
            </form>
        </div></div></div><div class="col-lg-8"><x-master-table :rows="$markets" type="market" /></div></div>
    </div>

    <div class="tab-pane fade {{ $activeTab === 'unit' ? 'show active' : '' }}" id="unit">
        @php($edit = $editing instanceof \App\Models\Satuan ? $editing : null)
        <div class="row g-4"><div class="col-lg-4"><div class="card master-form-card"><div class="card-header"><strong>{{ $edit ? 'Edit Satuan' : 'Tambah Satuan' }}</strong></div><div class="card-body">
            <form method="post" action="{{ $edit ? route('master.update',['unit',$edit->id]).'?tab=unit&edit=unit:'.$edit->id : route('master.store','unit').'?tab=unit' }}">@csrf @if($edit) @method('PATCH') @endif
                <label class="form-label">Kode Satuan</label><input name="kode" class="form-control mb-3" value="{{ old('kode',$edit?->kode) }}" placeholder="kg, ton, liter" required>
                <label class="form-label">Nama Satuan</label><input name="nama" class="form-control mb-3" value="{{ old('nama',$edit?->nama) }}" required>
                <div class="d-flex gap-2">@if($edit)<a href="{{ route('master.index',['tab'=>'unit']) }}" class="btn btn-light flex-fill">Batal</a>@endif<button class="btn btn-primary flex-fill"><i class="bi bi-save me-1"></i>{{ $edit?'Simpan Perubahan':'Tambah Satuan' }}</button></div>
            </form>
        </div></div></div><div class="col-lg-8"><x-master-table :rows="$units" type="unit" /></div></div>
    </div>

    <div class="tab-pane fade {{ $activeTab === 'group' ? 'show active' : '' }}" id="group">
        @php($edit = $editing instanceof \App\Models\KelompokKomoditas ? $editing : null)
        <div class="row g-4"><div class="col-lg-4"><div class="card master-form-card"><div class="card-header"><strong>{{ $edit ? 'Edit Kelompok' : 'Tambah Kelompok Komoditas' }}</strong></div><div class="card-body">
            <form method="post" action="{{ $edit ? route('master.update',['group',$edit->id]).'?tab=group&edit=group:'.$edit->id : route('master.store','group').'?tab=group' }}">@csrf @if($edit) @method('PATCH') @endif
                <label class="form-label">Kode Kelompok</label><input name="kode" class="form-control mb-3" value="{{ old('kode',$edit?->kode) }}" required>
                <label class="form-label">Nama Kelompok</label><input name="nama" class="form-control mb-3" value="{{ old('nama',$edit?->nama) }}" required>
                <div class="d-flex gap-2">@if($edit)<a href="{{ route('master.index',['tab'=>'group']) }}" class="btn btn-light flex-fill">Batal</a>@endif<button class="btn btn-primary flex-fill"><i class="bi bi-save me-1"></i>{{ $edit?'Simpan Perubahan':'Tambah Kelompok' }}</button></div>
            </form>
        </div></div></div><div class="col-lg-8"><x-master-table :rows="$groups" type="group" /></div></div>
    </div>

    @foreach(['price' => ['Komoditas Harga', $priceCommodities, \App\Models\KomoditasHarga::class], 'stock' => ['Komoditas Stok', $stockCommodities, \App\Models\KomoditasStok::class]] as $type => [$label, $rows, $modelClass])
    <div class="tab-pane fade {{ $activeTab === $type ? 'show active' : '' }}" id="{{ $type }}">
        @php($edit = $editing instanceof $modelClass ? $editing : null)
        <div class="row g-4"><div class="col-lg-4"><div class="card master-form-card"><div class="card-header"><strong>{{ $edit ? 'Edit '.$label : 'Tambah '.$label }}</strong></div><div class="card-body">
            <form method="post" action="{{ $edit ? route('master.update',[$type,$edit->id]).'?tab='.$type.'&edit='.$type.':'.$edit->id : route('master.store',$type).'?tab='.$type }}">@csrf @if($edit) @method('PATCH') @endif
                <label class="form-label">Kode</label><input name="kode" class="form-control mb-3" value="{{ old('kode',$edit?->kode) }}" required>
                <label class="form-label">Nama Komoditas</label><input name="nama" class="form-control mb-3" value="{{ old('nama',$edit?->nama) }}" required>
                @if($type === 'price')<label class="form-label">Kelompok Komoditas</label><select name="kelompok_id" class="form-select mb-3"><option value="">Tanpa kelompok</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((string)old('kelompok_id',$edit?->kelompok_id)===(string)$group->id)>{{ $group->nama }}</option>@endforeach</select><label class="form-label">HET/HA (Rp)</label><input type="number" min="0" step="1" name="het_ha" class="form-control mb-3" value="{{ old('het_ha',$edit?->het_ha) }}" placeholder="Kosongkan bila tidak ada">@endif
                <label class="form-label">Satuan</label><select name="satuan_id" class="form-select mb-3" required><option value="">Pilih satuan</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((string)old('satuan_id',$edit?->satuan_id)===(string)$unit->id)>{{ $unit->nama }} ({{ $unit->kode }})</option>@endforeach</select>
                <div class="d-flex gap-2">@if($edit)<a href="{{ route('master.index',['tab'=>$type]) }}" class="btn btn-light flex-fill">Batal</a>@endif<button class="btn btn-primary flex-fill"><i class="bi bi-save me-1"></i>{{ $edit?'Simpan Perubahan':'Tambah Komoditas' }}</button></div>
            </form>
        </div></div></div><div class="col-lg-8"><x-master-table :rows="$rows" :type="$type" /></div></div>
    </div>
    @endforeach

    <div class="tab-pane fade {{ $activeTab === 'period' ? 'show active' : '' }}" id="period">
        @php($edit = $editing instanceof \App\Models\PeriodeSurveyHarga ? $editing : null)
        <div class="row g-4"><div class="col-lg-4"><div class="card master-form-card"><div class="card-header"><strong>{{ $edit ? 'Edit Periode Survei' : 'Tambah Periode Survei' }}</strong></div><div class="card-body">
            <form method="post" action="{{ $edit ? route('master.update',['period',$edit->id]).'?tab=period&edit=period:'.$edit->id : route('master.store','period').'?tab=period' }}">@csrf @if($edit) @method('PATCH') @endif
                <label class="form-label">Tanggal Survei</label><input type="date" name="tanggal" class="form-control mb-3" value="{{ old('tanggal',$edit?->tanggal?->format('Y-m-d')) }}" required>
                <label class="form-label">Batas Waktu</label><input type="datetime-local" name="deadline" class="form-control mb-3" value="{{ old('deadline',$edit?->deadline?->format('Y-m-d\TH:i')) }}">
                <label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control mb-3" rows="2">{{ old('keterangan',$edit?->keterangan) }}</textarea>
                <div class="d-flex gap-2">@if($edit)<a href="{{ route('master.index',['tab'=>'period']) }}" class="btn btn-light flex-fill">Batal</a>@endif<button class="btn btn-primary flex-fill"><i class="bi bi-save me-1"></i>{{ $edit?'Simpan Perubahan':'Tambah Periode' }}</button></div>
            </form>
        </div></div></div><div class="col-lg-8">
            <div class="card master-list-card"><div class="card-header d-flex justify-content-between"><strong>Daftar Periode</strong><span class="badge rounded-pill bg-primary-subtle text-primary">{{ $periods->count() }} data</span></div><div class="table-responsive"><table class="table master-table mb-0"><thead><tr><th>Tanggal</th><th>Deadline / Keterangan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($periods as $period)<tr><td data-label="Tanggal" class="fw-semibold">{{ $period->tanggal->locale('id')->translatedFormat('d F Y') }}</td><td data-label="Keterangan"><div>{{ $period->deadline?->format('d/m/Y H:i') ?? 'Tanpa deadline' }}</div><small class="text-muted">{{ $period->keterangan }}</small></td><td data-label="Status"><span class="badge {{ $period->is_active?'bg-success-subtle text-success':'bg-secondary-subtle text-secondary' }}">{{ $period->is_active?'Aktif':'Nonaktif' }}</span></td><td data-label="Aksi" class="text-end"><div class="d-inline-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ route('master.index',['tab'=>'period','edit'=>'period:'.$period->id]).'#period' }}"><i class="bi bi-pencil-square"></i></a><form method="post" action="{{ route('master.toggle',['period',$period->id]).'?tab=period' }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary"><i class="bi {{ $period->is_active?'bi-pause-circle':'bi-play-circle' }}"></i></button></form><form method="post" action="{{ route('master.destroy',['period',$period->id]).'?tab=period' }}" onsubmit="return confirm('Hapus periode survei ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button></form></div></td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-5">Belum ada periode survei.</td></tr>@endforelse
            </tbody></table></div></div>
        </div></div>
    </div>

    <div class="tab-pane fade {{ $activeTab === 'officer' ? 'show active' : '' }}" id="officer">
        @php($edit = $editing instanceof \App\Models\User ? $editing : null)
        <div class="row g-4"><div class="col-lg-4"><div class="card master-form-card"><div class="card-header"><strong>{{ $edit ? 'Edit Pengguna' : 'Tambah Petugas / Pengguna' }}</strong></div><div class="card-body">
            <form method="post" action="{{ $edit ? route('master.update',['officer',$edit->id]).'?tab=officer&edit=officer:'.$edit->id : route('master.store','officer').'?tab=officer' }}">@csrf @if($edit) @method('PATCH') @endif
                <label class="form-label">Nama Lengkap</label><input name="name" class="form-control mb-3" value="{{ old('name',$edit?->name) }}" required>
                <label class="form-label">Username</label><input name="username" class="form-control mb-3" value="{{ old('username',$edit?->username) }}" autocomplete="off" required>
                <label class="form-label">Email</label><input type="email" name="email" class="form-control mb-3" value="{{ old('email',$edit?->email) }}">
                <label class="form-label">Peran</label><select name="role_id" id="officer-role" class="form-select mb-3" required><option value="">Pilih peran</option>@foreach($roles as $role)<option value="{{ $role->id }}" data-role="{{ $role->name }}" @selected((string)old('role_id',$edit?->role_id)===(string)$role->id)>{{ $role->label }}</option>@endforeach</select>
                <div id="market-field"><label class="form-label">Pasar Petugas</label><select name="pasar_id" class="form-select mb-3"><option value="">Pilih pasar</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected((string)old('pasar_id',$edit?->pasar_id)===(string)$market->id)>{{ $market->nama }}</option>@endforeach</select></div>
                <label class="form-label">{{ $edit ? 'Kata Sandi Baru (opsional)' : 'Kata Sandi' }}</label><input type="password" name="password" class="form-control mb-3" minlength="8" {{ $edit?'':'required' }} autocomplete="new-password">
                <label class="form-label">Konfirmasi Kata Sandi</label><input type="password" name="password_confirmation" class="form-control mb-3" minlength="8" {{ $edit?'':'required' }} autocomplete="new-password">
                <div class="d-flex gap-2">@if($edit)<a href="{{ route('master.index',['tab'=>'officer']) }}" class="btn btn-light flex-fill">Batal</a>@endif<button class="btn btn-primary flex-fill"><i class="bi bi-save me-1"></i>{{ $edit?'Simpan Perubahan':'Tambah Pengguna' }}</button></div>
            </form>
        </div></div></div><div class="col-lg-8"><x-master-table :rows="$officers" type="officer" /></div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.master-tabs button').forEach(button => button.addEventListener('shown.bs.tab', event => {
    const tab = event.target.dataset.bsTarget.substring(1);
    history.replaceState(null, '', `{{ route('master.index') }}?tab=${tab}#${tab}`);
}));
const roleSelect = document.getElementById('officer-role');
const marketField = document.getElementById('market-field');
function toggleMarketField(){const selected=roleSelect?.options[roleSelect.selectedIndex];if(marketField)marketField.style.display=selected?.dataset.role==='petugas_pasar'?'block':'none'}
roleSelect?.addEventListener('change',toggleMarketField);toggleMarketField();
</script>
@endpush
