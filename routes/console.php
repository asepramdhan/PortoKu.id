<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // Pastikan Schedule di-import

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FIX: Tambahkan penjadwalan Anda di sini
Schedule::command('app:generate-portfolio-summary')->dailyAt('06:00');
