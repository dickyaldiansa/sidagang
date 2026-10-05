<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komoditas_harga', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 30)->unique();
            $t->string('nama', 200);
            $t->foreignId('kelompok_id')->nullable()->constrained('kelompok_komoditas');
            $t->foreignId('satuan_id')->constrained('satuan');
            $t->unsignedInteger('urutan')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('komoditas_stok', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 30)->unique();
            $t->string('nama', 200);
            $t->foreignId('satuan_id')->constrained('satuan');
            $t->unsignedInteger('urutan')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('periode_survey_harga', function (Blueprint $t) {
            $t->id();
            $t->date('tanggal')->unique();
            $t->dateTime('deadline')->nullable();
            $t->string('keterangan')->nullable();
            $t->boolean('is_active')->default(true);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('laporan_harga', function (Blueprint $t) {
            $t->id();
            $t->date('tanggal')->index();
            $t->foreignId('pasar_id')->constrained('pasar')->index();
            $t->foreignId('petugas_id')->constrained('users');
            $t->string('status', 20)->default('draft')->index();
            $t->dateTime('submitted_at')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('verified_at')->nullable();
            $t->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('rejected_at')->nullable();
            $t->text('catatan_verifikasi')->nullable();
            $t->timestamps();
            $t->unique(['tanggal', 'pasar_id']);
        });
        Schema::create('laporan_harga_detail', function (Blueprint $t) {
            $t->id();
            $t->foreignId('laporan_harga_id')->constrained('laporan_harga')->cascadeOnDelete();
            $t->foreignId('komoditas_harga_id')->constrained('komoditas_harga');
            $t->decimal('harga', 15, 2)->nullable();
            $t->string('status_data', 20)->default('available');
            $t->string('catatan')->nullable();
            $t->timestamps();
            $t->unique(['laporan_harga_id', 'komoditas_harga_id'], 'harga_detail_unique');
        });
        Schema::create('laporan_stok', function (Blueprint $t) {
            $t->id();
            $t->date('tanggal')->index();
            $t->unsignedTinyInteger('minggu_ke')->nullable();
            $t->unsignedTinyInteger('bulan');
            $t->unsignedSmallInteger('tahun');
            $t->foreignId('petugas_id')->constrained('users');
            $t->string('sumber_data')->nullable();
            $t->string('status', 20)->default('draft')->index();
            $t->dateTime('submitted_at')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('verified_at')->nullable();
            $t->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('rejected_at')->nullable();
            $t->text('catatan_verifikasi')->nullable();
            $t->timestamps();
        });
        Schema::create('laporan_stok_detail', function (Blueprint $t) {
            $t->id();
            $t->foreignId('laporan_stok_id')->constrained('laporan_stok')->cascadeOnDelete();
            $t->foreignId('komoditas_stok_id')->constrained('komoditas_stok');
            $t->decimal('jumlah', 18, 3)->nullable();
            $t->string('catatan')->nullable();
            $t->timestamps();
            $t->unique(['laporan_stok_id', 'komoditas_stok_id'], 'stok_detail_unique');
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->string('type', 20)->default('string');
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('module');
            $t->string('action', 30);
            $t->string('table_name')->nullable();
            $t->unsignedBigInteger('record_id')->nullable();
            $t->json('old_value')->nullable();
            $t->json('new_value')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'settings', 'laporan_stok_detail', 'laporan_stok', 'laporan_harga_detail', 'laporan_harga', 'periode_survey_harga', 'komoditas_stok', 'komoditas_harga'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
