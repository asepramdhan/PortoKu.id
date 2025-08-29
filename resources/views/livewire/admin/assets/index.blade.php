<?php

use Livewire\Volt\Component;
use App\Models\Asset;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithPagination;

    // Properti untuk form tambah
    public string $name = "";
    public string $symbol = "";

    // Properti untuk inline edit
    public ?Asset $editing = null;
    public string $editingName = "";
    public string $editingSymbol = "";

    // Properti untuk hapus
    public ?Asset $deleting = null;
    public $showDeleteModal = false;

    /**
     * Mengambil daftar aset menggunakan Computed Property untuk efisiensi.
     * Query ini hanya akan dijalankan sekali per siklus request.
     */
    #[Computed]
    public function assets()
    {
        return Asset::orderBy("name")->paginate(10);
    }

    /**
     * Menambahkan aset baru.
     */
    public function addAsset(): void
    {
        $validated = $this->validate([
            "name" => "required|string|max:255|unique:assets,name",
            "symbol" => "required|string|max:10|unique:assets,symbol",
        ]);

        Asset::create($validated);

        $this->reset("name", "symbol");

        // [FIX] Hapus cache computed property agar daftar di-refresh dengan data baru.
        unset($this->assets);

        session()->flash("message", "Aset berhasil ditambahkan.");
    }

    /**
     * Menyiapkan mode inline-edit.
     */
    public function edit(Asset $asset): void
    {
        $this->editing = $asset;
        $this->editingName = $asset->name;
        $this->editingSymbol = $asset->symbol; // Menambahkan symbol ke mode edit
    }

    /**
     * Menyimpan perubahan dari inline-edit.
     */
    public function update(): void
    {
        if (! $this->editing) {
            return;
        }

        $validated = $this->validate([
            "editingName" => [
                "required",
                "string",
                "max:255",
                Rule::unique("assets", "name")->ignore($this->editing->id),
            ],
            "editingSymbol" => [
                "required",
                "string",
                "max:10",
                Rule::unique("assets", "symbol")->ignore($this->editing->id),
            ],
        ]);

        $this->editing->update([
            "name" => $validated["editingName"],
            "symbol" => $validated["editingSymbol"],
        ]);

        $this->cancelEdit();
        session()->flash("message", "Aset berhasil diupdate.");
    }

    /**
     * Membatalkan mode inline-edit.
     */
    public function cancelEdit(): void
    {
        $this->reset("editing", "editingName", "editingSymbol");
    }

    /**
     * Menyiapkan modal konfirmasi hapus.
     */
    public function prepareToDelete(Asset $asset): void
    {
        $this->deleting = $asset;
        $this->showDeleteModal = true;
    }

    /**
     * Menghapus aset yang dipilih.
     */
    public function deleteAsset(): void
    {
        if ($this->deleting) {
            $this->deleting->delete();

            // [FIX] Hapus cache computed property agar daftar di-refresh.
            unset($this->assets);

            session()->flash("message", "Aset berhasil dihapus.");
        }
        $this->showDeleteModal = false;
    }
}; ?>

<div>
    <!-- Page Content -->
    <main class="flex-1 p-6 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-white">Kelola Aset</h1>
        </div>

        <x-notification />

        <!-- Add Asset Form -->
        <div class="card p-6 mb-8">
            <form
                wire:submit="addAsset"
                class="space-y-4 md:space-y-0 md:flex md:items-start md:gap-4"
            >
                {{-- Grup untuk input --}}
                <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="sr-only">Nama Aset</label>
                        <input
                            type="text"
                            id="name"
                            wire:model.blur="name"
                            class="form-input"
                            placeholder="Nama aset (misal: Bitcoin)"
                            autocomplete="off"
                        />
                        @error("name")
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div>
                        <label for="symbol" class="sr-only">Simbol</label>
                        <input
                            type="text"
                            id="symbol"
                            wire:model.blur="symbol"
                            class="form-input"
                            placeholder="Simbol (misal: BTC)"
                            autocomplete="off"
                        />
                        @error("symbol")
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="addAsset"
                    class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-5 py-2.5 rounded-lg w-full md:w-auto cursor-pointer transition-colors"
                >
                    <div class="flex items-center justify-center">
                        <x-loading
                            wire:loading
                            wire:target="addAsset"
                            class="loading-dots mr-2"
                        />
                        <x-icon
                            name="lucide.plus"
                            wire:loading.remove
                            wire:target="addAsset"
                            class="mr-2"
                        />
                        Tambah
                    </div>
                </button>
            </form>
        </div>

        <!-- Assets Table -->
        <div class="card">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Simbol</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody @click.away="$wire.cancelEdit()">
                        @forelse ($this->assets as $asset)
                            <tr wire:key="{{ $asset->id }}">
                                <td class="truncate">
                                    @if ($editing?->id === $asset->id)
                                        <input
                                            type="text"
                                            wire:model="editingName"
                                            wire:keydown.enter="update"
                                            wire:keydown.escape="cancelEdit"
                                            class="form-input text-sm p-1"
                                            x-init="$nextTick(() => $el.focus())"
                                            x-trap.noscroll
                                        />
                                    @else
                                        <p
                                            wire:click="edit({{ $asset->id }})"
                                            class="font-semibold text-white cursor-pointer hover:bg-slate-700 p-1 rounded"
                                        >
                                            {{ Str::title($asset->name) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="truncate">
                                    @if ($editing?->id === $asset->id)
                                        <input
                                            type="text"
                                            wire:model="editingSymbol"
                                            wire:keydown.enter="update"
                                            wire:keydown.escape="cancelEdit"
                                            class="form-input text-sm p-1"
                                        />
                                    @else
                                        <p class="text-slate-400">
                                            {{ strtoupper($asset->symbol) }}
                                        </p>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <button
                                            wire:click="prepareToDelete({{ $asset->id }})"
                                            class="text-slate-400 hover:text-red-500 cursor-pointer"
                                        >
                                            <x-icon
                                                name="lucide.trash-2"
                                                class="w-5 h-5"
                                            />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="3"
                                    class="text-center py-8 text-slate-400"
                                >
                                    Belum ada aset.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="p-4 border-t border-slate-800">
                {{-- [FIX] Menggunakan $this->assets untuk memanggil computed property --}}
                {{ $this->assets->links("livewire.tailwind-custom") }}
            </div>
        </div>
    </main>

    <!-- Delete Modal -->
    <div
        x-data="{ show: @entangle("showDeleteModal") }"
        x-show="show"
        @keydown.escape.window="show = false"
        class="fixed inset-0 bg-black z-50 flex items-center justify-center p-4"
        style="background-color: rgba(0, 0, 0, 0.7)"
        x-cloak
    >
        <div @click.away="show = false" class="card w-full max-w-md">
            <div class="p-6 md:p-8 text-center">
                <h2 class="text-2xl font-bold text-white">Hapus Aset?</h2>
                <p class="text-slate-400 mt-2">
                    Anda yakin ingin menghapus aset ini? Tindakan ini tidak
                    dapat dibatalkan.
                </p>
                <div class="mt-6 flex justify-center gap-4">
                    <button
                        type="button"
                        @click="show = false"
                        class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-6 py-2 rounded-lg w-full cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="deleteAsset"
                        wire:loading.attr="disabled"
                        wire:target="deleteAsset"
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-lg w-full cursor-pointer"
                    >
                        <div class="flex items-center justify-center">
                            <x-loading
                                wire:loading
                                wire:target="deleteAsset"
                                class="loading-dots mr-2"
                            />
                            <x-icon
                                name="lucide.trash-2"
                                wire:loading.remove
                                wire:target="deleteAsset"
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
