<script>
document.querySelector('.topbar .dropdown-menu li:first-child')?.remove();
const marketNavs=document.querySelectorAll('.sidebar .nav-side');
if(marketNavs[0])marketNavs[0].innerHTML='<a href="{{ route('market-data.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a><a href="{{ route('market-data.dashboard') }}"><i class="bi bi-database"></i> Data Pasar</a>';
if(marketNavs[1])marketNavs[1].innerHTML='<a href="{{ route('market-data.managers.index') }}"><i class="bi bi-buildings"></i> Data Pengelola</a><a href="{{ route('market-data.merchants.index') }}"><i class="bi bi-shop"></i> Data Pedagang</a><a href="{{ route('market-data.report') }}"><i class="bi bi-clipboard-data"></i> Laporan Pasar</a>';
@if(auth()->user()->hasPermission('market_data.manage'))
document.querySelector('.sidebar')?.insertAdjacentHTML('beforeend','<div class="sidebar-label">ADMINISTRASI</div><nav class="nav-side"><a href="{{ route('market-data.master') }}"><i class="bi bi-list-check"></i> Master Data</a><a href="{{ route('market-data.accounts') }}"><i class="bi bi-people"></i> Akun Petugas</a></nav>');
@endif
</script>
