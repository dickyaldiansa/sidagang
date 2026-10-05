<?php

namespace App\Http\Controllers;

use App\Models\Pasar;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdministratorController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['role', 'pasar'])->orderBy('name')->get();
        $roles = Role::with('permissions')->withCount('users')->orderBy('label')->get();
        $permissions = Permission::where('is_active', true)->orderBy('module')->orderBy('label')->get()->groupBy('module');
        $markets = Pasar::where('is_active', true)->orderBy('urutan')->get();
        $availableRoles = Role::when($request->user()->role->name !== 'super_admin', fn ($query) => $query->where('name', '!=', 'super_admin'))
            ->orderBy('label')->get();
        $activeTab = $request->query('tab') === 'access' || ! $request->user()->hasPermission('users.manage') ? 'access' : 'accounts';
        $editing = null;

        if ($request->user()->hasPermission('users.manage') && $request->filled('edit')) {
            $editing = User::findOrFail((int) $request->query('edit'));
            $this->guardSuperAdmin($request, $editing);
        }

        return view('administrator.index', compact('users', 'roles', 'permissions', 'markets', 'availableRoles', 'activeTab', 'editing'));
    }

    public function storeAccount(Request $request)
    {
        $data = $this->validatedAccount($request);
        $data = $this->prepareAccount($request, $data);
        User::create($data);

        return redirect()->route('administrator.index', ['tab' => 'accounts'])->with('success', 'Akun baru berhasil dibuat.');
    }

    public function updateAccount(Request $request, User $user)
    {
        $this->guardSuperAdmin($request, $user);
        $data = $this->prepareAccount($request, $this->validatedAccount($request, $user->id));
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return redirect()->route('administrator.index', ['tab' => 'accounts'])->with('success', 'Akun berhasil diperbarui.');
    }

    public function toggleAccount(Request $request, User $user)
    {
        $this->guardSuperAdmin($request, $user);
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['akun' => 'Akun yang sedang digunakan tidak dapat dinonaktifkan.']);
        }
        $user->update(['is_active' => ! $user->is_active]);

        return redirect()->route('administrator.index', ['tab' => 'accounts'])->with('success', 'Status akun berhasil diperbarui.');
    }

    public function destroyAccount(Request $request, User $user)
    {
        $this->guardSuperAdmin($request, $user);
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['akun' => 'Akun yang sedang digunakan tidak dapat dihapus.']);
        }
        if ($this->accountIsUsed($user)) {
            throw ValidationException::withMessages(['akun' => 'Akun sudah memiliki histori aktivitas. Nonaktifkan akun agar histori tetap utuh.']);
        }
        $user->delete();

        return redirect()->route('administrator.index', ['tab' => 'accounts'])->with('success', 'Akun yang belum digunakan berhasil dihapus.');
    }

    public function updatePermissions(Request $request, Role $role)
    {
        if ($role->name === 'super_admin') {
            throw ValidationException::withMessages(['permission' => 'Super Administrator selalu memiliki seluruh hak akses dan tidak dapat dibatasi.']);
        }
        if ($request->user()->role_id === $role->id && $request->user()->role->name !== 'super_admin') {
            throw ValidationException::withMessages(['permission' => 'Anda tidak dapat mengubah hak akses peran yang sedang Anda gunakan sendiri.']);
        }

        $data = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => ['integer', 'exists:permissions,id']]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('administrator.index', ['tab' => 'access'])->with('success', 'Hak akses '.$role->label.' berhasil diperbarui.');
    }

    private function validatedAccount(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'alpha_dash', 'max:100', Rule::unique('users', 'username')->ignore($id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'pasar_id' => ['nullable', 'integer', 'exists:pasar,id'],
            'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ], [], ['role_id' => 'peran', 'pasar_id' => 'pasar']);
    }

    private function prepareAccount(Request $request, array $data): array
    {
        $role = Role::findOrFail($data['role_id']);
        if ($role->name === 'super_admin' && $request->user()->role->name !== 'super_admin') {
            abort(403, 'Hanya Super Administrator yang dapat membuat akun Super Administrator.');
        }
        if ($role->name === 'petugas_pasar' && empty($data['pasar_id'])) {
            throw ValidationException::withMessages(['pasar_id' => 'Pasar wajib dipilih untuk Petugas Pasar.']);
        }
        if ($role->name !== 'petugas_pasar') {
            $data['pasar_id'] = null;
        }

        return $data;
    }

    private function guardSuperAdmin(Request $request, User $user): void
    {
        if ($user->role?->name === 'super_admin' && $request->user()->role->name !== 'super_admin') {
            abort(403, 'Akun Super Administrator hanya dapat dikelola oleh Super Administrator.');
        }
    }

    private function accountIsUsed(User $user): bool
    {
        return DB::table('laporan_harga')->where('petugas_id', $user->id)->orWhere('verified_by', $user->id)->orWhere('rejected_by', $user->id)->exists()
            || DB::table('laporan_stok')->where('petugas_id', $user->id)->orWhere('verified_by', $user->id)->orWhere('rejected_by', $user->id)->exists()
            || DB::table('periode_survey_harga')->where('created_by', $user->id)->exists()
            || DB::table('pengelola_pasar')->where('created_by', $user->id)->orWhere('verified_by', $user->id)->exists()
            || DB::table('pedagang_pasar')->where('created_by', $user->id)->orWhere('verified_by', $user->id)->exists();
    }
}
