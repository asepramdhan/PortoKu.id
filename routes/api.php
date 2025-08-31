<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use App\Models\Message;
use Illuminate\Http\Request;

Route::get('/messages/unread-count', function (Request $request) {
  // Jalankan logika untuk mendapatkan jumlah pesan
  $unreadCount = Cache::remember('messages.unread_count', now()->addMinutes(5), function () {
    return Message::whereNull("read_at")
      ->whereHas("user", fn($q) => $q->where("is_admin", false))
      ->count();
  });

  // Kode di bawah ini TIDAK AKAN dieksekusi karena dd() menghentikan skrip
  return response()->json([
    'unreadCount' => $unreadCount
  ]);
});
