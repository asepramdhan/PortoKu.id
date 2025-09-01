<form
    wire:submit.prevent="addTransaction"
    x-data="{
        amount: @entangle("amount"),
        price_per_unit: @entangle("price_per_unit"),
        quantity: @entangle("quantity"),
        fee_percentage: @entangle("fee_percentage"),
        fee_amount: @entangle("fee_amount"),
        type: @entangle("type"),

        // Fungsi untuk membersihkan dan mengonversi nilai
        parseNumber(value) {
            return parseFloat(value.replace(/[^0-9,-]/g, '').replace(',', '.')) || 0
        },

        // Fungsi untuk menghitung fee
        calculateFee() {
            let rawAmount = this.parseNumber(this.amount)
            let rawFeePercentage = parseFloat(this.fee_percentage) || 0
            this.fee_amount = (rawAmount * (rawFeePercentage / 100)).toFixed(2)
        },

        // Fungsi untuk menghitung quantity
        calculateQuantity() {
            let rawAmount = this.parseNumber(this.amount)
            let rawPricePerUnit = this.parseNumber(this.price_per_unit)
            let rawFeeAmount = this.parseNumber(this.fee_amount)

            if (rawPricePerUnit <= 0) return

            let finalAmount = rawAmount
            if (this.type === 'buy') {
                finalAmount = rawAmount - rawFeeAmount
            } else if (this.type === 'sell') {
                finalAmount = rawAmount + rawFeeAmount
            }

            this.quantity = (finalAmount / rawPricePerUnit).toFixed(8)
        },
    }"
    x-init="
        // Watcher untuk memicu perhitungan saat input berubah
        $watch('amount', () => {
            calculateFee()
            calculateQuantity()
        })
        $watch('price_per_unit', () => calculateQuantity())
        $watch('fee_percentage', () => {
            calculateFee()
            calculateQuantity()
        })
        $watch('type', () => calculateQuantity()) // Tambahkan watcher untuk type
    "
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
            class="text-slate-400 hover:text-white cursor-pointer"
        >
            <x-icon name="lucide.x" class="h-6 w-6" />
        </button>
    </div>

    <div class="space-y-3 mt-4">
        <button
            type="button"
            @click="$dispatch('open-camera-modal')"
            class="w-full flex items-center justify-center gap-2 rounded-lg bg-indigo-400 px-4 py-3 font-semibold text-white transition-colors disabled:bg-indigo-200 disabled:cursor-not-allowed cursor-pointer"
            {{-- class="w-full flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white transition-colors disabled:bg-indigo-500 disabled:cursor-not-allowed cursor-pointer" --}}
            disabled
        >
            <x-icon name="lucide.camera" class="h-5 w-5" />
            <span>Pindai dengan Kamera</span>
        </button>
        <div class="text-center text-xs text-slate-400">
            atau pilih dari galeri:
        </div>
        <div class="grid grid-cols-2 gap-4">
            <button
                type="button"
                @click="$wire.setScanContext('expense'); $refs.fileInput.click()"
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-sky-600 px-4 py-3 font-semibold text-white transition-colors hover:bg-sky-700 cursor-pointer"
            >
                <x-icon name="lucide.receipt" class="h-5 w-5" />
                <span>Struk Belanja</span>
            </button>
            <button
                type="button"
                @click="$wire.setScanContext('asset'); $refs.fileInput.click()"
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-3 font-semibold text-white transition-colors hover:bg-teal-700 cursor-pointer"
            >
                <x-icon name="lucide.bar-chart-2" class="h-5 w-5" />
                <span>Transaksi Aset</span>
            </button>
        </div>
    </div>

    {{-- Input file tersembunyi yang kita picu dari tombol di atas --}}
    <input
        x-ref="fileInput"
        type="file"
        wire:model="receiptImage"
        class="hidden"
        accept="image/*"
    />

    <p>
        <i class="block text-sm text-slate-400">
            * Harap periksa kembali data sebelum disimpan, karena fitur scan ini
            masih dalam tahap pengembangan (beta)
        </i>
    </p>

    {{-- Pemisah --}}
    <div class="flex items-center">
        <div class="flex-grow border-t border-slate-700"></div>
        <span class="flex-shrink mx-4">
            <span wire:loading.remove wire:target="receiptImage">
                @if ($scanStatusMessage)
                    <span
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        class="text-sm {{ $scanStatusType === "success" ? "text-green-400 opacity-80" : "text-red-400 opacity-80" }}"
                    >
                        {{ $scanStatusMessage }}
                    </span>
                @endif

                @if (! $scanStatusMessage)
                    <span class="text-sm text-slate-400">atau isi manual</span>
                @endif
            </span>
            <span
                wire:loading
                wire:target="receiptImage"
                class="text-sm text-slate-400"
            >
                <x-loading class="loading-dots" />
            </span>
        </span>
        <div class="flex-grow border-t border-slate-700"></div>
    </div>

    <!-- Tipe Transaksi -->
    <div>
        <label for="type" class="block text-sm font-medium text-slate-300 mb-2">
            Tipe Transaksi
        </label>
        <select id="type" x-model="type" class="form-input">
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
                        {{ Str::title($asset->name) }}
                        ({{ Str::upper($asset->symbol) }})
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
                    x-model="quantity"
                    placeholder="Akan diisi otomatis"
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
                    x-model="price_per_unit"
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
        </div>

        <div class="flex items-center gap-2">
            <input
                type="text"
                x-ref="amountInput"
                inputmode="decimal"
                id="amount"
                x-mask:dynamic="$money($input, ',')"
                x-model="amount"
                :placeholder="$wire.type === 'income' ? 'Contoh: 5.000.000' : ($wire.type === 'expense' ? 'Contoh: 50.000' : ($wire.type === 'buy' ? 'Contoh: 90.050.000' : 'Contoh: 89.950.000'))"
                class="form-input @error("amount") input-error @enderror"
            />
        </div>
        @error("amount")
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror

        @error("receiptImage")
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div x-show="$wire.type === 'buy' || $wire.type === 'sell'" x-transition>
        <div>
            <label
                for="fee_percentage"
                class="block text-sm font-medium text-slate-300 mb-2"
            >
                Biaya Transaksi (%)
            </label>
            <input
                type="text"
                id="fee_percentage"
                inputmode="decimal"
                x-model="fee_percentage"
                x-mask:dynamic="'9.9999'"
                placeholder="Contoh: 0.1"
                class="form-input @error("fee_percentage") input-error @enderror"
            />
            @error("fee_percentage")
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="text-sm text-slate-400 mt-2">
            Biaya Fee Dihitung: Rp.
            <span
                x-text="parseFloat(fee_amount).toLocaleString('id-ID')"
            ></span>
        </div>
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
