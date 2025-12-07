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
        Schema::table('shopee_ads', function (Blueprint $table) {
            // Tambahkan kolom integer 'clicks_count' dengan nilai default 0
            $table->unsignedBigInteger('clicks_count')->default(0)->after('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopee_ads', function (Blueprint $table) {
            $table->dropColumn('clicks_count');
        });
    }
};
