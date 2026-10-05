<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', 'petugas_bidang_pasar')->value('id');
        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId(['name' => 'petugas_bidang_pasar', 'label' => 'Petugas Bidang Pasar', 'description' => 'Petugas pendataan pengelola dan pedagang pasar.', 'created_at' => now(), 'updated_at' => now()]);
        }
        $permissionIds = DB::table('permissions')->whereIn('name', ['market_data.view', 'market_data.create'])->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
        DB::table('users')->insertOrIgnore(['name' => 'Petugas Bidang Pasar', 'username' => 'petugasbidangpasar', 'email' => 'bidangpasar@sidagang.test', 'password' => Hash::make('password'), 'role_id' => $roleId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('users')->where('username', 'petugasbidangpasar')->delete();
        DB::table('roles')->where('name', 'petugas_bidang_pasar')->delete();
    }
};
