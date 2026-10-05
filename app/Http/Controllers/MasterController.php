<?php

namespace App\Http\Controllers;

use App\Models\KelompokKomoditas;
use App\Models\KomoditasHarga;
use App\Models\KomoditasStok;
use App\Models\Pasar;
use App\Models\PeriodeSurveyHarga;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MasterController extends Controller
{
    private const TYPES = ['market', 'unit', 'group', 'price', 'stock', 'period', 'officer'];

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $markets = Pasar::orderBy('urutan')->get();
        $units = Satuan::orderBy('nama')->get();
        $groups = KelompokKomoditas::orderBy('urutan')->get();
        $priceCommodities = KomoditasHarga::with(['satuan', 'kelompok'])->orderBy('urutan')->get();
        $stockCommodities = KomoditasStok::with('satuan')->orderBy('urutan')->get();
        $periods = PeriodeSurveyHarga::latest('tanggal')->get();
        $canManageUsers = $request->user()->hasPermission('users.manage');
        $roles = $canManageUsers ? Role::when($request->user()->role->name !== 'super_admin', fn ($query) => $query->where('name', '!=', 'super_admin'))->orderBy('label')->get() : collect();
        $officers = $canManageUsers ? User::with(['role', 'pasar'])->orderBy('name')->get() : collect();

        $activeTab = in_array($request->query('tab'), self::TYPES, true) ? $request->query('tab') : 'market';
        $editing = null;
        if ($request->filled('edit') && preg_match('/^([a-z]+):(\d+)$/', $request->query('edit'), $matches)) {
            abort_unless(in_array($matches[1], self::TYPES, true), 404);
            $this->authorizeType($request, $matches[1]);
            $activeTab = $matches[1];
            $editing = $this->model($activeTab, (int) $matches[2]);
            $this->guardSuperAdmin($request, $editing);
        }

        return view('master.index', compact(
            'markets', 'units', 'groups', 'priceCommodities', 'stockCommodities',
            'periods', 'roles', 'officers', 'activeTab', 'editing', 'canManageUsers'
        ));
    }

    public function store(Request $request, string $type)
    {
        $this->authorizeAdmin($request);
        $this->assertType($type);
        $this->authorizeType($request, $type);
        $model = $this->newModel($type);
        $data = $this->prepare($request, $type, $this->validated($request, $type), $model);
        $model->fill($data)->save();

        return redirect()->route('master.index', ['tab' => $type])
            ->with('success', 'Data master berhasil ditambahkan.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $this->authorizeAdmin($request);
        $this->assertType($type);
        $this->authorizeType($request, $type);
        $model = $this->model($type, $id);
        $this->guardSuperAdmin($request, $model);
        $data = $this->prepare($request, $type, $this->validated($request, $type, $id), $model);
        $model->update($data);

        return redirect()->route('master.index', ['tab' => $type])
            ->with('success', 'Data master berhasil diperbarui.');
    }

    public function toggle(Request $request, string $type, int $id)
    {
        $this->authorizeAdmin($request);
        $this->assertType($type);
        $this->authorizeType($request, $type);
        $model = $this->model($type, $id);
        $this->guardSuperAdmin($request, $model);

        if ($type === 'officer' && $model->is($request->user())) {
            throw ValidationException::withMessages(['status' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan.']);
        }

        $model->update(['is_active' => ! $model->is_active]);

        return redirect()->route('master.index', ['tab' => $type])
            ->with('success', 'Status data berhasil diperbarui.');
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $this->authorizeAdmin($request);
        $this->assertType($type);
        $this->authorizeType($request, $type);
        $model = $this->model($type, $id);
        $this->guardSuperAdmin($request, $model);

        if ($type === 'officer' && $model->is($request->user())) {
            throw ValidationException::withMessages(['hapus' => 'Akun yang sedang digunakan tidak dapat dihapus.']);
        }

        if ($this->isUsed($type, $model)) {
            throw ValidationException::withMessages([
                'hapus' => 'Data sudah digunakan oleh transaksi atau data lain. Nonaktifkan data agar histori tetap utuh.',
            ]);
        }

        $model->delete();

        return redirect()->route('master.index', ['tab' => $type])
            ->with('success', 'Data master yang belum digunakan berhasil dihapus.');
    }

    private function validated(Request $request, string $type, ?int $id = null): array
    {
        $rules = match ($type) {
            'market' => [
                'kode' => ['required', 'string', 'max:20', Rule::unique('pasar', 'kode')->ignore($id)],
                'nama' => ['required', 'string', 'max:150'],
                'alamat' => ['nullable', 'string', 'max:1000'],
                'kecamatan' => ['nullable', 'string', 'max:100'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ],
            'unit' => [
                'kode' => ['required', 'string', 'max:20', Rule::unique('satuan', 'kode')->ignore($id)],
                'nama' => ['required', 'string', 'max:50'],
            ],
            'group' => [
                'kode' => ['required', 'string', 'max:30', Rule::unique('kelompok_komoditas', 'kode')->ignore($id)],
                'nama' => ['required', 'string', 'max:100'],
            ],
            'price' => [
                'kode' => ['required', 'string', 'max:30', Rule::unique('komoditas_harga', 'kode')->ignore($id)],
                'nama' => ['required', 'string', 'max:200'],
                'satuan_id' => ['required', 'integer', 'exists:satuan,id'],
                'kelompok_id' => ['nullable', 'integer', 'exists:kelompok_komoditas,id'],
                'het_ha' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            ],
            'stock' => [
                'kode' => ['required', 'string', 'max:30', Rule::unique('komoditas_stok', 'kode')->ignore($id)],
                'nama' => ['required', 'string', 'max:200'],
                'satuan_id' => ['required', 'integer', 'exists:satuan,id'],
            ],
            'period' => [
                'tanggal' => ['required', 'date', Rule::unique('periode_survey_harga', 'tanggal')->ignore($id)],
                'deadline' => ['nullable', 'date'],
                'keterangan' => ['nullable', 'string', 'max:255'],
            ],
            'officer' => [
                'name' => ['required', 'string', 'max:150'],
                'username' => ['required', 'alpha_dash', 'max:100', Rule::unique('users', 'username')->ignore($id)],
                'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
                'role_id' => ['required', 'integer', 'exists:roles,id'],
                'pasar_id' => ['nullable', 'integer', 'exists:pasar,id'],
                'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            ],
        };

        return $request->validate($rules, [], [
            'kode' => 'kode', 'nama' => 'nama', 'satuan_id' => 'satuan',
            'kelompok_id' => 'kelompok komoditas', 'role_id' => 'peran', 'pasar_id' => 'pasar',
        ]);
    }

    private function prepare(Request $request, string $type, array $data, Model $model): array
    {
        if (in_array($type, ['market', 'group', 'price', 'stock'], true) && ! $model->exists) {
            $data['urutan'] = $model->newQuery()->max('urutan') + 1;
        }

        if ($type === 'period' && ! $model->exists) {
            $data['created_by'] = $request->user()->id;
        }

        if ($type === 'officer') {
            $role = Role::findOrFail($data['role_id']);
            if ($role->name === 'super_admin' && $request->user()->role->name !== 'super_admin') {
                abort(403, 'Hanya Super Administrator yang dapat menetapkan peran ini.');
            }
            if ($role->name === 'petugas_pasar' && empty($data['pasar_id'])) {
                throw ValidationException::withMessages(['pasar_id' => 'Pasar wajib dipilih untuk Petugas Pasar.']);
            }
            if ($role->name !== 'petugas_pasar') {
                $data['pasar_id'] = null;
            }
            if (empty($data['password'])) {
                unset($data['password']);
            }
        }

        return $data;
    }

    private function isUsed(string $type, Model $model): bool
    {
        return match ($type) {
            'market' => DB::table('users')->where('pasar_id', $model->id)->exists()
                || DB::table('laporan_harga')->where('pasar_id', $model->id)->exists(),
            'unit' => DB::table('komoditas_harga')->where('satuan_id', $model->id)->exists()
                || DB::table('komoditas_stok')->where('satuan_id', $model->id)->exists(),
            'group' => DB::table('komoditas_harga')->where('kelompok_id', $model->id)->exists(),
            'price' => DB::table('laporan_harga_detail')->where('komoditas_harga_id', $model->id)->exists(),
            'stock' => DB::table('laporan_stok_detail')->where('komoditas_stok_id', $model->id)->exists(),
            'period' => DB::table('laporan_harga')->whereDate('tanggal', $model->tanggal)->exists(),
            'officer' => DB::table('laporan_harga')->where('petugas_id', $model->id)->exists()
                || DB::table('laporan_stok')->where('petugas_id', $model->id)->exists()
                || DB::table('laporan_harga')->where('verified_by', $model->id)->orWhere('rejected_by', $model->id)->exists()
                || DB::table('laporan_stok')->where('verified_by', $model->id)->orWhere('rejected_by', $model->id)->exists()
                || DB::table('periode_survey_harga')->where('created_by', $model->id)->exists(),
        };
    }

    private function newModel(string $type): Model
    {
        return match ($type) {
            'market' => new Pasar,
            'unit' => new Satuan,
            'group' => new KelompokKomoditas,
            'price' => new KomoditasHarga,
            'stock' => new KomoditasStok,
            'period' => new PeriodeSurveyHarga,
            'officer' => new User,
        };
    }

    private function model(string $type, int $id): Model
    {
        return $this->newModel($type)->newQuery()->findOrFail($id);
    }

    private function assertType(string $type): void
    {
        abort_unless(in_array($type, self::TYPES, true), 404);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('master.manage'), 403);
    }

    private function authorizeType(Request $request, string $type): void
    {
        if ($type === 'officer') {
            abort_unless($request->user()->hasPermission('users.manage'), 403);
        }
    }

    private function guardSuperAdmin(Request $request, Model $model): void
    {
        if ($model instanceof User && $model->role?->name === 'super_admin' && $request->user()->role->name !== 'super_admin') {
            abort(403, 'Akun Super Administrator hanya dapat dikelola oleh Super Administrator.');
        }
    }
}
