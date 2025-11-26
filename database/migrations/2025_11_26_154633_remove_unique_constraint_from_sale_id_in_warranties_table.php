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
            // Langkah 1: Hapus foreign key constraint yang lama.
            // Laravel secara default menamai constraint: namatabel_namakolom_foreign
            $table->dropForeign('warranties_sale_id_foreign');

            // Langkah 2: Hapus unique index. Sekarang ini akan berhasil.
            $table->dropUnique('warranties_sale_id_unique');

            // Langkah 3: Buat index biasa (non-unique) pada kolom sale_id.
            // Ini sangat penting untuk performa query.
            $table->index('sale_id');

            // Langkah 4: Buat kembali foreign key constraint.
            // Sekarang ia akan menggunakan index biasa yang baru.
            $table->foreign('sale_id')
                ->references('id')
                ->on('sales')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     * (Penting untuk membuat operasi kebalikannya jika perlu rollback)
     */
    public function down(): void
    {
        Schema::table('warranties', function (Blueprint $table) {
            // Urutan dibalik dari method up()
            $table->dropForeign('warranties_sale_id_foreign');
            $table->dropIndex('warranties_sale_id_index'); // Hapus index biasa
            $table->unique('sale_id'); // Buat kembali unique index

            // Buat kembali foreign key dengan unique index
            $table->foreign('sale_id')
                ->references('id')
                ->on('sales')
                ->onDelete('cascade');
        });
    }
};
