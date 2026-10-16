<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengelola_pasar', function (Blueprint $table) {
            $table->string('nama_pasar')->nullable()->after('nama_perusahaan');
        });
    }

    public function down(): void
    {
        Schema::table('pengelola_pasar', function (Blueprint $table) {
            $table->dropColumn('nama_pasar');
        });
    }
};
