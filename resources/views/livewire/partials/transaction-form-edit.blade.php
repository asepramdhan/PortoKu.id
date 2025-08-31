<form
    wire:submit.prevent="updateTransaction"
    x-data="{
        amount: @entangle("edit_amount"),
        price_per_unit: @entangle("edit_price_per_unit"),
        quantity: @entangle("edit_quantity"),
        fee_percentage: @entangle("edit_fee_percentage"),
        fee_amount: 0,
        type: @entangle("edit_type"),

        // Fungsi untuk membersihkan dan mengonversi nilai
        parseNumber(value) {
            if (typeof value !== 'string') return value || 0
            return parseFloat(value.replace(/\./g, '').replace(',', '.')) || 0
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
        <h2 class="text-2xl font-bold text-white">Edit Transaksi</h2>
        <button
            type="button"
            @click="show = false"
            class="text-slate-400 hover:text-white cursor-pointer"
        >
            <x-icon name="lucide.x" class="h-6 w-6" />
        </button>
    </div>

    <div class="text-center">
        <label class="block text-sm font-medium text-slate-300 mb-3">
            Pindai ulang untuk perbarui data:
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <button
                type="button"
                @click="$wire.setScanContext('expense'); $refs.fileInputEdit.click()"
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-sky-600 px-4 py-3 font-semibold text-white transition-colors hover:bg-sky-700 cursor-pointer"
            >
                <x-icon name="lucide.receipt" class="h-5 w-5" />
                <span>Struk Belanja</span>
            </button>
            <button
                type="button"
                @click="$wire.setScanContext('asset'); $refs.fileInputEdit.click()"
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-3 font-semibold text-white transition-colors hover:bg-teal-700 cursor-pointer"
            >
                <x-icon name="lucide.bar-chart-2" class="h-5 w-5" />
                <span>Transaksi Aset</span>
            </button>
        </div>
    </div>

    {{-- Input file tersembunyi yang kita picu dari tombol di atas --}}
    <input
        x-ref="fileInputEdit"
        type="file"
        wire:model="editReceiptImage"
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
            <span wire:loading.remove wire:target="editReceiptImage">
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
                    <span class="text-sm text-slate-400">Atau ubah manual</span>
                @endif
            </span>
            <span
                wire:loading
                wire:target="editReceiptImage"
                class="text-sm text-slate-400"
            >
                <x-loading class="loading-dots" />
            </span>
        </span>
        <div class="flex-grow border-t border-slate-700"></div>
    </div>

    <!-- Tipe Transaksi -->
    <div>
        <label
            for="edit_type"
            class="block text-sm font-medium text-slate-300 mb-2"
        >
            Tipe Transaksi
        </label>
        <select id="edit_type" x-model="type" class="form-input">
            <option value="income">Pemasukan</option>
            <option value="expense">Pengeluaran</option>
            <option value="buy">Beli Aset</option>
            <option value="sell">Jual Aset</option>
        </select>
    </div>

    <!-- Fields for Income/Expense -->
    <div
        x-show="$wire.edit_type === 'income' || $wire.edit_type === 'expense'"
        x-transition
    >
        <label
            for="edit_category"
            class="block text-sm font-medium text-slate-300 mb-2"
        >
            Kategori
        </label>
        <input
            type="text"
            id="edit_category"
            wire:model="edit_category"
            placeholder="Contoh: Gaji, Makanan"
            class="form-input @error("edit_category") input-error @enderror"
        />
        @error("edit_category")
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <!-- Fields for Buy/Sell -->
    <div
        x-show="$wire.edit_type === 'buy' || $wire.edit_type === 'sell'"
        x-transition
        class="space-y-4"
    >
        <div>
            <label
                for="edit_asset_id"
                class="block text-sm font-medium text-slate-300 mb-2"
            >
                Aset
            </label>
            <select
                id="edit_asset_id"
                wire:model="edit_asset_id"
                class="form-input @error("edit_asset_id") input-error @enderror"
            >
                <option value="">Pilih Aset...</option>
                @foreach ($assets as $asset)
                    <option value="{{ $asset->id }}">
                        {{ $asset->name }} ({{ $asset->symbol }})
                    </option>
                @endforeach
            </select>
            @error("edit_asset_id")
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label
                    for="edit_quantity"
                    class="block text-sm font-medium text-slate-300 mb-2"
                >
                    Jumlah Aset
                </label>
                <input
                    type="text"
                    inputmode="decimal"
                    id="edit_quantity"
                    x-mask:dynamic="'9.99999999'"
                    x-model="quantity"
                    placeholder="Akan diisi otomatis"
                    class="form-input @error("edit_quantity") input-error @enderror"
                />
                @error("edit_quantity")
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label
                    for="edit_price_per_unit"
                    class="block text-sm font-medium text-slate-300 mb-2"
                >
                    Harga per Unit (IDR)
                </label>
                <input
                    type="text"
                    inputmode="decimal"
                    id="edit_price_per_unit"
                    x-mask:dynamic="$money($input, ',')"
                    x-model="price_per_unit"
                    placeholder="90.000.000"
                    class="form-input @error("edit_price_per_unit") input-error @enderror"
                />
                @error("edit_price_per_unit")
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Common Fields -->
    <div>
        <label
            for="edit_amount"
            class="block text-sm font-medium text-slate-300 mb-2"
        >
            Total Nilai (IDR)
        </label>
        <input
            type="text"
            inputmode="decimal"
            id="edit_amount"
            x-mask:dynamic="$money($input, ',')"
            x-model="amount"
            :placeholder="$wire.type === 'income' ? 'Contoh: 5.000.000' : ($wire.type === 'expense' ? 'Contoh: 50.000' : ($wire.type === 'buy' ? 'Contoh: 90.050.000' : 'Contoh: 89.950.000'))"
            class="form-input @error("edit_amount") input-error @enderror"
        />
        @error("edit_amount")
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div
        x-show="$wire.edit_type === 'buy' || $wire.edit_type === 'sell'"
        x-transition
    >
        <div>
            <label
                for="fee_percentage"
                class="block text-sm font-medium text-slate-300 mb-2"
            >
                Biaya Transaksi (%)
                <i class="text-xs text-red-400">* not saved</i>
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
            for="edit_transaction_date"
            class="block text-sm font-medium text-slate-300 mb-2"
        >
            Tanggal
        </label>
        <input
            type="date"
            id="edit_transaction_date"
            wire:model="edit_transaction_date"
            class="form-input @error("edit_transaction_date") input-error @enderror"
        />
        @error("edit_transaction_date")
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label
            for="edit_notes"
            class="block text-sm font-medium text-slate-300 mb-2"
        >
            Catatan (Opsional)
        </label>
        <textarea
            id="edit_notes"
            wire:model="edit_notes"
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
            wire:target="updateTransaction"
            class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg cursor-pointer"
        >
            <div class="flex items-center">
                <x-loading
                    wire:loading
                    wire:target="updateTransaction"
                    class="loading-dots mr-2"
                />
                <x-icon
                    name="lucide.save"
                    wire:loading.remove
                    wire:target="updateTransaction"
                    class="mr-2"
                />
                Simpan
            </div>
        </button>
    </div>
</form>
