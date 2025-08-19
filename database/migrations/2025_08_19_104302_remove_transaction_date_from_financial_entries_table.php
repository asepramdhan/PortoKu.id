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
        Schema::table('financial_entries', function (Blueprint $table) {
            // Hapus kolom transaction_date jika ada
            if (Schema::hasColumn('financial_entries', 'transaction_date')) {
                $table->dropColumn('transaction_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            // Tambahkan kembali kolom transaction_date jika rollback dibutuhkan
            // Pastikan tipe data dan atributnya sesuai dengan sebelumnya
            $table->date('transaction_date')->nullable(); // Sesuaikan dengan definisi awal Anda
        });
    }
};
