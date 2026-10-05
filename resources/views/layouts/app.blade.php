<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','SIDAGANG')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{--navy:#092a4b;--navy2:#0d3b66;--blue:#176bba;--bg:#f4f7fb;--muted:#6c7a89}
        body{background:var(--bg);color:#203040;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;font-size:.92rem}
        .sidebar{position:fixed;inset:0 auto 0 0;width:264px;background:linear-gradient(160deg,var(--navy),#061b31);color:#fff;z-index:1030;overflow:auto}
        .brand{height:78px;display:flex;align-items:center;padding:0 22px;border-bottom:1px solid rgba(255,255,255,.1)}
        .brand-mark{width:42px;height:42px;border-radius:12px;background:#fff;color:var(--navy);display:grid;place-items:center;font-weight:900;font-size:19px;margin-right:12px}
        .brand small{display:block;color:#9fc2df;font-size:.68rem;letter-spacing:.05em}.sidebar-label{padding:24px 22px 7px;color:#7394af;font-size:.67rem;font-weight:700;letter-spacing:.12em}
        .nav-side{padding:0 12px}.nav-side a{color:#c6d7e6;text-decoration:none;display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:9px;margin:2px 0}.nav-side a:hover,.nav-side a.active{background:rgba(44,143,221,.22);color:#fff}.nav-side i{font-size:1.05rem;width:20px}
        .main{margin-left:264px;min-height:100vh}.topbar{height:78px;background:#fff;border-bottom:1px solid #e7edf3;display:flex;align-items:center;justify-content:space-between;padding:0 30px;position:sticky;top:0;z-index:1020}.page{padding:27px 30px 50px}
        .card{border:1px solid #e7edf3;border-radius:14px;box-shadow:0 3px 16px rgba(9,42,75,.045)}.card-header{background:#fff;border-bottom:1px solid #eef2f6;padding:17px 20px;border-radius:14px 14px 0 0!important}
        .kpi{height:100%;padding:18px;position:relative;overflow:hidden}.kpi .icon{width:44px;height:44px;display:grid;place-items:center;border-radius:12px;font-size:1.3rem}.kpi h3{font-weight:750;margin:12px 0 2px}.kpi p{margin:0;color:var(--muted);font-size:.8rem}.kpi:after{content:"";position:absolute;width:70px;height:70px;border-radius:50%;right:-25px;top:-25px;background:currentColor;opacity:.045}
        .table{--bs-table-bg:transparent}.table thead th{font-size:.72rem;text-transform:uppercase;letter-spacing:.035em;color:#718096;background:#f8fafc;border-bottom-width:1px;white-space:nowrap}.table td{vertical-align:middle}.badge-status{font-size:.69rem;padding:.42rem .6rem;border-radius:20px}.sticky-table thead{position:sticky;top:0;z-index:2}.sticky-col{position:sticky;left:0;background:inherit!important;z-index:1}
        .btn{border-radius:9px}.form-control,.form-select{border-radius:9px;border-color:#dfe7ef}.form-label{font-size:.8rem;font-weight:650;color:#526170}.text-rise{color:#d83b4c}.text-fall{color:#15925d}.text-stable{color:#66809a}.section-title{font-weight:750;font-size:1.22rem;margin:0}.section-subtitle{color:#718096;font-size:.83rem;margin-top:3px}
        @media(max-width:991px){.sidebar{transform:translateX(-100%);transition:.2s}.sidebar.show{transform:none}.main{margin-left:0}.page{padding:20px 15px}.topbar{padding:0 16px}.sidebar-backdrop{display:none;position:fixed;inset:0;background:#00172b88;z-index:1025}.sidebar-backdrop.show{display:block}}
        @media(max-width:767px){body{font-size:.9rem}.topbar{height:64px}.topbar .btn{min-height:42px}.page{padding:16px 12px 35px}.card{border-radius:13px}.card-header{padding:14px}.form-control,.form-select{min-height:46px;font-size:16px}.btn{min-height:44px}.section-title{font-size:1.12rem}.section-subtitle{line-height:1.4}.mobile-action-space{padding-bottom:92px}}
        @media print{.sidebar,.topbar,.no-print{display:none!important}.main{margin:0}.page{padding:0}.card{box-shadow:none;border:0}}
    </style>
    @stack('styles')
</head>
<body>
<div class="sidebar-backdrop" id="backdrop"></div>
<aside class="sidebar" id="sidebar">
    <a class="brand text-white text-decoration-none" href="{{ route('home') }}"><div class="brand-mark"><img src="{{ asset('images/logo-pemko-batam.png') }}" alt="Lambang Kota Batam" style="width:36px;height:36px;object-fit:contain"></div><div><strong>SIDAGANG</strong><small>DISPERINDAG KOTA BATAM</small></div></a>
    <div class="sidebar-label">IKHTISAR</div><nav class="nav-side"><a href="{{ route('home') }}"><i class="bi bi-house"></i> Halaman Home</a>@if(auth()->user()->hasPermission('dashboard.view'))<a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>@endif<a class="{{ request()->routeIs('guide.*')?'active':'' }}" href="{{ route('guide.index') }}"><i class="bi bi-journal-text"></i> Panduan Pengguna</a></nav>
    @if(auth()->user()->hasPermission('prices.view'))
    <div class="sidebar-label">HARGA BAHAN POKOK</div><nav class="nav-side">
        @if(auth()->user()->hasPermission('prices.create'))
        <a class="{{ request()->routeIs('prices.create')?'active':'' }}" href="{{ route('prices.create') }}"><i class="bi bi-pencil-square"></i> Input Harga</a>
        @endif
        <a class="{{ request()->routeIs('prices.index','prices.show')?'active':'' }}" href="{{ route('prices.index') }}"><i class="bi bi-graph-up"></i> Monitoring Harga</a>
    </nav>
    @endif
    @if(auth()->user()->hasPermission('stocks.view'))
    <div class="sidebar-label">STOK BAHAN POKOK</div><nav class="nav-side">
        @if(auth()->user()->hasPermission('stocks.create'))
        <a class="{{ request()->routeIs('stocks.create')?'active':'' }}" href="{{ route('stocks.create') }}"><i class="bi bi-box-seam"></i> Input Stok</a>
        @endif
        <a class="{{ request()->routeIs('stocks.index','stocks.show')?'active':'' }}" href="{{ route('stocks.index') }}"><i class="bi bi-bar-chart"></i> Monitoring Stok</a>
    </nav>
    @endif
    @if(auth()->user()->hasPermission('reports.view'))
    <div class="sidebar-label">LAPORAN</div><nav class="nav-side"><a class="{{ request()->routeIs('reports.price-movement')?'active':'' }}" href="{{ route('reports.price-movement') }}"><i class="bi bi-graph-up-arrow"></i> Pergerakan Harga</a><a class="{{ request()->routeIs('reports.monthly')||request()->routeIs('reports.csv')?'active':'' }}" href="{{ route('reports.monthly') }}"><i class="bi bi-file-earmark-bar-graph"></i> Rekap Bulanan Harga</a><a class="{{ request()->routeIs('reports.stocks*')?'active':'' }}" href="{{ route('reports.stocks') }}"><i class="bi bi-boxes"></i> Rekap Bulanan Stok</a></nav>
    @endif
    @if(auth()->user()->hasPermission('master.manage') || auth()->user()->hasPermission('users.manage') || auth()->user()->hasPermission('roles.manage'))
    <div class="sidebar-label">ADMINISTRASI</div><nav class="nav-side">
        @if(auth()->user()->hasPermission('master.manage'))<a class="{{ request()->routeIs('master.*')?'active':'' }}" href="{{ route('master.index') }}"><i class="bi bi-database-gear"></i> Master Data</a>@endif
        @if(auth()->user()->hasPermission('users.manage') || auth()->user()->hasPermission('roles.manage'))<a class="{{ request()->routeIs('administrator.*')?'active':'' }}" href="{{ route('administrator.index') }}"><i class="bi bi-shield-lock"></i> Administrator</a>@endif
    </nav>
    @endif
</aside>
<main class="main">
    <header class="topbar"><div class="d-flex align-items-center gap-3"><button class="btn btn-light d-lg-none" id="menu"><i class="bi bi-list fs-5"></i></button><div><strong>@yield('header','SIDAGANG')</strong><div class="text-muted small">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</div></div></div>
        <div class="dropdown"><button class="btn bg-white d-flex align-items-center gap-2" data-bs-toggle="dropdown"><span class="rounded-circle bg-primary-subtle text-primary d-grid" style="width:36px;height:36px;place-items:center"><i class="bi bi-person"></i></span><span class="d-none d-sm-block text-start"><strong class="d-block small">{{ auth()->user()->name }}</strong><span class="text-muted" style="font-size:.7rem">{{ auth()->user()->role->label }}</span></span><i class="bi bi-chevron-down small"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><form method="post" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button></form></li></ul></div>
    </header>
    <div class="page">
        @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        @if($errors->any())<div class="alert alert-danger"><strong>Periksa kembali isian:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>const side=document.getElementById('sidebar'),back=document.getElementById('backdrop');document.getElementById('menu')?.addEventListener('click',()=>{side.classList.add('show');back.classList.add('show')});back.addEventListener('click',()=>{side.classList.remove('show');back.classList.remove('show')});</script>
@stack('scripts')
</body></html>
