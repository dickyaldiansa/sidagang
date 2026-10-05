<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bidang_usaha_pasar', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        foreach (['Sembako', 'Sayur dan Buah', 'Ikan dan Hasil Laut', 'Daging dan Unggas', 'Kuliner', 'Pakaian', 'Jasa', 'Lainnya'] as $name) {
            DB::table('bidang_usaha_pasar')->insert(['nama' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        $permissionId = DB::table('permissions')->insertGetId(['name' => 'market_data.manage', 'label' => 'Kelola Administrasi Bidang Pasar', 'module' => 'Bidang Pasar', 'description' => 'Mengelola akun petugas dan master data Bidang Pasar.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        foreach (DB::table('roles')->whereIn('name', ['super_admin', 'admin'])->pluck('id') as $roleId) {
            DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'market_data.manage')->value('id');
        DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
        Schema::dropIfExists('bidang_usaha_pasar');
    }
};
