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

    // Properti untuk umpan balik inline edit
    // public $savedMessageId = null;
    // public $savedMessageField = null;

    // Metode ini akan berjalan otomatis saat struk diunggah
    public function updatedReceiptImage()
    {
        $this->validate([
            "receiptImage" => "required|image|max:4096",
        ]);

        try {
            $apiKey = config("services.ocrspace.api_key");
            if (! $apiKey) {
                throw new \Exception("Kunci API OCR.space belum diatur.");
            }

            // Kirim gambar ke API OCR.space
            $response = Http::withHeaders(["apikey" => $apiKey])
                ->attach(
                    "file",
                    file_get_contents($this->receiptImage->getRealPath()),
                    $this->receiptImage->getClientOriginalName(),
                )
                ->post("https://api.ocr.space/parse/image", [
                    "language" => "eng",
                ]);

            $result = $response->json();

            if ($result && $result["IsErroredOnProcessing"] === false) {
                $fullText = $result["ParsedResults"][0]["ParsedText"];
                $extractedAmount = 0;

                // Logika untuk mencari tanggal
                if (
                    preg_match(
                        "/(\d{1,2}\s\w+\s\d{4})/",
                        $fullText,
                        $dateMatches,
                    )
                ) {
                    $dateString = $dateMatches[1];
                    $date = Carbon::createFromFormat("j F Y", $dateString);
                    $this->transaction_date = $date->format("Y-m-d");
                    // dd($this->transaction_date);
                }

                // FIX: Logika baru yang lebih cerdas untuk mencari total
                // 1. Cari semua angka yang diawali dengan "Rp".
                preg_match_all("/Rp\s*([\d,.]+)/i", $fullText, $matches);

                // 2. Jika ditemukan, ambil angka terakhir.
                if (! empty($matches[1])) {
                    $lastMatch = end($matches[1]);
                    $cleanedAmount = preg_replace("/[^0-9]/", "", $lastMatch);
                    $extractedAmount = (int) $cleanedAmount;
                }

                if ($extractedAmount > 0) {
                    // Kirim event ke browser dengan data yang diekstrak
                    $this->dispatch("ocr-completed", amount: $extractedAmount);
                    $this->scanStatusMessage = "Berhasil dipindai!";
                    $this->scanStatusType = "success";
                } else {
                    $this->scanStatusMessage = "Gagal menemukan total.";
                    $this->scanStatusType = "error";
                }
            } else {
                $this->scanStatusMessage =
                    $result["ErrorMessage"][0] ?? "Gagal memproses gambar.";
                $this->scanStatusType = "error";
            }
        } catch (\Exception $e) {
            $this->scanStatusMessage = "API Error. Coba lagi.";
            $this->scanStatusType = "error";
            Log::error("OCR.space API Error: " . $e->getMessage());
        } finally {
            $this->reset("receiptImage");
            // Kirim event ke browser untuk menghapus pesan setelah beberapa detik
            $this->dispatch("scan-message-received");
        }
    }

    public function prepareToAdd(): void
    {
        $this->showAddModal = true;
    }

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

    public function cancelEdit()
    {
        $this->reset("editingId", "editingField", "editingValue");
    }

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
        // Karena fee dan quantity tidak disimpan, kita hitung ulang saat modal dibuka
        if ($this->edit_type === "buy" || $this->edit_type === "sell") {
            // Beri nilai default untuk fee_percentage karena tidak ada di database
            $this->edit_fee_percentage = 0.1;

            // Bersihkan format nilai sebelum dihitung
            $cleanAmount = (float) str_replace(
                [".", ","],
                ["", "."],
                $this->edit_amount ?? 0,
            );
            $cleanPricePerUnit = (float) str_replace(
                [".", ","],
                ["", "."],
                $this->edit_price_per_unit ?? 0,
            );

            // Hitung fee_amount
            $this->edit_fee_amount =
                $cleanAmount * ($this->edit_fee_percentage / 100);

            // Perbarui quantity agar konsisten
            if ($cleanPricePerUnit > 0) {
                $finalAmount =
                    $this->edit_type === "buy"
                        ? $cleanAmount - $this->edit_fee_amount
                        : $cleanAmount + $this->edit_fee_amount;
                $this->edit_quantity = $finalAmount / $cleanPricePerUnit;
            }
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

    public function prepareToDelete(FinancialEntry $entry): void
    {
        $this->deleting = $entry;
        $this->showDeleteModal = true;
    }

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
