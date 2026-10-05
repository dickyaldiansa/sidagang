@extends('layouts.app')
@section('title', 'Administrator')
@section('header', 'Administrator')

@push('styles')
<style>
    .admin-tabs .nav-link{border:1px solid #dfe7ef;background:#fff;color:#526170}.admin-tabs .nav-link.active{background:var(--blue);border-color:var(--blue);color:#fff}.account-form{position:sticky;top:98px}.permission-module{border:1px solid #e6edf4;border-radius:12px;padding:14px;height:100%;background:#fbfdff}.permission-item{display:flex;gap:10px;padding:8px 0;border-bottom:1px dashed #e5ebf1}.permission-item:last-child{border-bottom:0}.permission-item .form-check-input{width:1.15rem;height:1.15rem}.role-card{overflow:hidden}.role-card .card-header{background:linear-gradient(135deg,#f7fbff,#fff)}
    @media(max-width:991px){.account-form{position:static}}
    @media(max-width:767px){.account-table thead{display:none}.account-table,.account-table tbody,.account-table tr,.account-table td{display:block;width:100%}.account-table tr{padding:12px 14px;border-bottom:1px solid #e7edf3}.account-table td{border:0;padding:4px 0 4px 37%;position:relative;text-align:left!important}.account-table td:before{content:attr(data-label);position:absolute;left:0;width:34%;font-size:.68rem;text-transform:uppercase;color:#718096;font-weight:700}.account-table td[data-label="Aksi"]{padding:10px 0 0}.account-table td[data-label="Aksi"]:before{display:none}.account-table .btn{min-height:40px;min-width:44px}}
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
    <div><h1 class="section-title">Administrator SIDAGANG</h1><div class="section-subtitle">Kelola akun pengguna dan hak akses setiap peran secara terpusat.</div></div>
    <div class="alert alert-primary py-2 px-3 mb-0 small"><i class="bi bi-shield-check me-1"></i>Perubahan permission langsung berlaku pada menu dan endpoint aplikasi.</div>
</div>

<ul class="nav nav-pills admin-tabs gap-2 mb-4">
    @if(auth()->user()->hasPermission('users.manage'))<li class="nav-item"><button class="nav-link {{ $activeTab==='accounts'?'active':'' }}" data-bs-toggle="tab" data-bs-target="#accounts"><i class="bi bi-people me-1"></i>Akun Pengguna</button></li>@endif
    @if(auth()->user()->hasPermission('roles.manage'))<li class="nav-item"><button class="nav-link {{ $activeTab==='access'?'active':'' }}" data-bs-toggle="tab" data-bs-target="#access"><i class="bi bi-key me-1"></i>Hak Akses</button></li>@endif
</ul>

<div class="tab-content">
@if(auth()->user()->hasPermission('users.manage'))
<div class="tab-pane fade {{ $activeTab==='accounts'?'show active':'' }}" id="accounts">
    @php($edit = $editing)
    <div class="row g-4">
        <div class="col-lg-4"><div class="card account-form"><div class="card-header"><strong>{{ $edit?'Edit Akun':'Buat Akun Baru' }}</strong><div class="text-muted small mt-1">Kata sandi minimal 8 karakter.</div></div><div class="card-body">
            <form method="post" action="{{ $edit ? route('administrator.accounts.update',$edit).'?tab=accounts&edit='.$edit->id : route('administrator.accounts.store').'?tab=accounts' }}">@csrf @if($edit) @method('PATCH') @endif
                <label class="form-label">Nama Lengkap</label><input name="name" class="form-control mb-3" value="{{ old('name',$edit?->name) }}" required autofocus>
                <label class="form-label">Username</label><input name="username" class="form-control mb-3" value="{{ old('username',$edit?->username) }}" autocomplete="off" required>
                <label class="form-label">Email</label><input type="email" name="email" class="form-control mb-3" value="{{ old('email',$edit?->email) }}">
                <label class="form-label">Peran</label><select name="role_id" id="admin-role" class="form-select mb-3" required><option value="">Pilih peran</option>@foreach($availableRoles as $role)<option value="{{ $role->id }}" data-role="{{ $role->name }}" @selected((string)old('role_id',$edit?->role_id)===(string)$role->id)>{{ $role->label }}</option>@endforeach</select>
                <div id="admin-market"><label class="form-label">Pasar</label><select name="pasar_id" class="form-select mb-3"><option value="">Pilih pasar petugas</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected((string)old('pasar_id',$edit?->pasar_id)===(string)$market->id)>{{ $market->nama }}</option>@endforeach</select></div>
                <label class="form-label">{{ $edit?'Kata Sandi Baru (opsional)':'Kata Sandi' }}</label><div class="input-group mb-3"><input type="password" id="admin-password" name="password" class="form-control" minlength="8" {{ $edit?'':'required' }} autocomplete="new-password"><button class="btn btn-outline-secondary" type="button" id="show-password" title="Tampilkan kata sandi"><i class="bi bi-eye"></i></button></div>
                <label class="form-label">Konfirmasi Kata Sandi</label><input type="password" name="password_confirmation" class="form-control mb-3" minlength="8" {{ $edit?'':'required' }} autocomplete="new-password">
                <div class="d-flex gap-2">@if($edit)<a href="{{ route('administrator.index',['tab'=>'accounts']) }}" class="btn btn-light flex-fill">Batal</a>@endif<button class="btn btn-primary flex-fill"><i class="bi bi-person-check me-1"></i>{{ $edit?'Simpan Perubahan':'Buat Akun' }}</button></div>
            </form>
        </div></div></div>
        <div class="col-lg-8"><div class="card"><div class="card-header d-flex justify-content-between align-items-center"><strong>Daftar Akun</strong><span class="badge rounded-pill bg-primary-subtle text-primary">{{ $users->count() }} akun</span></div><div class="table-responsive"><table class="table account-table mb-0"><thead><tr><th>Pengguna</th><th>Peran</th><th>Status</th><th>Login Terakhir</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @foreach($users as $user)
            @php($protected = $user->role?->name==='super_admin' && auth()->user()->role->name!=='super_admin')
            <tr><td data-label="Pengguna"><div class="fw-semibold">{{ $user->name }}</div><code>{{ $user->username }}</code>@if($user->email)<div class="text-muted small">{{ $user->email }}</div>@endif</td><td data-label="Peran"><span class="badge bg-info-subtle text-info-emphasis">{{ $user->role?->label }}</span>@if($user->pasar)<div class="text-muted small mt-1">{{ $user->pasar->nama }}</div>@endif</td><td data-label="Status"><span class="badge {{ $user->is_active?'bg-success-subtle text-success':'bg-secondary-subtle text-secondary' }}">{{ $user->is_active?'Aktif':'Nonaktif' }}</span></td><td data-label="Login Terakhir" class="small text-muted">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}</td><td data-label="Aksi" class="text-end">
                @if(!$protected)<div class="d-inline-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ route('administrator.index',['tab'=>'accounts','edit'=>$user->id]).'#accounts' }}" title="Edit"><i class="bi bi-pencil-square"></i></a>@if(!$user->is(auth()->user()))<form method="post" action="{{ route('administrator.accounts.toggle',$user).'?tab=accounts' }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary" title="{{ $user->is_active?'Nonaktifkan':'Aktifkan' }}"><i class="bi {{ $user->is_active?'bi-pause-circle':'bi-play-circle' }}"></i></button></form><form method="post" action="{{ route('administrator.accounts.destroy',$user).'?tab=accounts' }}" onsubmit="return confirm('Hapus akun {{ addslashes($user->name) }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash3"></i></button></form>@endif</div>@else<span class="text-muted small"><i class="bi bi-shield-lock"></i> Dilindungi</span>@endif
            </td></tr>
        @endforeach
        </tbody></table></div></div></div>
    </div>
</div>
@endif

@if(auth()->user()->hasPermission('roles.manage'))
<div class="tab-pane fade {{ $activeTab==='access'?'show active':'' }}" id="access">
    <div class="mb-3"><h2 class="h5 fw-bold mb-1">Matriks Hak Akses</h2><p class="text-muted small mb-0">Centang kemampuan yang diberikan kepada setiap peran. Super Administrator selalu memiliki seluruh akses.</p></div>
    <div class="row g-4">
    @foreach($roles as $role)
        @php($locked = $role->name==='super_admin' || (auth()->user()->role_id===$role->id && auth()->user()->role->name!=='super_admin'))
        <div class="col-xl-6"><div class="card role-card h-100"><div class="card-header d-flex justify-content-between align-items-start"><div><strong>{{ $role->label }}</strong><div class="text-muted small">{{ $role->users_count }} pengguna · {{ $role->permissions->count() }} permission</div></div>@if($locked)<span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-lock me-1"></i>Dilindungi</span>@endif</div><div class="card-body">
            <form method="post" action="{{ route('administrator.roles.permissions',$role).'?tab=access' }}">@csrf @method('PUT')
                <div class="row g-3">@foreach($permissions as $module=>$items)<div class="col-md-6"><div class="permission-module"><div class="fw-bold small text-primary mb-1">{{ strtoupper($module) }}</div>@foreach($items as $permission)<label class="permission-item"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($role->name==='super_admin' || $role->permissions->contains($permission->id)) @disabled($locked)><span><span class="d-block fw-semibold small">{{ $permission->label }}</span><span class="text-muted" style="font-size:.72rem">{{ $permission->description }}</span></span></label>@endforeach</div></div>@endforeach</div>
                @if(!$locked)<button class="btn btn-primary w-100 mt-3"><i class="bi bi-save me-1"></i>Simpan Hak Akses {{ $role->label }}</button>@endif
            </form>
        </div></div></div>
    @endforeach
    </div>
</div>
@endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.admin-tabs button').forEach(button=>button.addEventListener('shown.bs.tab',event=>{const tab=event.target.dataset.bsTarget.substring(1);history.replaceState(null,'',`{{ route('administrator.index') }}?tab=${tab}#${tab}`)}));
const adminRole=document.getElementById('admin-role'),adminMarket=document.getElementById('admin-market');function showAdminMarket(){const option=adminRole?.options[adminRole.selectedIndex];if(adminMarket)adminMarket.style.display=option?.dataset.role==='petugas_pasar'?'block':'none'}adminRole?.addEventListener('change',showAdminMarket);showAdminMarket();
document.getElementById('show-password')?.addEventListener('click',()=>{const input=document.getElementById('admin-password');input.type=input.type==='password'?'text':'password'});
</script>
@endpush
