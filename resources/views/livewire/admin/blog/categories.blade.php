<?php

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    // Properti untuk form tambah
    public string $name = "";

    // Properti untuk inline edit
    public ?Category $editing = null;
    public string $editingName = "";

    // Properti untuk hapus
    public ?Category $deleting = null;
    public $showDeleteModal = false;

    public function addCategory(): void
    {
        $validated = $this->validate([
            "name" => "required|string|max:255|unique:categories,name",
        ]);

        Category::create([
            "name" => $validated["name"],
            "slug" => Str::slug($validated["name"]),
        ]);

        $this->reset("name");
        session()->flash("message", "Kategori berhasil ditambahkan.");
    }

    public function edit(Category $category): void
    {
        $this->editing = $category;
        $this->editingName = $category->name;
    }

    public function update(): void
    {
        if (! $this->editing) {
            return;
        }

        $validated = $this->validate([
            "editingName" =>
                "required|string|max:255|unique:categories,name," .
                $this->editing->id,
        ]);

        $this->editing->update([
            "name" => $validated["editingName"],
            "slug" => Str::slug($validated["editingName"]),
        ]);

        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->reset("editing", "editingName");
    }

    public function prepareToDelete(Category $category): void
    {
        $this->deleting = $category;
        $this->showDeleteModal = true;
    }

    public function deleteCategory(): void
    {
        if ($this->deleting) {
            $this->deleting->delete();
            session()->flash("message", "Kategori berhasil dihapus.");
        }
        $this->showDeleteModal = false;
    }

    public function with(): array
    {
        return [
            "categories" => Category::withCount("posts")
                ->orderBy("name")
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <!-- Page Content -->
    <main class="flex-1 p-6 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-white">Kelola Kategori</h1>
        </div>

        <x-notification />

        <!-- Add Category Form -->
        <div class="card p-6 mb-8">
            <form
                wire:submit.prevent="addCategory"
                class="flex flex-col md:flex-row gap-4 items-start"
            >
                <div class="flex-grow w-full">
                    <label for="name" class="sr-only">Nama Kategori</label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        class="form-input"
                        placeholder="Nama kategori baru..."
                    />
                    @error("name")
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="addCategory"
                    class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-5 py-2.5 rounded-lg w-full md:w-auto cursor-pointer transition-colors"
                >
                    <div class="flex items-center justify-center">
                        <x-loading
                            wire:loading
                            wire:target="addCategory"
                            class="loading-dots mr-2"
                        />
                        <x-icon
                            name="lucide.plus"
                            wire:loading.remove
                            wire:target="addCategory"
                            class="mr-2"
                        />
                        Tambah
                    </div>
                </button>
            </form>
        </div>

        <!-- Categories Table -->
        <div class="card">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Slug</th>
                            <th>Jumlah Postingan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr wire:key="{{ $category->id }}">
                                <td class="truncate">
                                    @if ($editing?->id === $category->id)
                                        <input
                                            type="text"
                                            wire:model="editingName"
                                            wire:keydown.enter="update"
                                            wire:keydown.escape="cancelEdit"
                                            class="form-input text-sm p-1"
                                            x-init="$nextTick(() => $el.focus())"
                                            x-ref="editInput{{ $category->id }}_name"
                                            @click.away="$wire.cancelEdit()"
                                            x-trap.noscroll
                                        />
                                    @else
                                        <p
                                            wire:click="edit({{ $category->id }})"
                                            class="font-semibold text-white cursor-pointer hover:bg-slate-700 p-1 rounded"
                                        >
                                            {{ Str::title($category->name) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="text-slate-400 truncate">
                                    {{ $category->slug }}
                                </td>
                                <td class="text-slate-400">
                                    {{ $category->posts_count }}
                                </td>
                                <td>
                                    <div>
                                        <button
                                            wire:click="prepareToDelete({{ $category->id }})"
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
                                    colspan="4"
                                    class="text-center py-8 text-slate-400"
                                >
                                    Belum ada kategori.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="p-4 border-t border-slate-800">
                {{ $categories->links("livewire.tailwind-custom") }}
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
                <h2 class="text-2xl font-bold text-white">Hapus Kategori?</h2>
                <p class="text-slate-400 mt-2">
                    Anda yakin ingin menghapus kategori ini? Postingan yang
                    terkait tidak akan terhapus, tetapi akan kehilangan
                    kategorinya.
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
                        wire:click="deleteCategory"
                        wire:loading.attr="disabled"
                        wire:target="deleteCategory"
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-lg w-full"
                    >
                        <div class="flex items-center justify-center">
                            <x-loading
                                wire:loading
                                wire:target="deleteCategory"
                                class="loading-dots mr-2"
                            />
                            <x-icon
                                name="lucide.trash-2"
                                wire:loading.remove
                                wire:target="deleteCategory"
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
