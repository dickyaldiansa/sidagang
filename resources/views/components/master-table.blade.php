@props(['rows', 'type'])
<div class="card master-list-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <strong>Daftar Data</strong>
        <span class="badge rounded-pill bg-primary-subtle text-primary">{{ $rows->count() }} data</span>
    </div>
    <div class="table-responsive">
        <table class="table master-table mb-0">
            <thead><tr><th>Kode</th><th>Nama / Keterangan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $protectedSuperAdmin = $type === 'officer' && $row->role?->name === 'super_admin' && auth()->user()->role->name !== 'super_admin';
                    $self = $type === 'officer' && $row->is(auth()->user());
                    $details = match($type) {
                        'market' => collect([$row->kecamatan, $row->alamat])->filter()->implode(' · '),
                        'price' => collect([$row->kelompok?->nama, $row->satuan?->nama, $row->het_ha !== null ? 'HET/HA Rp'.number_format($row->het_ha, 0, ',', '.') : null])->filter()->implode(' · '),
                        'stock' => $row->satuan?->nama,
                        'officer' => collect([$row->role?->label, $row->pasar?->nama, $row->email])->filter()->implode(' · '),
                        default => null,
                    };
                    $code = $type === 'officer' ? $row->username : $row->kode;
                    $name = $type === 'officer' ? $row->name : $row->nama;
                @endphp
                <tr>
                    <td data-label="Kode"><code>{{ $code }}</code></td>
                    <td data-label="Nama"><div class="fw-semibold">{{ $name }}</div>@if($details)<small class="text-muted">{{ $details }}</small>@endif</td>
                    <td data-label="Status"><span class="badge {{ $row->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td data-label="Aksi" class="text-end">
                        @if(!$protectedSuperAdmin)
                        <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('master.index', ['tab' => $type, 'edit' => $type.':'.$row->id]).'#'.$type }}" title="Edit"><i class="bi bi-pencil-square"></i><span class="d-none d-xl-inline ms-1">Edit</span></a>
                            @if(!$self)
                            <form method="post" action="{{ route('master.toggle', [$type, $row->id]).'?tab='.$type }}">@csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-secondary" title="{{ $row->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"><i class="bi {{ $row->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i></button>
                            </form>
                            <form method="post" action="{{ route('master.destroy', [$type, $row->id]).'?tab='.$type }}" onsubmit="return confirm('Hapus data {{ addslashes($name) }}? Data yang sudah digunakan tidak dapat dihapus.')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash3"></i></button>
                            </form>
                            @endif
                        </div>
                        @else
                            <span class="text-muted small"><i class="bi bi-shield-lock me-1"></i>Dilindungi</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
