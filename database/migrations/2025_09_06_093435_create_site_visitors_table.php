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
        Schema::create('site_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->text('user_agent')->nullable(); // Info browser pengunjung
            $table->timestamps(); // Kita butuh created_at untuk menghitung per hari
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_visitors');
    }
};
