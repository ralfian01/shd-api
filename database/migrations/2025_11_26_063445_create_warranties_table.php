<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            // Relasi One-to-One dengan tabel sales
            $table->foreignId('sale_id')->unique()->constrained('sales')->onDelete('cascade');
            $table->string('card_number')->unique();
            $table->string('express_service_code')->nullable();
            $table->string('service_tag')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
