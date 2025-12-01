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
        Schema::table('users', function (Blueprint $table) {
            // Kolom untuk Indodax API Key (string/varchar)
            $table->string('indodax_api_key')->nullable()->after('phone_number');

            // Kolom untuk Indodax Secret Key. Disarankan menggunakan tipe data yang aman 
            // karena biasanya nilainya dienkripsi.
            $table->text('indodax_secret_key')->nullable()->after('indodax_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom saat rollback
            $table->dropColumn(['indodax_api_key', 'indodax_secret_key']);
        });
    }
};
