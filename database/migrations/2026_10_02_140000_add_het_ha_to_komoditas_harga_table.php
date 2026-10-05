<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('komoditas_harga', fn (Blueprint $table) => $table->decimal('het_ha', 15, 2)->nullable()->after('nama')); } public function down(): void { Schema::table('komoditas_harga', fn (Blueprint $table) => $table->dropColumn('het_ha')); } };
