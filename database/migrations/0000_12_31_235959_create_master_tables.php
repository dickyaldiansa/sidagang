<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('pasar', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 150);
            $table->text('alamat')->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('satuan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('kelompok_komoditas', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 100);
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelompok_komoditas');
        Schema::dropIfExists('satuan');
        Schema::dropIfExists('pasar');
        Schema::dropIfExists('roles');
    }
};
