<form
    wire:submit.prevent="addTransaction"
    x-data
    @ocr-completed.window="
        if ($refs.amountInput) {
            $refs.amountInput.value = $event.detail.amount;
            $refs.amountInput.dispatchEvent(new Event('input')); // Penting untuk memicu x-mask
        }
      "
    @scan-message-received.window="setTimeout(() => $wire.set('scanStatusMessage', ''), 4000)"
    class="p-6 md:p-8 space-y-4"
>
    <div class="flex justify-between items-center mb-2">
        <h2 class="text-2xl font-bold text-white">Tambah Transaksi</h2>
        <button
            type="button"
            @click="show = false"
            class="text-slate-400 hover:text-white"
        >
            <x-icon name="lucide.x" class="h-6 w-6" />
        </button>
    </div>

    <!-- Tipe Transaksi -->
    <div>
        <label for="type" class="block text-sm font-medium text-slate-300 mb-2">
            Tipe Transaksi
        </label>
        <select id="type" wire:model.live="type" class="form-input">
            <option value="income">Pemasukan</option>
            <option value="expense">Pengeluaran</option>
            <option value="buy">Beli Aset</option>
            <option value="sell">Jual Aset</option>
        </select>
    </div>

    <!-- Fields for Income/Expense -->
    <div
        x-show="$wire.type === 'income' || $wire.type === 'expense'"
        x-transition
    >
        <label
            for="category"
            class="block text-sm font-medium text-slate-300 mb-2"
        >
            Kategori
        </label>
        <input
            type="text"
            id="category"
            wire:model="category"
            placeholder="Contoh: Gaji, Makanan"
            class="form-input @error("category") input-error @enderror"
        />
        @error("category")
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <!-- Fields for Buy/Sell -->
    <div
        x-show="$wire.type === 'buy' || $wire.type === 'sell'"
        x-transition
        class="space-y-4"
    >
        <div>
            <label
                for="asset_id"
                class="block text-sm font-medium text-slate-300 mb-2"
            >
                Aset
            </label>
            <select
                id="asset_id"
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
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label
                    for="quantity"
                    class="block text-sm font-medium text-slate-300 mb-2"
                >
                    Jumlah Aset
                </label>
                {{-- FIX: Menambahkan x-mask --}}
                <input
                    type="text"
                    inputmode="decimal"
                    id="quantity"
                    x-mask:dynamic="'9.99999999'"
                    wire:model="quantity"
                    placeholder="Contoh: 0.05 atau 1.12345"
                    class="form-input @error("quantity") input-error @enderror"
                />
                @error("quantity")
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label
                    for="price_per_unit"
                    class="block text-sm font-medium text-slate-300 mb-2"
                >
                    Harga per Unit (IDR)
                </label>
                {{-- FIX: Menambahkan x-mask --}}
                <input
                    type="text"
                    inputmode="decimal"
                    id="price_per_unit"
                    x-mask:dynamic="$money($input, ',')"
                    wire:model="price_per_unit"
                    placeholder="Contoh: 90.000.000"
                    class="form-input @error("price_per_unit") input-error @enderror"
                />
                @error("price_per_unit")
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Common Fields -->
    <div>
        <div class="flex items-center justify-between gap-2">
            <label
                for="amount"
                class="block text-sm font-medium text-slate-300 mb-2"
            >
                Total Nilai (IDR)
            </label>
            @if ($scanStatusMessage)
                <span
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    class="text-xs {{ $scanStatusType === "success" ? "text-green-400 opacity-80" : "text-red-400 opacity-80" }}"
                >
                    {{ $scanStatusMessage }}
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <input
                type="text"
                x-ref="amountInput"
                inputmode="decimal"
                id="amount"
                x-mask:dynamic="$money($input, ',')"
                wire:model="amount"
                :placeholder="$wire.type === 'income' ? 'Contoh: 5.000.000' : ($wire.type === 'expense' ? 'Contoh: 50.000' : ($wire.type === 'buy' ? 'Contoh: 90.050.000' : 'Contoh: 89.950.000'))"
                class="form-input @error("amount") input-error @enderror"
            />
            {{-- Tombol Scan Struk Baru --}}
            <label
                for="receiptImage"
                class="flex-shrink-0 bg-slate-700 disabled:hover:bg-slate-600 text-white font-semibold p-2.5 rounded-lg cursor-not-allowed transition-colors"
            >
                <div wire:loading.remove wire:target="receiptImage">
                    <x-icon name="lucide.scan-line" class="w-5 h-5" />
                </div>
                <div wire:loading wire:target="receiptImage">
                    <x-loading class="loading-dots" />
                </div>
            </label>
            <input
                type="file"
                id="receiptImage"
                wire:model.live="receiptImage"
                class="hidden"
                disabled
            />
        </div>
        @error("amount")
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror

        @error("receiptImage")
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label
            for="transaction_date"
            class="block text-sm font-medium text-slate-300 mb-2"
        >
            Tanggal
        </label>
        <input
            type="date"
            id="transaction_date"
            wire:model.defer="transaction_date"
            class="form-input @error("transaction_date") input-error @enderror"
        />
        @error("transaction_date")
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label
            for="notes"
            class="block text-sm font-medium text-slate-300 mb-2"
        >
            Catatan (Opsional)
        </label>
        <textarea
            id="notes"
            wire:model="notes"
            rows="3"
            class="form-input"
        ></textarea>
    </div>

    <!-- Actions -->
    <div class="pt-4 flex justify-end gap-4">
        <button
            type="button"
            @click="show = false"
            class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-6 py-2 rounded-lg cursor-pointer"
        >
            Batal
        </button>
        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="addTransaction"
            class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg cursor-pointer"
        >
            <div class="flex items-center">
                <x-loading
                    wire:loading
                    wire:target="addTransaction"
                    class="loading-dots mr-2"
                />
                <x-icon
                    name="lucide.plus"
                    wire:loading.remove
                    wire:target="addTransaction"
                    class="mr-2"
                />
                Tambah
            </div>
        </button>
    </div>
</form>
