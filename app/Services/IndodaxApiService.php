<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt; // Diperlukan jika Secret Key dienkripsi

class IndodaxApiService
{
  protected string $apiKey;
  protected string $secretKey;

  /**
   * Inisialisasi service dengan API Key dan Secret dari User.
   */
  public function __construct(User $user)
  {
    $this->apiKey = $user->indodax_api_key ?? '';

    // PENTING: Jika Anda mengenkripsi Secret Key saat menyimpan, 
    // ganti baris di bawah ini:
    // $this->secretKey = Crypt::decryptString($user->indodax_secret_key);

    // JIKA TIDAK DIENKRIPSI (kurang aman, hanya untuk debugging/development):
    $this->secretKey = $user->indodax_secret_key ?? '';
  }

  /**
   * Fungsi dasar untuk mengirim permintaan ke TAPI Indodax.
   * @param array $params Parameter spesifik untuk metode API (e.g., method, pair).
   * @return array Hasil respons JSON.
   * @throws \Exception
   */
  protected function request(array $params): array
  {
    if (empty($this->apiKey) || empty($this->secretKey)) {
      throw new \Exception("API Keys Indodax belum diatur.");
    }

    // Tambahkan parameter wajib: nonce (timestamp), method, dll.
    // Catatan: Dokumentasi Indodax menggunakan 'timestamp' dan 'recvWindow'.
    // Namun, API mereka juga dapat menerima 'nonce' saja (dari dokumentasi lama).
    // Kita gunakan format yang sederhana dulu: nonce (milidetik).

    $params['nonce'] = (int)(microtime(true) * 1000); // Nonce dalam milidetik

    // Ubah data menjadi query string untuk Signing
    $postData = http_build_query($params, '', '&');

    // Buat Signature (Hash HMAC SHA-512)
    $signature = hash_hmac('sha512', $postData, $this->secretKey);

    // Kirim request ke API Private Indodax
    $response = Http::withHeaders([
      'Key' => $this->apiKey,
      'Sign' => $signature,
    ])
      ->asForm() // Mengirim data sebagai application/x-www-form-urlencoded
      ->post('https://indodax.com/tapi', $params);

    if ($response->successful()) {
      $data = $response->json();
      if (isset($data['success']) && $data['success'] == 1) {
        return $data;
      }

      // Tangani error API (success: 0)
      throw new \Exception("API Error: " . ($data['error'] ?? 'Unknown error'));
    }

    throw new \Exception("Gagal koneksi ke Indodax API: HTTP Status " . $response->status());
  }

  /**
   * Mengambil informasi saldo akun (sesuai 'getInfo').
   */
  public function getInfo(): array
  {
    return $this->request(['method' => 'getInfo']);
  }

  /**
   * Mengambil histori transaksi (sesuai 'transHistory').
   * @param string $asset Tipe aset (e.g., 'btc').
   */
  public function getTransactionHistory(string $asset = 'btc'): array
  {
    return $this->request([
      'method' => 'transHistory',
      'asset' => $asset, // Perlu parameter asset
      // Tambahkan parameter lain seperti: 'count', 'offset', 'order' jika perlu
    ]);
  }

  /**
   * Mengambil histori trade (sering dipakai untuk riwayat beli/jual).
   * Saya tambahkan ini sebagai alternatif yang lebih umum:
   */
  public function getTradeHistory(string $pair = 'btc_idr'): array
  {
    return $this->request([
      'method' => 'tradeHistory',
      'pair' => $pair,
    ]);
  }
}
