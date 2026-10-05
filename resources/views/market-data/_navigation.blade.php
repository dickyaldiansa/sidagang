<script>
document.querySelector('.topbar .dropdown-menu li:first-child')?.remove();
const marketNavs=document.querySelectorAll('.sidebar .nav-side');
if(marketNavs[0])marketNavs[0].innerHTML='<a href="{{ route('market-data.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a><a href="{{ route('market-data.index') }}"><i class="bi bi-database"></i> Data Pasar</a>';
@if(auth()->user()->hasPermission('market_data.manage'))
document.querySelector('.sidebar')?.insertAdjacentHTML('beforeend','<div class="sidebar-label">ADMINISTRASI</div><nav class="nav-side"><a href="{{ route('market-data.master') }}"><i class="bi bi-list-check"></i> Master Data</a><a href="{{ route('market-data.accounts') }}"><i class="bi bi-people"></i> Akun Petugas</a></nav>');
@endif
</script>
