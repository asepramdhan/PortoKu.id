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
        Schema::table('comments', function (Blueprint $table) {
            // Tambahkan kolom baru untuk menyimpan ID komentar induk
            // onDelete('cascade') artinya jika komentar induk dihapus, semua balasannya ikut terhapus.
            $table->foreignId('parent_id')->nullable()->after('post_id')->constrained('comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Hapus foreign key constraint dulu sebelum hapus kolom
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
