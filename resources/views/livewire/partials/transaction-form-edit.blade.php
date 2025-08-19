<form wire:submit.prevent="updateTransaction" class="p-6 md:p-8 space-y-4">
  <div class="flex justify-between items-center mb-2">
    <h2 class="text-2xl font-bold text-white">Edit Transaksi</h2>
    <button type="button" @click="show = false" class="text-slate-400 hover:text-white">
      <x-icon name="lucide.x" class="h-6 w-6" />
    </button>
  </div>

  <!-- Tipe Transaksi -->
  <div>
    <label for="edit_type" class="block text-sm font-medium text-slate-300 mb-2">Tipe Transaksi</label>
    <select id="edit_type" wire:model.live="edit_type" class="form-input">
      <option value="income">Pemasukan</option>
      <option value="expense">Pengeluaran</option>
      <option value="buy">Beli Aset</option>
      <option value="sell">Jual Aset</option>
    </select>
  </div>

  <!-- Fields for Income/Expense -->
  <div x-show="$wire.edit_type === 'income' || $wire.edit_type === 'expense'" x-transition>
    <label for="edit_category" class="block text-sm font-medium text-slate-300 mb-2">Kategori</label>
    <input type="text" id="edit_category" wire:model="edit_category" placeholder="Contoh: Gaji, Makanan" class="form-input @error('edit_category') input-error @enderror">
    @error('edit_category') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
  </div>

  <!-- Fields for Buy/Sell -->
  <div x-show="$wire.edit_type === 'buy' || $wire.edit_type === 'sell'" x-transition class="space-y-4">
    <div>
      <label for="edit_asset_id" class="block text-sm font-medium text-slate-300 mb-2">Aset</label>
      <select id="edit_asset_id" wire:model="edit_asset_id" class="form-input @error('edit_asset_id') input-error @enderror">
        <option value="">Pilih Aset...</option>
        @foreach($assets as $asset)
        <option value="{{ $asset->id }}">{{ $asset->name }} ({{ $asset->symbol }})</option>
        @endforeach
      </select>
      @error('edit_asset_id') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label for="edit_quantity" class="block text-sm font-medium text-slate-300 mb-2">Jumlah Aset</label>
        <input type="text" inputmode="decimal" id="edit_quantity" x-mask:dynamic="'9.99999999'" wire:model="edit_quantity" placeholder="Contoh: 0.05 atau 1.12345" class="form-input @error('edit_quantity') input-error @enderror">
        @error('edit_quantity') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
      </div>
      <div>
        <label for="edit_price_per_unit" class="block text-sm font-medium text-slate-300 mb-2">Harga per Unit (IDR)</label>
        <input type="text" inputmode="decimal" id="edit_price_per_unit" x-mask:dynamic="$money($input, ',')" wire:model="edit_price_per_unit" placeholder="90.000.000" class="form-input @error('edit_price_per_unit') input-error @enderror">
        @error('edit_price_per_unit') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
      </div>
    </div>
  </div>

  <!-- Common Fields -->
  <div>
    <label for="edit_amount" class="block text-sm font-medium text-slate-300 mb-2">Total Nilai (IDR)</label>
    <input type="text" inputmode="decimal" id="edit_amount" x-mask:dynamic="$money($input, ',')" wire:model="edit_amount" :placeholder="$wire.type === 'income' ? 'Contoh: 5.000.000' : ($wire.type === 'expense' ? 'Contoh: 50.000' : ($wire.type === 'buy' ? 'Contoh: 90.050.000' : 'Contoh: 89.950.000'))" class="form-input @error('edit_amount') input-error @enderror">
    @error('edit_amount') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
  </div>

  <div>
    <label for="edit_transaction_date" class="block text-sm font-medium text-slate-300 mb-2">Tanggal</label>
    <input type="date" id="edit_transaction_date" wire:model="edit_transaction_date" class="form-input @error('edit_transaction_date') input-error @enderror">
    @error('edit_transaction_date') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
  </div>

  <div>
    <label for="edit_notes" class="block text-sm font-medium text-slate-300 mb-2">Catatan (Opsional)</label>
    <textarea id="edit_notes" wire:model="edit_notes" rows="3" class="form-input"></textarea>
  </div>

  <!-- Actions -->
  <div class="pt-4 flex justify-end gap-4">
    <button type="button" @click="show = false" class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-6 py-2 rounded-lg cursor-pointer">Batal</button>
    <button type="submit" wire:loading.attr="disabled" wire:target="updateTransaction" class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg cursor-pointer">
      <div class="flex items-center">
        <x-loading wire:loading wire:target="updateTransaction" class="loading-dots mr-2" />
        <x-icon name="lucide.save" wire:loading.remove wire:target="updateTransaction" class="mr-2" />
        Simpan
      </div>
    </button>
  </div>
</form>
