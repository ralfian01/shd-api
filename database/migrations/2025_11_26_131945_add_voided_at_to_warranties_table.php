<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warranties', function (Blueprint $table) {
            // Menambahkan kolom setelah 'service_tag'
            // Tipe timestamp dan nullable, defaultnya null (aktif)
            $table->timestamp('voided_at')->nullable()->after('service_tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warranties', function (Blueprint $table) {
            $table->dropColumn('voided_at');
        });
    }
};
