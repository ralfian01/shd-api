<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Durasi dalam bulan, unsigned (tidak bisa negatif), default 12 bulan (1 tahun)
            $table->unsignedInteger('warranty_duration_months')->default(12)->after('tags');
        });
    }
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('warranty_duration_months');
        });
    }
};
