<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Hapus kolom dari tabel sales
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });
        // Tambahkan kolom ke tabel warranties
        Schema::table('warranties', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->unique()->after('sale_id');
        });
    }
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->unique();
        });
        Schema::table('warranties', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });
    }
};
