<?php

namespace App\Http\Controllers;

use App\Models\PedagangPasar;
use App\Models\PengelolaPasar;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MarketAdminController extends Controller
{
    public function dashboard()
    {
        $managers = PengelolaPasar::where('is_active', true)->count();
        $merchants = PedagangPasar::where('is_active', true)->count();
        $capacity = (int) PengelolaPasar::where('is_active', true)->sum('kapasitas_tenant');
        $occupancy = $capacity > 0 ? round($merchants / $capacity * 100, 1) : 0;
        $businessTypes = PedagangPasar::where('is_active', true)->select('bidang_usaha', DB::raw('COUNT(*) total'))->groupBy('bidang_usaha')->orderByDesc('total')->get();
        $managerStats = PengelolaPasar::withCount(['pedagang' => fn ($q) => $q->where('is_active', true)])->orderByDesc('pedagang_count')->limit(8)->get();
        $recentMerchants = PedagangPasar::with('pengelola')->latest()->limit(5)->get();

        return view('market-data.dashboard', compact('managers', 'merchants', 'capacity', 'occupancy', 'businessTypes', 'managerStats', 'recentMerchants'));
    }

    public function master()
    {
        $businessTypes = DB::table('bidang_usaha_pasar')->orderBy('nama')->get();
        return view('market-data.master', compact('businessTypes'));
    }

    public function storeBusinessType(Request $request)
    {
        $data = $request->validate(['nama' => 'required|string|max:150|unique:bidang_usaha_pasar,nama']);
        DB::table('bidang_usaha_pasar')->insert($data + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Bidang usaha berhasil ditambahkan.');
    }

    public function deleteBusinessType(int $id)
    {
        $name = DB::table('bidang_usaha_pasar')->where('id', $id)->value('nama');
        abort_if(! $name, 404);
        if (PedagangPasar::where('bidang_usaha', $name)->exists()) {
            return back()->withErrors(['bidang_usaha' => 'Bidang usaha sudah digunakan dan tidak dapat dihapus.']);
        }
        DB::table('bidang_usaha_pasar')->where('id', $id)->delete();
        return back()->with('success', 'Bidang usaha berhasil dihapus.');
    }

    public function accounts()
    {
        $role = Role::where('name', 'petugas_bidang_pasar')->firstOrFail();
        $users = User::where('role_id', $role->id)->orderBy('name')->get();
        return view('market-data.accounts', compact('users'));
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'username' => 'required|alpha_dash|max:100|unique:users,username', 'email' => 'nullable|email|max:150|unique:users,email', 'password' => 'required|string|min:8|confirmed']);
        $data['role_id'] = Role::where('name', 'petugas_bidang_pasar')->value('id');
        $data['is_active'] = true;
        User::create($data);
        return back()->with('success', 'Akun Petugas Bidang Pasar berhasil dibuat.');
    }

    public function updateAccount(Request $request, User $user)
    {
        abort_unless($user->role?->name === 'petugas_bidang_pasar', 404);
        $data = $request->validate(['name' => 'required|string|max:150', 'username' => ['required', 'alpha_dash', 'max:100', Rule::unique('users')->ignore($user->id)], 'email' => ['nullable', 'email', 'max:150', Rule::unique('users')->ignore($user->id)], 'password' => 'nullable|string|min:8|confirmed']);
        if (empty($data['password'])) unset($data['password']);
        $user->update($data);
        return back()->with('success', 'Akun petugas berhasil diperbarui.');
    }

    public function toggleAccount(User $user)
    {
        abort_unless($user->role?->name === 'petugas_bidang_pasar', 404);
        $user->update(['is_active' => ! $user->is_active]);
        return back()->with('success', 'Status akun petugas berhasil diperbarui.');
    }
}
