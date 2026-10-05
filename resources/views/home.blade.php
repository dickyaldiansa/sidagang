<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIDAGANG — Sistem Informasi Digital Integrasi Perdagangan</title>
    <meta name="description" content="SIDAGANG adalah Sistem Informasi Digital Integrasi Perdagangan Dinas Perindustrian dan Perdagangan Kota Batam.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{--navy:#082d4b;--blue:#0878c9;--ink:#15334a;--muted:#61788a;--line:#d7e5ee;--soft:#f3f8fb;--green:#14875b}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--ink);background:#fff}
        .navbar{background:#ffffffed!important;border-bottom:1px solid #e6eef3;backdrop-filter:blur(15px)}.navbar-brand{color:var(--navy);font-weight:850;letter-spacing:-.02em}.brand-mark{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;color:#fff;background:linear-gradient(145deg,var(--navy),var(--blue));font-weight:900;box-shadow:0 9px 23px #0878c92b}.brand-copy small{display:block;color:#738697;font-size:.62rem;font-weight:700;letter-spacing:.08em}.nav-link{color:#50677a;font-weight:650}.btn{border-radius:11px;font-weight:750;padding:.72rem 1.15rem}
        .hero{min-height:515px;padding:130px 0 72px;display:flex;align-items:center;position:relative;overflow:hidden;background:radial-gradient(circle at 84% 12%,#bceaff 0,transparent 27%),radial-gradient(circle at 8% 90%,#fff0bd 0,transparent 24%),linear-gradient(145deg,#f8fcff 0%,#edf7fc 60%,#fffdf7 100%)}.hero:after{content:"";position:absolute;width:390px;height:390px;border-radius:50%;border:1px solid #0d73b51c;right:-130px;bottom:-240px}.eyebrow{display:inline-flex;align-items:center;gap:8px;padding:8px 13px;border-radius:50px;background:#dff2fc;color:#0869a8;font-size:.76rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.hero h1{margin:22px 0 13px;color:var(--navy);font-size:clamp(3.2rem,6.3vw,5rem);line-height:1;font-weight:900;letter-spacing:-.06em;white-space:nowrap}.hero h1 span{color:var(--blue)}.hero .tagline{max-width:690px;margin:0;font-size:clamp(1.16rem,2vw,1.48rem);line-height:1.55;color:#506b7e}.hero-orbit{position:relative;width:min(100%,350px);aspect-ratio:1;margin:auto;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#0a3558,#0876bb);box-shadow:0 28px 65px #063b5b2d;color:#fff}.hero-orbit:before,.hero-orbit:after{content:"";position:absolute;border-radius:50%;border:1px solid #ffffff34}.hero-orbit:before{inset:29px}.hero-orbit:after{inset:59px}.hero-logo{position:relative;z-index:1;text-align:center}.hero-logo i{font-size:3.4rem}.hero-logo strong{display:block;margin-top:8px;font-size:1.25rem;letter-spacing:.08em}
        .section{padding:76px 0}.section-soft{background:var(--soft)}.section-heading{max-width:720px;margin:0 auto 38px;text-align:center}.section-heading h2{margin:14px 0 10px;color:var(--navy);font-size:clamp(2rem,4vw,2.85rem);font-weight:850;letter-spacing:-.04em}.section-heading p{color:var(--muted);font-size:1.03rem}
        .gallery-card{height:100%;border:1px solid var(--line);border-radius:22px;background:#fff;position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s,border-color .2s}.gallery-media{aspect-ratio:16/9;position:relative;overflow:hidden;background:#dceaf2}.gallery-media img{width:100%;height:100%;object-fit:cover;transition:transform .35s}.gallery-card:hover .gallery-media img{transform:scale(1.035)}.gallery-overlay{position:absolute;inset:0;background:linear-gradient(180deg,transparent 50%,#062b485e)}.card-icon{position:absolute;left:20px;bottom:18px;width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#fff;color:var(--blue);font-size:1.3rem;box-shadow:0 9px 25px #092d4850}.gallery-body{padding:23px 24px 24px;display:flex;min-height:260px;flex-direction:column}.gallery-card h3{min-height:3.35rem;margin:0 0 10px;color:var(--navy);font-size:1.12rem;font-weight:850;line-height:1.42}.gallery-card p{min-height:4.65rem;margin:0 0 21px;color:var(--muted);font-size:.96rem;line-height:1.6}.gallery-card .status{margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:12px}.gallery-card.active{border-color:#76bfe9;box-shadow:0 19px 44px #0e64951b}.gallery-card.active:hover{transform:translateY(-6px);box-shadow:0 25px 55px #0e649529}.gallery-card.active .card-icon{color:#fff;background:linear-gradient(145deg,var(--navy),var(--blue))}.gallery-card.disabled .gallery-media img{filter:grayscale(.45) saturate(.65);opacity:.72}.gallery-card.disabled .gallery-overlay{background:#eaf1f54f}.gallery-card.disabled h3{color:#536a7b}.status-badge{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border-radius:30px;background:#eef2f5;color:#708493;font-size:.74rem;font-weight:800}.gallery-card.active .status-badge{background:#ddf5e9;color:var(--green)}.open-button{display:inline-flex;align-items:center;gap:7px;padding:10px 14px;border-radius:10px;color:#fff;background:var(--blue);font-size:.8rem;font-weight:850;box-shadow:0 8px 18px #0878c92b}.stretched-link:focus-visible{outline:3px solid #63b8eb;outline-offset:-4px;border-radius:20px}.portal-note{margin-top:32px;padding:20px 24px;border:1px solid #d8e8f1;border-radius:17px;background:#fff;color:#5e7688;font-size:.94rem}.portal-note i{color:var(--blue)}
        footer{padding:32px 0;border-top:1px solid #e4edf2;color:#718593;font-size:.9rem}
        @media(max-width:991px){.hero{padding:125px 0 68px}.hero h1{font-size:4rem}.hero-orbit{width:300px}.gallery-card h3{min-height:auto}.gallery-card p{min-height:auto}}
        @media(max-width:767px){.navbar .brand-copy small{display:none}.hero{min-height:auto;padding:110px 0 58px}.hero h1{font-size:clamp(2.8rem,15vw,4rem);white-space:normal}.hero .tagline{font-size:1.08rem}.section{padding:60px 0}.section-heading{margin-bottom:30px}.gallery-body{min-height:235px}.navbar .btn{padding:.55rem .75rem;font-size:.85rem}}
    </style>
</head>
<body>
@php
    $services = [
        ['bi-shop','Bidang Perdagangan','Pemantauan harga dan stok bahan pokok, verifikasi data, dashboard, serta laporan perdagangan.','images/home/bidang-perdagangan.webp',true],
        ['bi-buildings','Bidang Perindustrian','Layanan data, pembinaan, dan pengembangan sektor industri Kota Batam.','images/home/bidang-perindustrian.webp',true],
        ['bi-shield-check','Bidang Tertib Niaga dan UPTD Metrologi Legal','Layanan pengawasan tertib niaga dan kemetrologian legal.','images/home/tertib-niaga-metrologi.webp',true],
        ['bi-basket2','Bidang Pasar','Layanan pengelolaan, pembinaan, dan informasi pasar.','images/home/bidang-pasar.webp',true],
        ['bi-briefcase','Sekretariat','Layanan administrasi dan koordinasi internal dinas.','images/home/sekretariat.webp',true],
    ];
@endphp
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}"><span class="brand-mark"><img src="{{ asset('images/logo-pemko-batam.png') }}" alt="Lambang Kota Batam" style="width:36px;height:36px;object-fit:contain"></span><span class="brand-copy">SIDAGANG<small>DISPERINDAG KOTA BATAM</small></span></a>
        <button class="navbar-toggler border-0 order-lg-2" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse order-lg-2" id="nav"><ul class="navbar-nav ms-auto me-4"><li class="nav-item"><a class="nav-link" href="#beranda">Beranda</a></li><li class="nav-item"><a class="nav-link" href="#bidang">Layanan</a></li></ul></div>
    </div>
</nav>

<main>
    <section class="hero" id="beranda">
        <div class="container position-relative" style="z-index:1">
            <div class="row align-items-center g-5">
                <div class="col-lg-7"><span class="eyebrow"><i class="bi bi-buildings"></i> Pemerintah Kota Batam</span><h1>SI<span>DAGANG</span></h1><p class="tagline">Sistem Informasi Digital Integrasi Perdagangan</p><a class="btn btn-primary btn-lg mt-4" href="#bidang">Jelajahi Layanan <i class="bi bi-arrow-down ms-2"></i></a></div>
                <div class="col-lg-5 d-none d-md-block"><div class="hero-orbit"><div class="hero-logo"><i class="bi bi-grid-1x2-fill"></i><strong>TERINTEGRASI</strong><small class="text-white-50">Satu portal, berbagai layanan</small></div></div></div>
            </div>
        </div>
    </section>

    <section class="section section-soft" id="bidang">
        <div class="container">
            <div class="section-heading"><span class="eyebrow"><i class="bi bi-grid"></i> Layanan SIDAGANG</span><h2>Pilih bidang layanan</h2><p>Portal digital terintegrasi untuk mendukung pelayanan Dinas Perindustrian dan Perdagangan Kota Batam.</p></div>
            <div class="row g-4 justify-content-center">
                @foreach($services as $service)
                    <div class="col-md-6 col-lg-4">
                        <article class="gallery-card {{ $service[4] ? 'active' : 'disabled' }}" @if(!$service[4]) aria-disabled="true" @endif>
                            <div class="gallery-media"><img src="{{ asset($service[3]) }}" alt="{{ $service[1] }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}" width="960" height="540"><span class="gallery-overlay"></span><span class="card-icon"><i class="bi {{ $service[0] }}"></i></span></div>
                            <div class="gallery-body"><h3>{{ $service[1] }}</h3><p>{{ $service[2] }}</p><div class="status">@if($service[4])<span class="status-badge"><i class="bi bi-check-circle-fill"></i> Tersedia</span><span class="open-button">Buka layanan <i class="bi bi-arrow-up-right"></i></span>@else<span class="status-badge"><i class="bi bi-tools"></i> Tahap Pengembangan</span>@endif</div></div>
                            @if($service[4])<a class="stretched-link" href="{{ $service[1] === 'Bidang Pasar' ? route('market-data.index') : ($service[1] === 'Bidang Perindustrian' ? route('industry.dashboard') : (str_contains($service[1], 'Metrologi') ? route('metrology.dashboard') : ($service[1] === 'Sekretariat' ? route('secretariat.dashboard') : route('dashboard')))) }}" aria-label="Buka layanan {{ $service[1] }}"></a>@endif
                        </article>
                    </div>
                @endforeach
            </div>
            <div class="portal-note text-center"><i class="bi bi-info-circle me-2"></i>Seluruh bidang layanan SIDAGANG, termasuk <strong>Sekretariat</strong>, sudah dapat digunakan.</div>
        </div>
    </section>
</main>

<footer><div class="container d-sm-flex justify-content-between text-center text-sm-start gap-3"><span>© {{ date('Y') }} Dinas Perindustrian dan Perdagangan Kota Batam</span><span>SIDAGANG · Sistem Informasi Digital Integrasi Perdagangan</span></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
