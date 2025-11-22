<?php

use App\Models\Asset;
use App\Models\FinancialEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Properti untuk form tambah/edit transaksi
    public $type = "buy",
        $asset_id = 1,
        $quantity,
        $price_per_unit,
        $transaction_date,
        $total_spent;

    // Properti untuk modal
    public $showAddModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;

    // Properti untuk aksi hapus
    public $assetIdToDelete;
    public ?FinancialEntry $editing = null;

    // Properti ini akan menampung SEMUA data portofolio untuk kalkulasi kartu ringkasan
    public Collection $fullPortfolio;

    public function mount(): void
    {
        $this->calculatePortfolio();
    }

    public function calculatePortfolio(): void
    {
        // FIX: Gunakan Cache::remember() untuk menyimpan hasil API selama 5 menit
        $currentPricePerUnit = Cache::remember(
            "bitcoin_price_idr",
            now()->addMinutes(5),
            function () {
                try {
                    $response = Http::get(
                        "https://api.coingecko.com/api/v3/simple/price",
                        [
                            "ids" => "bitcoin",
                            "vs_currencies" => "idr",
                        ],
                    )->json();
                    return $response["bitcoin"]["idr"] ?? null;
                } catch (\Exception $e) {
                    return null;
                }
            },
        );

        // Gunakan harga statis sebagai fallback
        $currentPricePerUnit = $currentPricePerUnit ?? 1950000000;

        // 1. Ambil SEMUA entri transaksi, bukan hasil paginate
        $entries = FinancialEntry::with("asset")
            ->where("user_id", Auth::id())
            ->whereNotNull("asset_id")
            ->get();

        // Mengelompokkan transaksi berdasarkan aset
        $groupedByAsset = $entries->groupBy("asset_id");

        $portfolioData = $groupedByAsset
            ->map(function ($assetEntries) use ($currentPricePerUnit) {
                $asset = $assetEntries->first()->asset;
                if (! $asset) {
                    return null;
                }

                $totalBuyQuantity = $assetEntries
                    ->where("type", "buy")
                    ->sum("quantity");
                $totalSellQuantity = $assetEntries
                    ->where("type", "sell")
                    ->sum("quantity");
                $totalQuantity = $totalBuyQuantity - $totalSellQuantity;

                if ($totalQuantity <= 0) {
                    return null;
                }

                $totalCost = $assetEntries->where("type", "buy")->sum("amount");
                $totalSellValue = $assetEntries
                    ->where("type", "sell")
                    ->sum("amount");

                // $currentPricePerUnit = 1950000000; // Contoh harga Bitcoin saat ini
                $currentValue = $totalQuantity * $currentPricePerUnit;
                $avgBuyPrice =
                    $totalBuyQuantity > 0 ? $totalCost / $totalBuyQuantity : 0;
                $profitOrLoss = $currentValue + $totalSellValue - $totalCost;
                $profitOrLossPercentage =
                    $totalCost > 0 ? ($profitOrLoss / $totalCost) * 100 : 0;

                // FIX: Ambil ID transaksi terbaru untuk diedit
                $lastTransaction = $assetEntries
                    ->sortByDesc("transaction_date")
                    ->first();

                return (object) [
                    "asset_id" => $asset->id,
                    "last_transaction_id" => $lastTransaction
                        ? $lastTransaction->id
                        : null,
                    "name" => $asset->name,
                    "symbol" => $asset->symbol,
                    "quantity" => $totalQuantity,
                    "avg_buy_price" => $avgBuyPrice,
                    "current_value" => $currentValue,
                    "pnl" => $profitOrLoss,
                    "pnl_percentage" => $profitOrLossPercentage,
                    "total_cost" => $totalCost,
                ];
            })
            ->filter();

        // Simpan data lengkap untuk kartu ringkasan
        $this->fullPortfolio = $portfolioData;
    }

    public function addTransaction(): void
    {
        // Membersihkan nilai yang terformat
        $this->price_per_unit = str_replace(".", "", $this->price_per_unit);
        $this->total_spent = str_replace(".", "", $this->total_spent);
        // Untuk kuantitas, ganti koma desimal ke titik
        $this->quantity = str_replace(",", ".", $this->quantity);
        $this->validate([
            "type" => "required|in:buy,sell",
            "asset_id" => "required|exists:assets,id",
            "quantity" => "required|numeric|min:0",
            "price_per_unit" => "required|numeric|min:0",
            "transaction_date" => "required|date",
            "total_spent" => "required|numeric|min:0",
        ]);

        FinancialEntry::create([
            "user_id" => Auth::id(),
            "type" => $this->type,
            "asset_id" => $this->asset_id,
            "quantity" => $this->quantity,
            "price_per_unit" => $this->price_per_unit,
            "amount" => $this->total_spent, // FIX: Simpan total_spent ke kolom amount
            "transaction_date" => $this->transaction_date,
        ]);

        // Reset form fields
        $this->reset([
            "type",
            "asset_id",
            "quantity",
            "price_per_unit",
            "transaction_date",
            "total_spent",
        ]);
        $this->type = "buy";
        $this->asset_id = 1;

        // Hitung ulang portofolio dan tutup modal
        $this->calculatePortfolio();
        session()->flash("message", "Transaksi berhasil ditambahkan.");
        $this->dispatch("close-add-modal");
    }

    public function prepareToEdit($entryId)
    {
        $this->editing = FinancialEntry::find($entryId);
        if ($this->editing) {
            $this->type = $this->editing->type;
            $this->asset_id = $this->editing->asset_id;
            $this->quantity = $this->editing->quantity;
            $this->price_per_unit = number_format(
                $this->editing->price_per_unit,
                0,
                ",",
                ".",
            );
            $this->total_spent = number_format(
                $this->editing->amount,
                0,
                ",",
                ".",
            );
            $this->transaction_date = $this->editing->transaction_date->format(
                "Y-m-d",
            );

            $this->showEditModal = true;
        }
    }

    public function updateTransaction(): void
    {
        if (! $this->editing) {
            return;
        }

        // Membersihkan nilai yang terformat
        $this->price_per_unit = str_replace(".", "", $this->price_per_unit);
        $this->total_spent = str_replace(".", "", $this->total_spent);
        $this->quantity = str_replace(",", ".", $this->quantity);

        $validated = $this->validate([
            "type" => "required|in:buy,sell",
            "asset_id" => "required|exists:assets,id",
            "quantity" => "required|numeric|min:0",
            "price_per_unit" => "required|numeric|min:0",
            "transaction_date" => "required|date",
            "total_spent" => "required|numeric|min:0",
        ]);

        $this->editing->update([
            "type" => $validated["type"],
            "asset_id" => $validated["asset_id"],
            "quantity" => $validated["quantity"],
            "price_per_unit" => $validated["price_per_unit"],
            "amount" => $validated["total_spent"],
            "transaction_date" => $validated["transaction_date"],
        ]);

        $this->showEditModal = false;
        $this->calculatePortfolio();
        session()->flash("message", "Transaksi berhasil diperbarui.");
    }

    public function prepareToDelete($assetId)
    {
        $this->assetIdToDelete = $assetId;
        $this->showDeleteModal = true;
    }

    public function deleteAssetEntries(): void
    {
        if ($this->assetIdToDelete) {
            FinancialEntry::where("user_id", Auth::id())
                ->where("asset_id", $this->assetIdToDelete)
                ->delete();

            $this->calculatePortfolio(); // Hitung ulang setelah menghapus data
            session()->flash("message", "Data berhasil dihapus.");
            $this->showDeleteModal = false;
        }
    }

    // Fungsi 'with' digunakan untuk melewatkan data ke view, termasuk data yang sudah dipaginasi
    public function with(): array
    {
        // Lakukan pagination di sini, TEPAT SEBELUM data dikirim ke view
        $perPage = 5; // Atur jumlah item per halaman
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $this->fullPortfolio->slice(
            ($currentPage - 1) * $perPage,
            $perPage,
        );

        $paginatedPortfolio = new LengthAwarePaginator(
            $currentPageItems,
            $this->fullPortfolio->count(),
            $perPage,
            $currentPage,
            ["path" => LengthAwarePaginator::resolveCurrentPath()],
        );

        return [
            "portfolio" => $paginatedPortfolio,
            "assets" => Asset::all(), // <-- TAMBAHKAN BARIS INI
        ];
    }
}; ?>

<div wire:poll.60s="calculatePortfolio">
    <!-- Page Content -->
    <div class="mb-6 flex flex-col justify-between md:flex-row md:items-center">
        <h1 class="text-3xl font-bold text-white">Portofolio Aset</h1>
        {{--
            <button
            @click="isAddModalOpen = true"
            class="mt-4 flex cursor-pointer items-center gap-2 rounded-lg bg-sky-500 px-4 py-2 font-semibold text-white transition-colors hover:bg-sky-600 md:mt-0"
            >
            <x-icon name="lucide.plus-circle" class="h-5 w-5" />
            Tambah Transaksi
            </button>
        --}}
    </div>

    <!-- Summary Cards -->
    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="card p-6">
            <h3 class="mb-2 font-medium text-slate-400">
                Nilai Portofolio Saat Ini
            </h3>
            <p class="text-3xl font-bold text-white">
                Rp
                {{ number_format($portfolio->sum("current_value"), 0, ",", ".") }}
            </p>
        </div>
        <div class="card p-6">
            <h3 class="mb-2 font-medium text-slate-400">Total Investasi</h3>
            <p class="text-3xl font-bold text-white">
                Rp
                {{ number_format($portfolio->sum("total_cost"), 0, ",", ".") }}
            </p>
        </div>
        <div class="card p-6">
            <h3 class="mb-2 font-medium text-slate-400">
                Laba/Rugi Portofolio
            </h3>
            <p
                class="text-3xl font-bold {{ $portfolio->sum("pnl") >= 0 ? "text-green-500" : "text-red-500" }}"
            >
                {{ $portfolio->sum("pnl") >= 0 ? "+" : "" }} Rp
                {{ number_format($portfolio->sum("pnl"), 0, ",", ".") }}
            </p>
        </div>
    </div>

    <x-notification />

    <!-- Asset Table -->
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Aset</th>
                        <th>Jumlah</th>
                        <th>Harga Beli Rata-rata</th>
                        <th>Nilai Saat Ini</th>
                        <th>Laba/Rugi</th>
                        {{-- <th>Aksi</th> --}}
                    </tr>
                </thead>
                <tbody>
                    @forelse ($portfolio as $asset)
                        <!-- Contoh Baris Data -->
                        <tr>
                            <td>
                                <div class="flex items-center">
                                    <img
                                        src="https://placehold.co/32x32/FBBF24/000000?text=B"
                                        alt="BTC Logo"
                                        class="mr-4 h-8 w-8 rounded-full"
                                    />
                                    <div>
                                        {{-- FIX: Menggunakan sintaks array --}}
                                        <p class="font-bold text-white">
                                            {{ $asset->name }}
                                        </p>
                                        <p class="text-sm text-slate-400">
                                            {{ $asset->symbol }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="truncate">
                                <p class="font-semibold text-white">
                                    {{ rtrim(rtrim(number_format($asset->quantity, 8, ".", "."), "0"), ",") }}
                                    {{ $asset->symbol }}
                                </p>
                            </td>
                            <td class="truncate">
                                <p class="font-semibold text-white">
                                    Rp
                                    {{ number_format($asset->avg_buy_price, 0, ",", ".") }}
                                </p>
                            </td>
                            <td class="truncate">
                                <p class="font-semibold text-white">
                                    Rp
                                    {{ number_format($asset->current_value, 0, ",", ".") }}
                                </p>
                            </td>
                            <td class="truncate">
                                <p
                                    class="font-semibold {{ $asset->pnl >= 0 ? "text-green-500" : "text-red-500" }}"
                                >
                                    {{ number_format($asset->pnl_percentage, 2, ",", ".") }}%
                                </p>
                            </td>
                            {{--
                                <td>
                                <div class="flex space-x-4">
                                @if ($asset->last_transaction_id)
                                <button
                                wire:click="prepareToEdit({{ $asset->last_transaction_id }})"
                                class="cursor-pointer text-slate-400 hover:text-sky-400"
                                >
                                <x-icon
                                name="lucide.edit-3"
                                class="h-5 w-5"
                                />
                                </button>
                                @endif
                                
                                <button
                                wire:click="prepareToDelete({{ $asset->asset_id }})"
                                class="cursor-pointer text-slate-400 hover:text-red-500"
                                >
                                <x-icon
                                name="lucide.trash-2"
                                class="h-5 w-5"
                                />
                                </button>
                                </div>
                                </td>
                            --}}
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="py-8 text-center text-slate-400"
                            >
                                Anda belum memiliki aset. Silakan tambah
                                transaksi pertama Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="border-t border-slate-800 p-4">
            {{ $portfolio->links("livewire.tailwind-custom") }}
        </div>
    </div>

    <!-- ===== Add Transaction Modal ===== -->
    <div
        x-show="isAddModalOpen"
        @keydown.escape.window="isAddModalOpen = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black p-4"
        style="background-color: rgba(0, 0, 0, 0.7)"
        x-cloak
    >
        <div
            @click.away="isAddModalOpen = false"
            class="card max-h-full w-full max-w-lg overflow-y-auto"
        >
            <div class="p-6 md:p-8">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">
                        Tambah Transaksi
                    </h2>
                    <button
                        @click="isAddModalOpen = false"
                        class="cursor-pointer text-slate-400 hover:text-white"
                    >
                        <x-icon name="lucide.x" class="h-6 w-6" />
                    </button>
                </div>
                <form wire:submit.prevent="addTransaction" class="space-y-4">
                    <!-- Transaction Type -->
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-slate-300"
                        >
                            Tipe Transaksi
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label>
                                <input
                                    type="radio"
                                    wire:model.live="type"
                                    value="buy"
                                    class="peer sr-only @error("type") input-error @enderror"
                                />
                                <div
                                    class="w-full cursor-pointer rounded-lg border border-slate-700 p-3 text-center font-semibold peer-checked:border-sky-500 peer-checked:bg-sky-500/10 peer-checked:text-sky-400"
                                >
                                    Beli
                                </div>
                            </label>
                            <label>
                                <input
                                    type="radio"
                                    wire:model.live="type"
                                    value="sell"
                                    class="peer sr-only @error("type") input-error @enderror"
                                />
                                <div
                                    class="w-full cursor-pointer rounded-lg border border-slate-700 p-3 text-center font-semibold peer-checked:border-red-500 peer-checked:bg-red-500/10 peer-checked:text-red-400"
                                >
                                    Jual
                                </div>
                            </label>
                            @error("type")
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                    <!-- Asset -->
                    <div>
                        <label
                            for="asset"
                            class="mb-2 block text-sm font-medium text-slate-300"
                        >
                            Aset
                        </label>
                        <select
                            id="asset"
                            wire:model="asset_id"
                            class="form-input @error("asset_id") input-error @enderror"
                        >
                            <option value="">Pilih Aset...</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}">
                                    {{ $asset->name }} ({{ $asset->symbol }})
                                </option>
                            @endforeach
                        </select>
                        @error("asset_id")
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label
                                for="quantity"
                                class="mb-2 block text-sm font-medium text-slate-300"
                            >
                                Jumlah
                            </label>
                            <input
                                type="text"
                                inputmode="decimal"
                                step="any"
                                id="quantity"
                                x-mask:dynamic="'9.99999999'"
                                wire:model="quantity"
                                placeholder="Contoh: 0.05 atau 1.12345"
                                class="form-input @error("quantity") input-error @enderror"
                            />
                            @error("quantity")
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div>
                            <label
                                for="price"
                                class="mb-2 block text-sm font-medium text-slate-300"
                            >
                                Harga per Koin (IDR)
                            </label>
                            <input
                                type="text"
                                inputmode="decimal"
                                step="any"
                                id="price"
                                x-mask:dynamic="$money($input, ',')"
                                wire:model="price_per_unit"
                                placeholder="Contoh: 90.000.000"
                                class="form-input @error("price_per_unit") input-error @enderror"
                            />
                            @error("price_per_unit")
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label
                            for="date"
                            class="mb-2 block text-sm font-medium text-slate-300"
                        >
                            Tanggal Transaksi
                        </label>
                        <input
                            type="date"
                            id="date"
                            wire:model="transaction_date"
                            class="form-input text-slate-400 @error("transaction_date") input-error @enderror"
                        />
                        @error("transaction_date")
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="total"
                            class="mb-2 block text-sm font-medium text-slate-300"
                        >
                            Total Biaya/Penerimaan (IDR)
                        </label>
                        <input
                            type="text"
                            inputmode="decimal"
                            step="any"
                            id="total"
                            x-mask:dynamic="$money($input, ',')"
                            wire:model="total_spent"
                            :placeholder="$wire.type === 'buy' ? 'Contoh: 90.050.000' : 'Contoh: 89.950.000'"
                            class="form-input @error("total_spent") input-error @enderror"
                        />
                        @error("total_spent")
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-4 pt-4">
                        <button
                            type="button"
                            @click="isAddModalOpen = false"
                            class="cursor-pointer rounded-lg bg-slate-700 px-6 py-2 font-semibold text-white hover:bg-slate-600"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="addTransaction"
                            class="cursor-pointer rounded-lg bg-sky-500 px-6 py-2 font-semibold text-white hover:bg-sky-600"
                        >
                            <x-loading
                                wire:loading
                                wire:target="addTransaction"
                                class="loading-dots"
                            />
                            <span
                                wire:loading.remove
                                wire:target="addTransaction"
                            >
                                Simpan Transaksi
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== Edit Transaction Modal ===== -->
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
            <div class="p-6 md:p-8">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">
                        Edit Transaksi
                    </h2>
                    <button
                        type="button"
                        @click="show = false"
                        class="cursor-pointer text-slate-400 hover:text-white"
                    >
                        <x-icon name="lucide.x" class="h-6 w-6" />
                    </button>
                </div>
                <form wire:submit.prevent="updateTransaction" class="space-y-4">
                    <!-- Transaction Type -->
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-slate-300"
                        >
                            Tipe Transaksi
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label>
                                <input
                                    type="radio"
                                    wire:model.live="type"
                                    value="buy"
                                    class="peer sr-only"
                                />
                                <div
                                    class="w-full cursor-pointer rounded-lg border border-slate-700 p-3 text-center font-semibold peer-checked:border-sky-500 peer-checked:bg-sky-500/10 peer-checked:text-sky-400"
                                >
                                    Beli
                                </div>
                            </label>
                            <label>
                                <input
                                    type="radio"
                                    wire:model.live="type"
                                    value="sell"
                                    class="peer sr-only"
                                />
                                <div
                                    class="w-full cursor-pointer rounded-lg border border-slate-700 p-3 text-center font-semibold peer-checked:border-red-500 peer-checked:bg-red-500/10 peer-checked:text-red-400"
                                >
                                    Jual
                                </div>
                            </label>
                        </div>
                    </div>
                    <!-- Asset -->
                    <div>
                        <label
                            for="asset"
                            class="mb-2 block text-sm font-medium text-slate-300"
                        >
                            Aset
                        </label>
                        <select
                            id="asset"
                            wire:model="asset_id"
                            class="form-input"
                        >
                            <option value="BTC">Bitcoin (BTC)</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label
                                for="quantity"
                                class="mb-2 block text-sm font-medium text-slate-300"
                            >
                                Jumlah
                            </label>
                            <input
                                type="text"
                                inputmode="decimal"
                                step="any"
                                id="quantity"
                                x-mask:dynamic="'9.99999999'"
                                wire:model="quantity"
                                placeholder="Contoh: 0.05 atau 1.12345"
                                class="form-input"
                            />
                        </div>
                        <div>
                            <label
                                for="price"
                                class="mb-2 block text-sm font-medium text-slate-300"
                            >
                                Harga per Koin (IDR)
                            </label>
                            <input
                                type="text"
                                inputmode="decimal"
                                step="any"
                                id="price"
                                x-mask:dynamic="$money($input, ',')"
                                wire:model="price_per_unit"
                                placeholder="Contoh: 90.000.000"
                                class="form-input"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            for="date"
                            class="mb-2 block text-sm font-medium text-slate-300"
                        >
                            Tanggal Transaksi
                        </label>
                        <input
                            type="date"
                            id="date"
                            wire:model="transaction_date"
                            class="form-input text-slate-400"
                        />
                    </div>

                    <div>
                        <label
                            for="total"
                            class="mb-2 block text-sm font-medium text-slate-300"
                        >
                            Total Biaya/Penerimaan (IDR)
                        </label>
                        <input
                            type="text"
                            inputmode="decimal"
                            step="any"
                            id="total"
                            x-mask:dynamic="$money($input, ',')"
                            wire:model="total_spent"
                            :placeholder="$wire.type === 'buy' ? 'Contoh: 90.050.000' : 'Contoh: 89.950.000'"
                            class="form-input"
                        />
                    </div>

                    <div class="flex justify-end gap-4 pt-4">
                        <button
                            type="button"
                            @click="show = false"
                            class="cursor-pointer rounded-lg bg-slate-700 px-6 py-2 font-semibold text-white hover:bg-slate-600"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="updateTransaction"
                            class="cursor-pointer rounded-lg bg-sky-500 px-6 py-2 font-semibold text-white hover:bg-sky-600"
                        >
                            <x-loading
                                wire:loading
                                wire:target="updateTransaction"
                                class="loading-dots"
                            />
                            <span
                                wire:loading.remove
                                wire:target="updateTransaction"
                            >
                                Simpan
                            </span>
                        </button>
                    </div>
                </form>
            </div>
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
                    Apakah Anda yakin ingin menghapus asset ini? Tindakan ini
                    tidak dapat dibatalkan.
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
                        wire:click="deleteAssetEntries"
                        wire:loading.attr="disabled"
                        wire:target="deleteAssetEntries"
                        class="w-full cursor-pointer rounded-lg bg-red-600 px-6 py-2 font-semibold text-white hover:bg-red-700"
                    >
                        <x-loading
                            wire:loading
                            wire:target="deleteAssetEntries"
                            class="loading-dots"
                        />
                        <span
                            wire:loading.remove
                            wire:target="deleteAssetEntries"
                        >
                            Ya, Hapus
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
