<?php

use App\Models\Asset;
use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component {
    use WithPagination, WithFileUploads;

    // Properti baru untuk unggah struk
    public $receiptImage;
    public $scanContext;
    public $editReceiptImage;
    public string $scanStatusMessage = "";
    public string $scanStatusType = "success";

    // Filter properties
    public $filterType = "",
        $filterAsset = "",
        $search = "",
        $filterDate = "";

    // Properti untuk modal
    public $showAddModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;

    // === ADD FORM PROPERTIES ===
    public $type = "income",
        $asset_id,
        $quantity,
        $price_per_unit,
        $amount,
        $fee_percentage,
        $fee_amount,
        $category,
        $notes,
        $transaction_date;

    // === EDIT FORM PROPERTIES ===
    public $edit_type,
        $edit_asset_id,
        $edit_quantity,
        $edit_price_per_unit,
        $edit_amount,
        $edit_fee_percentage,
        $edit_fee_amount,
        $edit_category,
        $edit_notes,
        $edit_transaction_date;

    // Properti untuk aksi
    public ?FinancialEntry $editing = null;
    public ?FinancialEntry $deleting = null;

    // === INLINE EDITING PROPERTIES ===
    public $editingId = null;
    public $editingField = null;
    public $editingValue = "";

    // Set scan context
    public function setScanContext(string $context)
    {
        $this->scanContext = $context;
    }

    // Updated receipt image
    public function updatedReceiptImage()
    {
        $this->validate(["receiptImage" => "required|image|max:4096"]);

        if ($this->scanContext === "asset") {
            $this->processAssetTransaction();
        } else {
            // Default ke proses struk biasa
            $this->processExpenseReceipt();
        }
    }

    // Proses struk biasa
    private function processExpenseReceipt()
    {
        $this->scanStatusMessage = "Membaca gambar struk...";
        $this->scanStatusType = "info";

        try {
            // === LANGKAH 1: Gunakan OCR.space untuk mengubah gambar menjadi teks mentah ===
            $ocrApiKey = config("services.ocrspace.api_key");
            if (! $ocrApiKey) {
                throw new \Exception("Kunci API OCR.space belum diatur");
            }

            $ocrResponse = \Illuminate\Support\Facades\Http::withHeaders([
                "apikey" => $ocrApiKey,
            ])
                ->attach(
                    "file",
                    file_get_contents($this->receiptImage->getRealPath()),
                    $this->receiptImage->getClientOriginalName(),
                )
                ->post("https://api.ocr.space/parse/image", [
                    "language" => "eng",
                ]);

            $ocrResult = $ocrResponse->json();

            if (
                ! $ocrResponse->successful() ||
                $ocrResult["IsErroredOnProcessing"]
            ) {
                throw new \Exception(
                    $ocrResult["ErrorMessage"][0] ??
                        "Gagal memproses gambar dengan OCR.",
                );
            }

            $rawText = $ocrResult["ParsedResults"][0]["ParsedText"];

            // === LANGKAH 2: Gunakan Groq untuk menganalisis teks mentah hasil OCR ===
            $this->scanStatusMessage = "Menganalisis teks struk...";
            $groqApiKey = config("services.groq.api_key");
            if (! $groqApiKey) {
                throw new \Exception("Kunci API Groq belum diatur");
            }

            // Prompt untuk Groq, sekarang inputnya adalah teks, bukan gambar
            $promptForGroq =
                "Anda adalah seorang analis data keuangan yang sangat teliti dan cerdas. Tugas Anda adalah menganalisis teks hasil OCR dari sebuah struk dan mengekstrak data secara akurat ke dalam format JSON.

                Aturan untuk `type`:
                1.  Berdasarkan konteks struk, tentukan tipe transaksinya.
                2.  Hampir semua struk belanja, pembayaran, atau tagihan adalah 'expense' (pengeluaran).
                3.  Hanya jika struk tersebut adalah bukti penerimaan gaji, bonus, atau pemasukan lain, gunakan 'income'.
                4.  Jika ragu, defaultnya adalah 'expense'.

                Aturan untuk `total_amount`:
                1.  Cari kata kunci penanda total seperti 'TOTAL', 'TOTAL BAYAR', 'TAGIHAN'. Angka yang paling dekat dengan kata kunci ini adalah prioritas utama.
                2.  Abaikan angka yang berhubungan dengan 'TUNAI', 'CASH', 'KEMBALI', atau 'CHANGE'.
                3.  Hasil akhir HARUS berupa angka integer (bulat) tanpa titik, koma, atau simbol mata uang.
                4.  Jika tidak bisa menemukan total yang valid, kembalikan nilai `null`.

                Aturan untuk `transaction_date`:
                1.  Cari tanggal dalam format apapun (DD-MM-YYYY, DD/MM/YY, dll) dan ubah ke format YYYY-MM-DD.
                2.  Jika tidak ada tanggal, kembalikan `null`.

                Aturan untuk `category`:
                1.  Berdasarkan item-item belanja atau nama toko (misal: 'INDOMARET', 'ALFAMART', 'SUPERINDO', 'PLN', 'GOJEK'), tentukan satu kategori yang paling relevan.
                2.  Contoh kategori: 'Kebutuhan Harian', 'Makanan & Minuman', 'Transportasi', 'Tagihan', 'Restoran', 'Elektronik'.
                3.  Jika tidak bisa menentukan kategori, kembalikan `null`.

                Jawab HANYA dengan format JSON yang valid. Pastikan semua field (`type`, `total_amount`, `transaction_date`, `category`) ada di dalam JSON.

                Teks struknya adalah:
                \n\n" . $rawText;

            $groqResponse = \Illuminate\Support\Facades\Http::withToken(
                $groqApiKey,
            )
                ->withHeaders(["Content-Type" => "application/json"])
                ->post("https://api.groq.com/openai/v1/chat/completions", [
                    "model" => "llama3-8b-8192", // Pakai model yg lebih kecil & cepat untuk efisiensi
                    "messages" => [
                        ["role" => "user", "content" => $promptForGroq],
                    ],
                    "response_format" => ["type" => "json_object"],
                    "temperature" => 0.1,
                ]);

            $groqResult = $groqResponse->json();

            if (
                $groqResponse->successful() &&
                isset($groqResult["choices"][0]["message"]["content"])
            ) {
                $contentJson = $groqResult["choices"][0]["message"]["content"];
                $extractedData = json_decode($contentJson, true);

                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    isset($extractedData["total_amount"])
                ) {
                    // TAMBAHKAN VALIDASI INI DI DALAMNYA
                    if (
                        empty($extractedData["total_amount"]) ||
                        ! is_numeric($extractedData["total_amount"]) ||
                        $extractedData["total_amount"] <= 0
                    ) {
                        throw new \Exception(
                            "AI gagal mengekstrak total yang valid dari struk.",
                        );
                    }

                    $this->type = $extractedData["type"] ?? "expense";
                    $this->amount = $extractedData["total_amount"];
                    $this->transaction_date =
                        $extractedData["transaction_date"] ??
                        now()->format("Y-m-d");
                    $this->category = $extractedData["category"] ?? null;
                    $this->dispatch("ocr-completed", amount: $this->amount);
                    $this->scanStatusMessage = "Struk berhasil dipindai!";
                    $this->scanStatusType = "success";
                } else {
                    throw new \Exception("Groq gagal mem-parsing teks OCR.");
                }
            } else {
                throw new \Exception(
                    $groqResult["error"]["message"] ??
                        "Gagal menganalisis teks dengan Groq.",
                );
            }
        } catch (\Exception $e) {
            $this->scanStatusMessage = "Error: " . $e->getMessage();
            $this->scanStatusType = "error";
        } finally {
            $this->reset("receiptImage");
            $this->dispatch("scan-message-received");
        }
    }

    // Proses transaksi aset
    private function processAssetTransaction()
    {
        $this->scanStatusMessage = "Membaca screenshot transaksi...";
        $this->scanStatusType = "info";

        try {
            // === LANGKAH 1: OCR (Tidak ada perubahan) ===
            $ocrApiKey = config("services.ocrspace.api_key");
            if (! $ocrApiKey) {
                throw new \Exception("Kunci API OCR.space belum diatur");
            }
            $ocrResponse = \Illuminate\Support\Facades\Http::withHeaders([
                "apikey" => $ocrApiKey,
            ])
                ->attach(
                    "file",
                    file_get_contents($this->receiptImage->getRealPath()),
                    $this->receiptImage->getClientOriginalName(),
                )
                ->post("https://api.ocr.space/parse/image", [
                    "language" => "eng",
                ]);
            $ocrResult = $ocrResponse->json();
            if (
                ! $ocrResponse->successful() ||
                $ocrResult["IsErroredOnProcessing"]
            ) {
                throw new \Exception(
                    $ocrResult["ErrorMessage"][0] ?? "Gagal memproses gambar.",
                );
            }
            $rawText = $ocrResult["ParsedResults"][0]["ParsedText"];
            if (empty(trim($rawText))) {
                throw new \Exception("OCR tidak menemukan teks.");
            }

            // === LANGKAH 2: Groq Menganalisis dengan Prompt Final ===
            $this->scanStatusMessage = "Menganalisis data transaksi...";
            $groqApiKey = config("services.groq.api_key");
            if (! $groqApiKey) {
                throw new \Exception("Kunci API Groq belum diatur");
            }

            $promptForAsset =
                "Anda adalah analis trading kripto yang sangat akurat. Analisis teks dari riwayat transaksi exchange dan ekstrak data mentah ke format JSON.

                Aturan `type`:
                - Tentukan 'buy' jika ada kata 'Beli' atau 'Buy'.
                - Tentukan 'sell' jika ada kata 'Jual' atau 'Sell'.

                Aturan `asset_symbol`:
                - Ekstrak simbol aset, contoh: 'BTC', 'ETH', 'USDT'.

                Aturan `transaction_date`:
                - Cari tanggal di teks. Ubah ke format **YYYY-MM-DD**. Abaikan jam. Contoh: '30 August 2025 09:12' menjadi `2025-08-30`. Jika tidak ada, kembalikan `null`.

                Aturan `price_per_unit`:
                - **PENTING:** Ini adalah harga aset UNTUK 1 UNIT PENUH dalam IDR. Biasanya ini adalah ANGKA TERBESAR. Cari di dekat kata kunci 'Harga', 'Price', atau 'Coin Price'.
                - Ekstrak hanya angkanya. Contoh: '1.787.702.000 IDR / 1 BTC' menjadi `1787702000`.

                Aturan `total_idr_amount`:
                - **PENTING:** Ini adalah jumlah uang Rupiah SEBELUM fee dipotong. Cari angka di dekat kata kunci 'Purchase Amount', 'Amount', atau 'Subtotal'. **JANGAN GUNAKAN 'Total in IDR'**.
                - Contoh: 'Purchase Amount 101.425 IDR' menjadi `101425`.

                Aturan `fee_percentage`:
                - **HITUNG PERSENTASENYA:** Cari nilai 'Service Fee' (contoh: 224 IDR) dan 'Purchase Amount' (contoh: 101425 IDR).
                - Hitung persentasenya dengan rumus: (fee / purchase_amount) * 100.
                - Kembalikan hasilnya sebagai angka desimal. Contoh: (224 / 101425) * 100 menjadi `0.22`.
                - Jika tidak ada fee, kembalikan `0`.

                Jawab HANYA dengan format JSON yang valid.
                Contoh Jawaban: {\"type\":\"buy\", \"asset_symbol\":\"BTC\", \"transaction_date\":\"2025-08-30\", \"price_per_unit\":1787702000, \"total_idr_amount\":101425, \"fee_percentage\":0.22}

                Teksnya adalah:
                \n\n" . $rawText;

            $groqResponse = \Illuminate\Support\Facades\Http::withToken(
                $groqApiKey,
            )
                ->withHeaders(["Content-Type" => "application/json"])
                ->post("https://api.groq.com/openai/v1/chat/completions", [
                    "model" => "llama3-8b-8192",
                    "messages" => [
                        ["role" => "user", "content" => $promptForAsset],
                    ],
                    "response_format" => ["type" => "json_object"],
                    "temperature" => 0.0,
                ]);

            $groqResult = $groqResponse->json();

            if (
                ! $groqResponse->successful() ||
                ! isset($groqResult["choices"][0]["message"]["content"])
            ) {
                throw new \Exception(
                    $groqResult["error"]["message"] ??
                        "Gagal menganalisis teks.",
                );
            }

            // === LANGKAH 3: Kalkulasi Final di Backend ===
            $contentJson = $groqResult["choices"][0]["message"]["content"];
            $data = json_decode($contentJson, true);

            if (
                json_last_error() !== JSON_ERROR_NONE ||
                empty($data["asset_symbol"]) ||
                empty($data["price_per_unit"])
            ) {
                throw new \Exception(
                    "AI gagal mengekstrak data aset yang valid. Coba lagi.",
                );
            }

            $asset = \App\Models\Asset::where(
                "symbol",
                strtoupper($data["asset_symbol"]),
            )->first();
            if (! $asset) {
                throw new \Exception(
                    "Aset dengan simbol '{$data["asset_symbol"]}' tidak ditemukan di database Anda.",
                );
            }

            // Isi field yang diekstrak
            $this->type = $data["type"] ?? "buy";
            $this->asset_id = $asset->id;
            $this->amount = $data["total_idr_amount"] ?? 0;
            $this->price_per_unit = $data["price_per_unit"] ?? 0;
            $this->transaction_date = $data["transaction_date"] ?? null;

            // Menggunakan fee_percentage dari AI untuk mengisi semua field terkait
            $this->fee_percentage = $data["fee_percentage"] ?? 0;

            $totalAmount = (float) $this->amount;
            $pricePerUnit = (float) $this->price_per_unit;

            // Hitung fee_amount & quantity di backend agar konsisten
            $this->fee_amount = $totalAmount * ($this->fee_percentage / 100);

            if ($pricePerUnit > 0) {
                $finalAmountForQuantity = $totalAmount - $this->fee_amount;
                $this->quantity = $finalAmountForQuantity / $pricePerUnit;
            }

            // Kirim event untuk update amount yang pakai x-mask
            $this->dispatch("ocr-completed", amount: $this->amount);
            $this->scanStatusMessage = "Transaksi aset berhasil dipindai!";
            $this->scanStatusType = "success";
        } catch (\Exception $e) {
            $this->scanStatusMessage = "Error: " . $e->getMessage();
            $this->scanStatusType = "error";
        } finally {
            $this->reset("receiptImage");
            $this->dispatch("scan-message-received");
        }
    }

    // Updated edit receipt image
    public function updatedEditReceiptImage()
    {
        $this->validate(["editReceiptImage" => "required|image|max:4096"]);

        if ($this->scanContext === "asset") {
            $this->processAssetTransactionForEdit();
        } else {
            $this->processExpenseReceiptForEdit();
        }
    }

    // Proses edit struk biasa
    private function processExpenseReceiptForEdit()
    {
        $this->scanStatusMessage = "Menganalisis Struk...";
        $this->scanStatusType = "info";

        try {
            $imageFile = $this->editReceiptImage;
            $ocrApiKey = config("services.ocrspace.api_key");
            if (! $ocrApiKey) {
                throw new \Exception("Kunci API OCR.space belum diatur");
            }

            $ocrResponse = \Illuminate\Support\Facades\Http::withHeaders([
                "apikey" => $ocrApiKey,
            ])
                ->attach(
                    "file",
                    file_get_contents($imageFile->getRealPath()),
                    $imageFile->getClientOriginalName(),
                )
                ->post("https://api.ocr.space/parse/image", [
                    "language" => "eng",
                ]);
            $ocrResult = $ocrResponse->json();
            if (
                ! $ocrResponse->successful() ||
                $ocrResult["IsErroredOnProcessing"]
            ) {
                throw new \Exception(
                    $ocrResult["ErrorMessage"][0] ?? "Gagal memproses gambar.",
                );
            }

            $rawText = $ocrResult["ParsedResults"][0]["ParsedText"];
            if (empty(trim($rawText))) {
                throw new \Exception("OCR tidak menemukan teks.");
            }

            $this->scanStatusMessage = "Menganalisis teks...";
            $groqApiKey = config("services.groq.api_key");
            if (! $groqApiKey) {
                throw new \Exception("Kunci API Groq belum diatur");
            }

            // PROMPT LENGKAP UNTUK EXPENSE
            $promptForGroq =
                "Anda adalah seorang analis data keuangan yang sangat teliti dan cerdas. Tugas Anda adalah menganalisis teks hasil OCR dari sebuah struk dan mengekstrak data secara akurat.

                Aturan `type`:
                1.  Hampir semua struk belanja, pembayaran, atau tagihan adalah 'expense'.
                2.  Jika ragu, defaultnya adalah 'expense'.

                Aturan `total_amount`:
                1.  Cari kata kunci total seperti 'TOTAL', 'TOTAL BAYAR', 'TAGIHAN'.
                2.  Abaikan angka yang berhubungan dengan 'TUNAI', 'KEMBALI'.
                3.  Hasil akhir HARUS berupa angka integer.
                4.  Jika tidak valid, kembalikan `null`.

                Aturan `transaction_date`:
                1.  Cari tanggal dan ubah ke format YYYY-MM-DD.
                2.  Jika tidak ada, kembalikan `null`.

                Aturan `category`:
                1.  Berdasarkan nama toko atau item, tentukan satu kategori relevan (Contoh: 'Kebutuhan Harian', 'Makanan & Minuman', 'Transportasi', 'Tagihan').
                2.  Jika tidak bisa, kembalikan `null`.

                Jawab HANYA dalam format json.

                Teks struknya adalah:
                \n\n" . $rawText;

            $groqResponse = \Illuminate\Support\Facades\Http::withToken(
                $groqApiKey,
            )
                ->withHeaders(["Content-Type" => "application/json"])
                ->post("https://api.groq.com/openai/v1/chat/completions", [
                    "model" => "llama3-8b-8192",
                    "messages" => [
                        ["role" => "user", "content" => $promptForGroq],
                    ],
                    "response_format" => ["type" => "json_object"],
                    "temperature" => 0.1,
                ]);
            $groqResult = $groqResponse->json();

            if (
                $groqResponse->successful() &&
                isset($groqResult["choices"][0]["message"]["content"])
            ) {
                $extractedData = json_decode(
                    $groqResult["choices"][0]["message"]["content"],
                    true,
                );
                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    isset($extractedData["total_amount"])
                ) {
                    // MENGISI PROPERTI EDIT
                    $this->edit_type = $extractedData["type"] ?? "expense";
                    $this->edit_amount = number_format(
                        $extractedData["total_amount"],
                        0,
                        ",",
                        ".",
                    );
                    $this->edit_category = $extractedData["category"] ?? null;
                    $this->edit_transaction_date = ! empty(
                        $extractedData["transaction_date"]
                    )
                        ? \Carbon\Carbon::parse(
                            $extractedData["transaction_date"],
                        )->format("Y-m-d")
                        : now()->format("Y-m-d");
                    $this->scanStatusMessage = "Berhasil memperbarui data!";
                    $this->scanStatusType = "success";
                } else {
                    throw new \Exception("Groq gagal mem-parsing teks OCR.");
                }
            } else {
                throw new \Exception(
                    $groqResult["error"]["message"] ??
                        "Gagal menganalisis teks.",
                );
            }
        } catch (\Exception $e) {
            $this->scanStatusMessage = "Error: " . $e->getMessage();
            $this->scanStatusType = "error";
        } finally {
            $this->reset("editReceiptImage");
            $this->dispatch("scan-message-received");
        }
    }

    // Proses edit transaksi aset
    private function processAssetTransactionForEdit()
    {
        $this->scanStatusMessage = "Menganalisis Transaksi...";
        $this->scanStatusType = "info";
        try {
            $imageFile = $this->editReceiptImage;
            $ocrApiKey = config("services.ocrspace.api_key");
            if (! $ocrApiKey) {
                throw new \Exception("Kunci API OCR.space belum diatur");
            }
            $ocrResponse = \Illuminate\Support\Facades\Http::withHeaders([
                "apikey" => $ocrApiKey,
            ])
                ->attach(
                    "file",
                    file_get_contents($imageFile->getRealPath()),
                    $imageFile->getClientOriginalName(),
                )
                ->post("https://api.ocr.space/parse/image", [
                    "language" => "eng",
                ]);
            $ocrResult = $ocrResponse->json();
            if (
                ! $ocrResponse->successful() ||
                $ocrResult["IsErroredOnProcessing"]
            ) {
                throw new \Exception(
                    $ocrResult["ErrorMessage"][0] ?? "Gagal memproses gambar.",
                );
            }
            $rawText = $ocrResult["ParsedResults"][0]["ParsedText"];
            if (empty(trim($rawText))) {
                throw new \Exception("OCR tidak menemukan teks.");
            }

            $this->scanStatusMessage = "Menganalisis data...";
            $groqApiKey = config("services.groq.api_key");
            if (! $groqApiKey) {
                throw new \Exception("Kunci API Groq belum diatur");
            }

            // PROMPT LENGKAP UNTUK ASSET
            $promptForAsset =
                "Anda adalah analis trading kripto yang sangat akurat. Analisis teks dari riwayat transaksi exchange dan ekstrak data mentah.

                Aturan `type`:
                - Tentukan 'buy' untuk 'Beli' atau 'Buy'.
                - Tentukan 'sell' untuk 'Jual' atau 'Sell'.

                Aturan `asset_symbol`:
                - Ekstrak simbol aset, contoh: 'BTC', 'ETH'.

                Aturan `transaction_date`:
                - Cari tanggal di teks. Ubah ke format **YYYY-MM-DD**. Abaikan jam. Contoh: '30 August 2025 09:12' menjadi `2025-08-30`. Jika tidak ada, kembalikan `null`.

                Aturan `price_per_unit`:
                - **PENTING:** Ini harga UNTUK 1 UNIT PENUH, biasanya ANGKA TERBESAR. Cari di dekat 'Harga', 'Price', atau 'Coin Price'.
                - Ekstrak hanya angkanya. Contoh: '1.787.702.000 IDR' menjadi `1787702000`.

                Aturan `total_idr_amount`:
                - **PENTING:** Ini jumlah uang SEBELUM fee. Cari di dekat 'Purchase Amount' atau 'Subtotal'. **JANGAN GUNAKAN 'Total in IDR'**.
                - Contoh: 'Purchase Amount 101.425 IDR' menjadi `101425`.

                Aturan `fee_percentage`:
                - **HITUNG PERSENTASENYA:** Cari 'Service Fee' (contoh: 224) dan 'Purchase Amount' (contoh: 101425).
                - Hitung dengan rumus: (fee / purchase_amount) * 100.
                - Kembalikan sebagai angka desimal. Contoh: `0.22`.

                Contoh Jawaban: {\"type\":\"buy\", \"asset_symbol\":\"BTC\", \"transaction_date\":\"2025-08-30\", \"price_per_unit\":1787702000, \"total_idr_amount\":101425, \"fee_percentage\":0.22}

                Jawab HANYA dalam format json.

                Teksnya adalah:
                \n\n" . $rawText;

            $groqResponse = \Illuminate\Support\Facades\Http::withToken(
                $groqApiKey,
            )
                ->withHeaders(["Content-Type" => "application/json"])
                ->post("https://api.groq.com/openai/v1/chat/completions", [
                    "model" => "llama3-8b-8192",
                    "messages" => [
                        ["role" => "user", "content" => $promptForAsset],
                    ],
                    "response_format" => ["type" => "json_object"],
                    "temperature" => 0.0,
                ]);
            $groqResult = $groqResponse->json();
            if (
                ! $groqResponse->successful() ||
                ! isset($groqResult["choices"][0]["message"]["content"])
            ) {
                throw new \Exception(
                    $groqResult["error"]["message"] ??
                        "Gagal menganalisis teks.",
                );
            }

            $data = json_decode(
                $groqResult["choices"][0]["message"]["content"],
                true,
            );
            if (
                json_last_error() !== JSON_ERROR_NONE ||
                empty($data["asset_symbol"])
            ) {
                throw new \Exception("AI gagal ekstrak data.");
            }
            $asset = \App\Models\Asset::where(
                "symbol",
                strtoupper($data["asset_symbol"]),
            )->first();
            if (! $asset) {
                throw new \Exception(
                    "Aset '{$data["asset_symbol"]}' tidak ditemukan.",
                );
            }

            // MENGISI PROPERTI FORM EDIT
            $this->edit_type = $data["type"] ?? "buy";
            $this->edit_asset_id = $asset->id;
            $this->edit_amount = number_format(
                $data["total_idr_amount"] ?? 0,
                0,
                ",",
                ".",
            );
            $this->edit_price_per_unit = number_format(
                $data["price_per_unit"] ?? 0,
                0,
                ",",
                ".",
            );
            $this->edit_transaction_date = $data["transaction_date"] ?? null;
            $this->edit_fee_percentage = $data["fee_percentage"] ?? 0;

            $totalAmount = (float) str_replace(".", "", $this->edit_amount);
            $pricePerUnit = (float) str_replace(
                ".",
                "",
                $this->edit_price_per_unit,
            );

            $this->edit_fee_amount =
                $totalAmount * ($this->edit_fee_percentage / 100);
            if ($pricePerUnit > 0) {
                $finalAmountForQuantity = $totalAmount - $this->edit_fee_amount;
                $this->edit_quantity = $finalAmountForQuantity / $pricePerUnit;
            }
            $this->scanStatusMessage = "Berhasil memperbarui data!";
            $this->scanStatusType = "success";
        } catch (\Exception $e) {
            $this->scanStatusMessage = "Error: " . $e->getMessage();
            $this->scanStatusType = "error";
        } finally {
            $this->reset("editReceiptImage");
            $this->dispatch("scan-message-received");
        }
    }

    // Modal Add
    public function prepareToAdd(): void
    {
        $this->showAddModal = true;
    }

    // addTransaction
    public function addTransaction(): void
    {
        // Membersihkan nilai
        $this->amount = $this->amount
            ? str_replace(".", "", $this->amount)
            : null;
        $this->price_per_unit = $this->price_per_unit
            ? str_replace(".", "", $this->price_per_unit)
            : null;
        $this->quantity = $this->quantity
            ? str_replace(",", ".", $this->quantity)
            : null;

        $validated = $this->validate([
            "type" => "required|in:buy,sell,income,expense",
            "amount" => "required|numeric|min:0",
            "transaction_date" => "required|date_format:Y-m-d",
            "category" =>
                "required_if:type,income,expense|nullable|string|max:255",
            "notes" => "nullable|string",
            "asset_id" => "required_if:type,buy,sell|nullable|exists:assets,id",
            "quantity" => "required_if:type,buy,sell|nullable|numeric|min:0",
            "price_per_unit" =>
                "required_if:type,buy,sell|nullable|numeric|min:0",
        ]);

        FinancialEntry::create($validated + ["user_id" => Auth::id()]);

        $this->reset(
            "type",
            "asset_id",
            "quantity",
            "price_per_unit",
            "amount",
            "fee_percentage",
            "fee_amount",
            "category",
            "notes",
            "transaction_date",
            "editing",
        );
        $this->type = "income";
        session()->flash("message", "Transaksi berhasil ditambahkan.");
        $this->showAddModal = false;
    }

    // Inline edit
    public function editField($transactionId, $field)
    {
        $transaction = FinancialEntry::find($transactionId);
        if (! $transaction) {
            return;
        }

        $this->editingId = $transactionId;
        $this->editingField = $field;

        // Format nilai untuk ditampilkan di input
        if ($field === "amount") {
            $this->editingValue = number_format(
                $transaction->amount ?? 0,
                0,
                ",",
                ".",
            );
        } elseif ($field === "quantity") {
            // Gunakan number_format dengan . sebagai pemisah desimal agar cocok dengan x-mask
            $this->editingValue = number_format(
                $transaction->quantity,
                8,
                ".",
                ",",
            );
        } elseif ($field === "transaction_date") {
            // Ketika mengedit tanggal inline, gunakan transaction_date dari model, jika null gunakan updated_at
            $dateToDisplay =
                $transaction->transaction_date ?? $transaction->updated_at;
            $this->editingValue = $dateToDisplay->format("Y-m-d");
        } else {
            $this->editingValue = $transaction->$field;
        }
    }

    // Inline save
    public function saveField()
    {
        if ($this->editingId === null || $this->editingField === null) {
            return;
        }

        $transaction = FinancialEntry::find($this->editingId);
        if (! $transaction) {
            return;
        }

        $rules = [];
        $cleanedValue = $this->editingValue;

        if ($this->editingField === "amount") {
            $cleanedValue = str_replace(".", "", $this->editingValue);
            $rules["editingValue"] = "required|min:0";
        } elseif ($this->editingField === "quantity") {
            $cleanedValue = str_replace(",", ".", $this->editingValue);
            $rules["editingValue"] = "required|min:0";
        } elseif ($this->editingField === "category") {
            $rules["editingValue"] = "required|string|max:255";
        } elseif ($this->editingField === "transaction_date") {
            // Aturan untuk inline edit tanggal
            $rules["editingValue"] = "required|date_format:Y-m-d";
        }

        $this->validate($rules);

        // Laravel akan otomatis mendeteksi perubahan berkat $casts di model
        $transaction->update([
            $this->editingField => $cleanedValue,
        ]);

        // **TAMBAHKAN INI:** Set properti untuk menampilkan pesan
        // $this->savedMessageId = $this->editingId;
        // $this->savedMessageField = $this->editingField;

        $this->cancelEdit();
        // session()->flash('message', 'Data berhasil diperbarui.');
    }

    // Inline cancel
    public function cancelEdit()
    {
        $this->reset("editingId", "editingField", "editingValue");
    }

    // Modal Edit
    public function prepareToEdit(FinancialEntry $entry): void
    {
        $this->editing = $entry;

        // Populate EDIT form properties with raw values from DB
        $this->edit_type = $entry->type;
        $this->edit_asset_id = $entry->asset_id;
        $this->edit_quantity = $entry->quantity;
        $this->edit_price_per_unit = $entry->price_per_unit;
        $this->edit_amount = $entry->amount;
        $this->edit_category = $entry->category;
        $this->edit_notes = $entry->notes;

        // Logika untuk tanggal
        $dateForEdit = $entry->transaction_date ?? $entry->updated_at;
        $this->edit_transaction_date = $dateForEdit->format("Y-m-d");

        // --- Logika Krusial untuk Fee dan Quantity ---
        if ($this->edit_type === "buy" || $this->edit_type === "sell") {
            // Cukup reset biar nggak error
            $this->edit_fee_percentage = $entry->fee_percentage ?? 0;
            $this->edit_fee_amount = 0; // hanya tampilan, Alpine yg isi ulang
        } else {
            // Jika bukan buy/sell, reset nilai fee
            $this->edit_fee_percentage = 0;
            $this->edit_fee_amount = 0;
        }

        // Format untuk tampilan input (jika menggunakan x-mask atau number_format)
        $this->edit_price_per_unit = number_format(
            $this->edit_price_per_unit ?? 0,
            0,
            ",",
            ".",
        );
        $this->edit_amount = number_format(
            $this->edit_amount ?? 0,
            0,
            ",",
            ".",
        );

        $this->showEditModal = true;
    }

    // updateTransaction
    public function updateTransaction(): void
    {
        if (! $this->editing) {
            return;
        }

        // Clean EDIT form values
        $editAmount = str_replace(".", "", $this->edit_amount);
        $editPricePerUnit = str_replace(".", "", $this->edit_price_per_unit);
        $editQuantity = str_replace(",", ".", $this->edit_quantity);

        // Prepare data for validation
        $dataToValidate = [
            "type" => $this->edit_type,
            "amount" => (float) $editAmount,
            "transaction_date" => $this->edit_transaction_date,
            "category" => $this->edit_category,
            "notes" => $this->edit_notes,
            "asset_id" => $this->edit_asset_id,
            "quantity" =>
                $this->edit_type === "buy" || $this->edit_type === "sell"
                    ? (float) $editQuantity
                    : null,
            "price_per_unit" =>
                $this->edit_type === "buy" || $this->edit_type === "sell"
                    ? (float) $editPricePerUnit
                    : null,
        ];

        // Define validation rules
        $rules = [
            "type" => "required|in:buy,sell,income,expense",
            "amount" => "required|numeric|min:0",
            "transaction_date" => "required|date_format:Y-m-d",
            "category" =>
                "required_if:type,income,expense|nullable|string|max:255",
            "notes" => "nullable|string",
            "asset_id" => "required_if:type,buy,sell|nullable|exists:assets,id",
            "quantity" => "required_if:type,buy,sell|nullable|numeric|min:0",
            "price_per_unit" =>
                "required_if:type,buy,sell|nullable|numeric|min:0",
        ];

        // Validate the data and update the model
        $validatedData = validator($dataToValidate, $rules)->validate();

        // Pastikan nilai null untuk asset_id, quantity, dan price_per_unit jika tipenya bukan buy/sell
        if (! in_array($validatedData["type"], ["buy", "sell"])) {
            $validatedData["asset_id"] = null;
            $validatedData["quantity"] = null;
            $validatedData["price_per_unit"] = null;
        }

        // Eloquent akan secara otomatis mendeteksi apakah 'transaction_date' benar-benar berubah
        // (setelah di-cast menjadi Carbon dan dibandingkan)
        $this->editing->update($validatedData);

        session()->flash("message", "Transaksi berhasil diperbarui.");
        $this->showEditModal = false;
    }

    // Modal Delete
    public function prepareToDelete(FinancialEntry $entry): void
    {
        $this->deleting = $entry;
        $this->showDeleteModal = true;
    }

    // deleteTransaction
    public function deleteTransaction(): void
    {
        if ($this->deleting) {
            $this->deleting->delete();
            session()->flash("message", "Transaksi berhasil dihapus.");
        }
        $this->showDeleteModal = false;
    }

    public function with(): array
    {
        $query = FinancialEntry::with("asset")
            ->where("user_id", Auth::id())
            // Urutkan berdasarkan transaction_date, dengan fallback ke updated_at jika transaction_date null
            ->orderByRaw("COALESCE(transaction_date, updated_at) DESC");

        if ($this->filterType) {
            $query->where("type", $this->filterType);
        }

        if ($this->filterAsset) {
            $query->where("asset_id", $this->filterAsset);
        }

        if ($this->search) {
            // Cari di category DAN asset name
            $query->where(function ($q) {
                $q->where(
                    "category",
                    "like",
                    "%" . $this->search . "%",
                )->orWhereHas("asset", function ($sq) {
                    $sq->where("name", "like", "%" . $this->search . "%");
                });
            });
        }

        if ($this->filterDate) {
            // Filter berdasarkan transaction_date, dengan fallback ke updated_at jika transaction_date null
            $query->where(function ($q) {
                $q->whereDate("transaction_date", $this->filterDate)->orWhere(
                    function ($sq) {
                        $sq->whereNull("transaction_date")->whereDate(
                            "updated_at",
                            $this->filterDate,
                        );
                    },
                );
            });
        }

        return [
            "transactions" => $query->paginate(10),
            "assets" => Asset::all(),
        ];
    }
}; ?>

<div>
    <!-- Page Content -->
    <div class="mb-6 flex flex-col justify-between md:flex-row md:items-center">
        <h1 class="text-3xl font-bold text-white">Riwayat Transaksi</h1>
        <button
            wire:click="prepareToAdd"
            class="mt-4 flex cursor-pointer items-center gap-2 rounded-lg bg-sky-500 px-4 py-2 font-semibold text-white transition-colors hover:bg-sky-600 md:mt-0"
        >
            <x-icon name="lucide.plus-circle" class="h-5 w-5" />
            Tambah Transaksi
        </button>
    </div>

    <!-- Filters -->
    <div class="card mb-6 p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <input
                type="text"
                wire:model.live="search"
                placeholder="Cari berdasarkan kategori..."
                class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
            />
            <select
                wire:model.live="filterType"
                class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
            >
                <option value="">Semua Tipe</option>
                <option value="buy">Beli</option>
                <option value="sell">Jual</option>
                <option value="income">Pemasukan</option>
                <option value="expense">Pengeluaran</option>
            </select>
            <select
                wire:model.live="filterAsset"
                class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
            >
                <option value="">Semua Aset</option>
                @foreach ($assets as $asset)
                    <option value="{{ $asset->id }}">
                        {{ $asset->name }}
                    </option>
                @endforeach
            </select>
            <input
                type="date"
                wire:model.live="filterDate"
                class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500"
            />
        </div>
    </div>

    <x-notification />

    <!-- Transactions Table -->
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Aset/Kategori</th>
                        <th>Jumlah</th>
                        <th>Nilai (IDR)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr wire:key="{{ $transaction->id }}">
                            <td class="truncate">
                                {{-- Inline edit untuk tanggal --}}

                                @if ($editingId === $transaction->id && $editingField === "transaction_date")
                                    <input
                                        type="date"
                                        wire:model="editingValue"
                                        wire:keydown.enter="saveField"
                                        wire:keydown.escape="cancelEdit"
                                        class="form-input text-sm p-1 **w-full**"
                                        x-init="$nextTick(() => $el.focus())"
                                        x-ref="editInput{{ $transaction->id }}_transaction_date"
                                        @click.away="$wire.cancelEdit()"
                                        x-trap.noscroll
                                    />
                                @else
                                    <p
                                        wire:click="editField({{ $transaction->id }}, 'transaction_date')"
                                        class="text-slate-300 cursor-pointer hover:bg-slate-700 p-1 rounded"
                                    >
                                        {{ ($transaction->transaction_date ?? $transaction->updated_at)->format("d M Y") }}
                                    </p>
                                @endif
                            </td>
                            <td class="truncate">
                                @if ($transaction->type == "buy")
                                    <p class="font-semibold text-green-400">
                                        Beli
                                    </p>
                                @elseif ($transaction->type == "sell")
                                    <p class="font-semibold text-red-400">
                                        Jual
                                    </p>
                                @elseif ($transaction->type == "income")
                                    <p class="font-semibold text-sky-400">
                                        Pemasukan
                                    </p>
                                @else
                                    <p class="font-semibold text-orange-400">
                                        Pengeluaran
                                    </p>
                                @endif
                            </td>
                            <td class="truncate">
                                <p class="font-semibold text-white p-1 rounded">
                                    {{ Str::title($transaction->asset->name ?? $transaction->category) }}
                                </p>
                            </td>
                            <td class="truncate">
                                @if ($transaction->quantity && $transaction->asset)
                                    @if ($editingId === $transaction->id && $editingField === "quantity")
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            x-mask:dynamic="'9.99999999'"
                                            wire:model="editingValue"
                                            wire:keydown.enter="saveField"
                                            wire:keydown.escape="cancelEdit"
                                            class="form-input text-sm p-1 w-full"
                                            x-init="$nextTick(() => $el.focus())"
                                            x-ref="editInput{{ $transaction->id }}_quantity"
                                            @click.away="$wire.cancelEdit()"
                                            x-trap.noscroll
                                        />
                                    @else
                                        <p
                                            wire:click="editField({{ $transaction->id }}, 'quantity')"
                                            class="font-semibold text-white cursor-pointer hover:bg-slate-700 p-1 rounded"
                                        >
                                            {{ rtrim(rtrim(number_format($transaction->quantity, 8, ".", ","), "0"), ".") }}
                                            {{ $transaction->asset->symbol }}
                                        </p>
                                    @endif
                                @else
                                        -
                                @endif
                            </td>
                            <td class="truncate">
                                @if ($editingId === $transaction->id && $editingField === "amount")
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        x-mask:dynamic="$money($input, ',')"
                                        wire:model="editingValue"
                                        wire:keydown.enter="saveField"
                                        wire:keydown.escape="cancelEdit"
                                        class="form-input text-sm p-1"
                                        x-init="$nextTick(() => $el.focus())"
                                        x-ref="editInput{{ $transaction->id }}_amount"
                                        @click.away="$wire.cancelEdit()"
                                        x-trap.noscroll
                                    />
                                @else
                                    <p
                                        wire:click="editField({{ $transaction->id }}, 'amount')"
                                        class="text-slate-300 cursor-pointer hover:bg-slate-700 p-1 rounded"
                                    >
                                        Rp
                                        {{ number_format($transaction->amount, 0, ",", ".") }}
                                    </p>
                                @endif
                            </td>
                            <td>
                                <div class="flex space-x-4">
                                    <button
                                        x-on:click="$wire.prepareToEdit({{ $transaction->id }})"
                                        class="cursor-pointer text-slate-400 hover:text-sky-400"
                                    >
                                        <x-icon
                                            name="lucide.edit-3"
                                            class="h-5 w-5"
                                        />
                                    </button>
                                    <button
                                        wire:click="prepareToDelete({{ $transaction->id }})"
                                        class="cursor-pointer text-slate-400 hover:text-red-500"
                                    >
                                        <x-icon
                                            name="lucide.trash-2"
                                            class="h-5 w-5"
                                        />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="py-8 text-center text-slate-400"
                            >
                                Tidak ada transaksi yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="border-t border-slate-800 p-4">
            {{ $transactions->links("livewire.tailwind-custom") }}
        </div>
    </div>

    <!-- ===== Add Transaction Modal ===== -->
    <div
        x-data="{ show: @entangle("showAddModal") }"
        x-show="show"
        @keydown.escape.window="show = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black p-4"
        style="background-color: rgba(0, 0, 0, 0.7)"
        x-cloak
    >
        <div
            @click.away="show = false"
            class="card max-h-full w-full max-w-lg overflow-y-auto"
        >
            @include("livewire.partials.transaction-form", ["formType" => "add"])
        </div>
    </div>

    <!-- Edit Modal -->
    <div
        x-data="{ show: @entangle("showEditModal") }"
        x-show="show"
        @keydown.escape.window="show = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black p-4"
        style="background-color: rgba(0, 0, 0, 0.7)"
        x-cloak
    >
        <div
            @click.away="show = false"
            class="card max-h-full w-full max-w-lg overflow-y-auto"
        >
            @include("livewire.partials.transaction-form-edit", ["formType" => "edit"])
        </div>
    </div>

    <!-- ===== Delete Confirmation Modal ===== -->
    <div
        x-data="{ show: @entangle("showDeleteModal") }"
        x-show="show"
        @keydown.escape.window="show = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black p-4"
        style="background-color: rgba(0, 0, 0, 0.7)"
        x-cloak
    >
        <div @click.away="show = false" class="card w-full max-w-md">
            <div class="p-6 text-center md:p-8">
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10"
                >
                    <x-icon
                        name="lucide.trash-2"
                        class="h-8 w-8 text-red-500"
                    />
                </div>
                <h2 class="text-2xl font-bold text-white">Hapus Transaksi?</h2>
                <p class="mt-2 text-slate-400">
                    Apakah Anda yakin ingin menghapus transaksi ini? Tindakan
                    ini tidak dapat dibatalkan.
                </p>
                <div class="mt-6 flex justify-center gap-4">
                    <button
                        type="button"
                        @click="show = false"
                        class="w-full cursor-pointer rounded-lg bg-slate-700 px-6 py-2 font-semibold text-white hover:bg-slate-600"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="deleteTransaction"
                        wire:loading.attr="disabled"
                        wire:target="deleteTransaction"
                        class="w-full cursor-pointer rounded-lg bg-red-600 px-6 py-2 font-semibold text-white hover:bg-red-700"
                    >
                        <div class="flex items-center justify-center">
                            <x-loading
                                wire:loading
                                wire:target="deleteTransaction"
                                class="loading-dots mr-2"
                            />
                            <x-icon
                                name="lucide.trash-2"
                                wire:loading.remove
                                wire:target="deleteTransaction"
                                class="mr-2"
                            />
                            Ya, Hapus
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
