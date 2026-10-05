<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengelola_pasar', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan');
            $table->text('alamat');
            $table->string('no_telepon', 50);
            $table->string('kontak_person');
            $table->unsignedInteger('kapasitas_tenant')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
        });

        Schema::create('pedagang_pasar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengelola_pasar_id')->constrained('pengelola_pasar')->cascadeOnDelete();
            $table->string('nama_toko');
            $table->string('penanggung_jawab');
            $table->string('no_telepon', 50);
            $table->string('bidang_usaha');
            $table->string('nomor_kios')->nullable();
            $table->text('alamat')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
            $table->unique(['pengelola_pasar_id', 'nama_toko', 'no_telepon'], 'pedagang_pasar_unique');
        });

        $permissions = [
            ['market_data.view', 'Lihat Data Bidang Pasar', 'Bidang Pasar', 'Melihat pengelola, pedagang, dan rekap Bidang Pasar.'],
            ['market_data.create', 'Input Data Bidang Pasar', 'Bidang Pasar', 'Membuat dan memperbarui data pengelola serta pedagang.'],
            ['market_data.verify', 'Verifikasi Data Bidang Pasar', 'Bidang Pasar', 'Menerima atau menolak data pengelola dan pedagang.'],
            ['market_data.export', 'Export Data Bidang Pasar', 'Bidang Pasar', 'Mengunduh rekap pengelola dan pedagang.'],
        ];
        foreach ($permissions as [$name, $label, $module, $description]) {
            DB::table('permissions')->insertOrIgnore(compact('name', 'label', 'module', 'description') + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        $permissionIds = DB::table('permissions')->whereIn('name', array_column($permissions, 0))->pluck('id', 'name');
        foreach (DB::table('roles')->get(['id', 'name']) as $role) {
            $names = match ($role->name) {
                'super_admin', 'admin' => array_column($permissions, 0),
                'petugas_pasar' => ['market_data.view', 'market_data.create'],
                'validator' => ['market_data.view', 'market_data.verify', 'market_data.export'],
                'kabid', 'kepala_dinas' => ['market_data.view', 'market_data.export'],
                default => [],
            };
            foreach ($names as $name) {
                DB::table('permission_role')->insertOrIgnore(['role_id' => $role->id, 'permission_id' => $permissionIds[$name]]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', ['market_data.view', 'market_data.create', 'market_data.verify', 'market_data.export'])->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('pedagang_pasar');
        Schema::dropIfExists('pengelola_pasar');
    }
};
