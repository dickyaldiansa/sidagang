<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $service === 'secretariat' ? 'Masuk Sekretariat' : ($service === 'metrology' ? 'Masuk Tertib Niaga & Metrologi' : ($service === 'industry' ? 'Masuk Bidang Perindustrian' : ($service === 'market' ? 'Masuk Bidang Pasar' : 'Masuk Bidang Perdagangan'))) }} — SIDAGANG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body{min-height:100vh;background:#f2f6fa;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;color:#17324d}.login-visual{background:linear-gradient(145deg,#062846,#0a4f82);position:relative;overflow:hidden}.login-visual:after,.login-visual:before{content:"";position:absolute;border:1px solid #ffffff18;border-radius:50%}.login-visual:before{width:520px;height:520px;right:-200px;bottom:-180px}.login-visual:after{width:320px;height:320px;right:-40px;top:-110px}.logo{width:58px;height:58px;background:#fff;color:#0a3a61;border-radius:17px;display:grid;place-items:center;font-weight:900;font-size:22px}.login-card{max-width:480px;width:100%;padding:30px 0}.form-control{padding:.8rem 1rem;border-radius:10px}.btn{padding:.78rem;border-radius:10px}.demo-account{width:100%;border:1px solid #dfe8ef;background:#fff;border-radius:12px;padding:11px;text-align:left;display:flex;align-items:center;gap:10px;transition:.18s;color:#24445d}.demo-account:hover{border-color:#1672ba;background:#f2f9fe;transform:translateY(-1px);box-shadow:0 7px 18px #0c518514}.demo-icon{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;flex:none}.demo-account strong{display:block;font-size:.78rem}.demo-account small{display:block;color:#768797;font-size:.68rem}.divider{display:flex;align-items:center;gap:12px;color:#8a9aa8;font-size:.72rem;margin:20px 0}.divider:before,.divider:after{content:"";height:1px;background:#e1e8ee;flex:1}.back-home{text-decoration:none;color:#657b8d;font-size:.8rem}.back-home:hover{color:#126fb4}@media(max-width:575px){.login-card{padding:20px 0}.demo-account{padding:10px 8px}.demo-icon{width:34px;height:34px}.container-fluid>.row>.col-lg-6{padding:20px!important}}
        body.market-login .login-visual{background:linear-gradient(145deg,#123d32,#187153)}body.market-login .text-info{color:#79e3b8!important}body.market-login .btn-primary{--bs-btn-bg:#17845e;--bs-btn-border-color:#17845e;--bs-btn-hover-bg:#126d4e;--bs-btn-hover-border-color:#126d4e}body.market-login .logo{color:#176d51}
        body.industry-login{background:#f8f5ef;color:#30273f}body.industry-login .login-visual{background:linear-gradient(145deg,#241640 0%,#503078 55%,#8b4d35 100%)}body.industry-login .login-visual:before{border-radius:24px;transform:rotate(24deg);border-color:#f6b85c30;background:linear-gradient(135deg,#ffffff05,#f6b85c12)}body.industry-login .login-visual:after{border:2px dashed #f6b85c32}body.industry-login .logo{color:#673e86;border-radius:13px}body.industry-login .text-info{color:#ffc56e!important}body.industry-login .btn-primary{--bs-btn-bg:#69418b;--bs-btn-border-color:#69418b;--bs-btn-hover-bg:#51316d;--bs-btn-hover-border-color:#51316d}body.industry-login .demo-account:hover{border-color:#9166af;background:#fbf8fd;box-shadow:0 7px 18px #54327318}.industry-emblem{width:84px;height:84px;border:1px solid #ffc66f55;border-radius:22px;background:#ffffff12;display:grid;place-items:center;font-size:2.6rem;color:#ffc66f;box-shadow:0 18px 50px #160b2538}.industry-features{display:flex;gap:10px;flex-wrap:wrap}.industry-features span{padding:7px 11px;border-radius:50px;background:#ffffff12;border:1px solid #ffffff16;color:#e9def0;font-size:.75rem}.industry-rule{width:68px;height:4px;background:#f0aa50;border-radius:5px;margin:18px 0}body.metrology-login{background:#f2f8f7;color:#183e3d}body.metrology-login .login-visual{background:linear-gradient(145deg,#063b40,#08766d 60%,#166458)}body.metrology-login .logo{color:#08766d;border-radius:50%}body.metrology-login .text-info{color:#ffd071!important}body.metrology-login .btn-primary{--bs-btn-bg:#08766d;--bs-btn-border-color:#08766d}body.metrology-login .demo-account:hover{border-color:#169589;background:#f2fbf9}.metro-emblem{width:86px;height:86px;border-radius:50%;display:grid;place-items:center;background:#e5ae3d;color:#164747;font-size:2.7rem;box-shadow:0 20px 45px #032c3038}
    </style>
</head>
<body class="{{ $service === 'secretariat' ? 'secretariat-login' : ($service === 'metrology' ? 'metrology-login' : ($service === 'industry' ? 'industry-login' : ($service === 'market' ? 'market-login' : 'trade-login'))) }}">
@php
    $marketLogin = $service === 'market';
    $industryLogin = $service === 'industry';
    $metrologyLogin = $service === 'metrology';
    $secretariatLogin = $service === 'secretariat';
    $demos = $secretariatLogin
        ? [['admin','Administrator','Akses penuh Sekretariat','bi-person-gear','primary'],['petugassekretariat','Petugas Sekretariat','Administrasi dan koordinasi','bi-briefcase','info']]
        : ($metrologyLogin
        ? [['admin','Administrator','Akses penuh layanan Metrologi','bi-person-gear','primary'],['petugasmetrologi','Petugas Metrologi','Pendataan dan pengawasan','bi-rulers','success']]
        : ($industryLogin
        ? [['admin','Administrator','Akses penuh Bidang Industri','bi-person-gear','primary'],['petugasindustri','Petugas Industri','Input dan ekspor data IKM','bi-buildings','warning']]
        : ($marketLogin
        ? [['admin','Administrator','Akses penuh Bidang Pasar','bi-person-gear','primary'],['validator','Validator','Lihat data Bidang Pasar','bi-shield-check','success'],['petugasbidangpasar','Petugas Bidang Pasar','Input pengelola dan pedagang','bi-basket2','warning']]
        : [['admin','Administrator','Akses penuh sistem','bi-person-gear','primary'],['validator','Validator','Verifikasi laporan','bi-shield-check','success'],['tos3000','Petugas Pasar','Input harga Tos 3000','bi-shop','warning'],['petugasstok','Petugas Stok','Input stok mingguan','bi-box-seam','info']])));
@endphp
<div class="container-fluid min-vh-100"><div class="row min-vh-100">
    <div class="col-lg-6 login-visual d-none d-lg-flex text-white p-5 flex-column justify-content-between">
        <a href="{{ route('home') }}" class="d-flex align-items-center gap-3 text-white text-decoration-none"><div class="logo"><img src="{{ asset('images/logo-pemko-batam.png') }}" alt="Lambang Kota Batam" style="width:48px;height:48px;object-fit:contain"></div><div><h4 class="mb-0">SIDAGANG</h4><small class="text-white-50">Disperindag Kota Batam</small></div></a>
        <div class="position-relative" style="z-index:1">
            @if($metrologyLogin)<div class="metro-emblem mb-4"><i class="bi bi-rulers"></i></div>@elseif($industryLogin)<div class="industry-emblem mb-4"><i class="bi bi-buildings-fill"></i></div>@endif
            <div class="text-uppercase small mb-3 text-info fw-bold" style="letter-spacing:.15em">{{ $metrologyLogin ? 'Tertib ukur · Melindungi konsumen' : ($industryLogin ? 'Sentra industri · IKM Kota Batam' : ($marketLogin ? 'Pendataan pasar terintegrasi' : 'Satu data, keputusan lebih cepat')) }}</div>
            <h1 class="display-5 fw-bold">{{ $metrologyLogin ? 'Ukuran tepat, transaksi terpercaya.' : ($industryLogin ? 'Industri tumbuh, ekonomi daerah semakin tangguh.' : ($marketLogin ? 'Kelola pengelola dan pedagang pasar dalam satu layanan.' : 'Pantau harga dan stok bahan pokok dalam satu sistem.')) }}</h1>
            @if($metrologyLogin)<p class="lead text-white-50 mt-3">Pendataan UTTP, pengawasan BDKT, dan pelayanan tera dalam satu sistem terpercaya.</p>@elseif($industryLogin)<div class="industry-rule"></div><p class="lead text-white-50">Pusat data pelaku IKM, produk unggulan, tenaga kerja, legalitas, dan perkembangan usaha.</p><div class="industry-features mt-4"><span><i class="bi bi-gear-wide-connected me-1"></i> Data IKM</span><span><i class="bi bi-box-seam me-1"></i> Produk Industri</span><span><i class="bi bi-people me-1"></i> Tenaga Kerja</span></div>@else<p class="lead text-white-50 mt-3">{{ $marketLogin ? 'Data tenant terhubung, proses verifikasi jelas, dan rekap siap digunakan.' : 'Data pasar terintegrasi, verifikasi transparan, dan laporan siap pakai.' }}</p>@endif
        </div>
        <small class="text-white-50">Pemerintah Kota Batam · {{ date('Y') }}</small>
    </div>
    <div class="col-lg-6 d-flex align-items-center justify-content-center p-4"><div class="login-card">
        <div class="d-flex justify-content-between align-items-center mb-4"><div class="d-lg-none logo bg-primary text-white"><img src="{{ asset('images/logo-pemko-batam.png') }}" alt="Lambang Kota Batam" style="width:48px;height:48px;object-fit:contain"></div><a href="{{ route('home') }}" class="back-home ms-auto"><i class="bi bi-arrow-left me-1"></i> Kembali ke Home</a></div>
        <h2 class="fw-bold mb-1">{{ $secretariatLogin ? 'Masuk Sekretariat' : ($metrologyLogin ? 'Masuk Tertib Niaga & Metrologi' : ($industryLogin ? 'Masuk Bidang Perindustrian' : ($marketLogin ? 'Masuk Bidang Pasar' : 'Selamat datang'))) }}</h2><p class="text-muted mb-4">{{ $secretariatLogin ? 'Gunakan akun khusus layanan Sekretariat.' : ($metrologyLogin ? 'Gunakan akun khusus UPTD Metrologi Legal.' : ($industryLogin ? 'Gunakan akun khusus Bidang Perindustrian.' : ($marketLogin ? 'Gunakan akun khusus layanan Bidang Pasar.' : 'Masuk menggunakan akun SIDAGANG Anda.'))) }}</p>
        @if($errors->any())<div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i> {{ $errors->first() }}</div>@endif

        <div class="d-none"><div class="d-flex align-items-center justify-content-between mb-2"><strong class="small">Coba akun demo</strong><span class="badge bg-success-subtle text-success rounded-pill"><i class="bi bi-lightning-charge-fill me-1"></i>Sekali klik</span></div>
        <div class="row g-2">
            @foreach($demos as $demo)
            <div class="{{ $marketLogin || $industryLogin ? 'col-12' : 'col-6' }}"><button type="button" class="demo-account" data-demo-user="{{ $demo[0] }}"><span class="demo-icon bg-{{ $demo[4] }}-subtle text-{{ $demo[4] }}"><i class="bi {{ $demo[3] }}"></i></span><span><strong>{{ $demo[1] }}</strong><small>{{ $demo[2] }}</small></span></button></div>
            @endforeach
        </div></div>
        <form method="post" action="{{ route('login.attempt') }}" id="loginForm">
            @csrf
            <div class="mb-3"><label class="form-label fw-semibold">Username</label><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-person text-muted"></i></span><input class="form-control" id="username" name="username" value="{{ old('username') }}" placeholder="Masukkan username" autofocus required></div></div>
            <div class="mb-3"><label class="form-label fw-semibold">Kata sandi</label><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span><input type="password" class="form-control" id="password" name="password" placeholder="Masukkan kata sandi" required><button type="button" class="btn btn-outline-secondary px-3" id="togglePassword" aria-label="Tampilkan kata sandi"><i class="bi bi-eye"></i></button></div></div>
            <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label text-muted" for="remember">Ingat saya di perangkat ini</label></div>
            <button class="btn btn-primary w-100 fw-semibold" id="loginButton">Masuk <i class="bi bi-arrow-right ms-2"></i></button>
        </form>
        <p class="text-center text-muted mt-4 mb-0" style="font-size:.7rem"><i class="bi bi-info-circle me-1"></i>Akun demo hanya digunakan untuk melihat fungsi aplikasi.</p>
    </div></div>
</div></div>
<script>
const form=document.querySelector('#loginForm'),username=document.querySelector('#username'),password=document.querySelector('#password'),loginButton=document.querySelector('#loginButton');
document.querySelectorAll('[data-demo-user]').forEach(button=>button.addEventListener('click',()=>{username.value=button.dataset.demoUser;password.value='password';document.querySelectorAll('.demo-account').forEach(item=>item.disabled=true);button.innerHTML='<span class="spinner-border spinner-border-sm text-primary"></span><span><strong>Memuat akun...</strong><small>Mohon tunggu</small></span>';loginButton.disabled=true;form.requestSubmit()}));
document.querySelector('#togglePassword').addEventListener('click',event=>{const show=password.type==='password';password.type=show?'text':'password';event.currentTarget.querySelector('i').className='bi '+(show?'bi-eye-slash':'bi-eye')});
</script>
</body></html>
