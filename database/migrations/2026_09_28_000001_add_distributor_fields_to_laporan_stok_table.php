<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_stok', function (Blueprint $table) {
            $table->string('nama_distributor')->nullable()->after('sumber_data');
            $table->string('contact_person')->nullable()->after('nama_distributor');
            $table->text('alamat')->nullable()->after('contact_person');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_stok', function (Blueprint $table) {
            $table->dropColumn(['nama_distributor', 'contact_person', 'alamat']);
        });
    }
};
