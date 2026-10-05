<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $superAdminRoleId = DB::table('roles')->where('name', 'super_admin')->value('id');
        $account = DB::table('users')->where('role_id', $superAdminRoleId)->orderBy('id')->first();

        if (! $account) {
            throw new RuntimeException('Akun Super Administrator tidak ditemukan.');
        }

        DB::table('users')->where('id', '!=', $account->id)->where('username', 'administrator')->update(['username' => DB::raw("CONCAT('legacy-', id)")]);
        DB::table('users')->where('id', $account->id)->update([
            'name' => 'Administrator SIDAGANG',
            'username' => 'administrator',
            'password' => Hash::make('Sdg@Batam2026!'),
            'is_active' => true,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Kredensial sebelumnya dapat dipulihkan dari backup database.
    }
};
