<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('label', 150);
            $table->string('module', 50)->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        $definitions = $this->definitions();
        foreach ($definitions as $name => [$label, $module, $description]) {
            DB::table('permissions')->insert([
                'name' => $name, 'label' => $label, 'module' => $module, 'description' => $description,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Existing installations already have roles when this migration runs.
        $this->assignDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
    }

    private function assignDefaults(): void
    {
        $defaults = $this->roleDefaults();
        $permissions = DB::table('permissions')->pluck('id', 'name');
        foreach (DB::table('roles')->get(['id', 'name']) as $role) {
            foreach ($defaults[$role->name] ?? [] as $permission) {
                if (isset($permissions[$permission])) {
                    DB::table('permission_role')->insertOrIgnore(['role_id' => $role->id, 'permission_id' => $permissions[$permission]]);
                }
            }
        }
    }

    private function definitions(): array
    {
        return [
            'dashboard.view' => ['Lihat Dashboard', 'Dashboard', 'Melihat ringkasan dan indikator utama.'],
            'prices.view' => ['Lihat Harga', 'Harga', 'Melihat monitoring, detail, tren, dan perbandingan harga.'],
            'prices.create' => ['Input Harga', 'Harga', 'Membuat dan mengirim laporan harga pasar.'],
            'prices.verify' => ['Verifikasi Harga', 'Harga', 'Menerima atau menolak laporan harga.'],
            'stocks.view' => ['Lihat Stok', 'Stok', 'Melihat monitoring, detail, dan tren stok.'],
            'stocks.create' => ['Input Stok', 'Stok', 'Membuat dan mengirim laporan stok.'],
            'stocks.verify' => ['Verifikasi Stok', 'Stok', 'Menerima atau menolak laporan stok.'],
            'reports.view' => ['Lihat dan Export Laporan', 'Laporan', 'Melihat serta mengunduh laporan bulanan.'],
            'master.manage' => ['Kelola Master Data', 'Administrasi', 'Mengelola pasar, komoditas, satuan, dan periode survei.'],
            'users.manage' => ['Kelola Akun', 'Administrasi', 'Membuat, mengubah, menonaktifkan, dan menghapus akun.'],
            'roles.manage' => ['Kelola Hak Akses', 'Administrasi', 'Mengatur permission setiap peran.'],
        ];
    }

    private function roleDefaults(): array
    {
        $all = array_keys($this->definitions());

        return [
            'super_admin' => $all,
            'admin' => $all,
            'petugas_pasar' => ['dashboard.view', 'prices.view', 'prices.create'],
            'petugas_stok' => ['dashboard.view', 'stocks.view', 'stocks.create'],
            'validator' => ['dashboard.view', 'prices.view', 'prices.verify', 'stocks.view', 'stocks.verify', 'reports.view'],
            'kabid' => ['dashboard.view', 'prices.view', 'stocks.view', 'reports.view'],
            'kepala_dinas' => ['dashboard.view', 'prices.view', 'stocks.view', 'reports.view'],
        ];
    }
};
