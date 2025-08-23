<?php

use App\Models\Tag;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    // Properti untuk form tambah
    public string $name = "";

    // Properti untuk inline edit
    public ?Tag $editing = null;
    public string $editingName = "";

    // Properti untuk hapus
    public ?Tag $deleting = null;
    public $showDeleteModal = false;

    public function addTag(): void
    {
        $validated = $this->validate([
            "name" => "required|string|max:255|unique:tags,name",
        ]);

        Tag::create([
            "name" => $validated["name"],
            "slug" => Str::slug($validated["name"]),
        ]);

        $this->reset("name");
        session()->flash("message", "Tag berhasil ditambahkan.");
    }

    public function edit(Tag $tag): void
    {
        $this->editing = $tag;
        $this->editingName = $tag->name;
    }

    public function update(): void
    {
        if (! $this->editing) {
            return;
        }

        $validated = $this->validate([
            "editingName" =>
                "required|string|max:255|unique:tags,name," .
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

    public function prepareToDelete(Tag $tag): void
    {
        $this->deleting = $tag;
        $this->showDeleteModal = true;
    }

    public function deleteTag(): void
    {
        if ($this->deleting) {
            $this->deleting->posts()->detach(); // Hapus relasi di tabel pivot
            $this->deleting->delete();
            session()->flash("message", "Tag berhasil dihapus.");
        }
        $this->showDeleteModal = false;
    }

    public function with(): array
    {
        return [
            "tags" => Tag::withCount("posts")
                ->orderBy("name")
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <!-- Page Content -->
    <main class="flex-1 p-6 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-white">Kelola Tag</h1>
        </div>

        <!-- Add Tag Form -->
        <div class="card p-6 mb-8">
            <form
                wire:submit.prevent="addTag"
                class="flex flex-col md:flex-row gap-4 items-start"
            >
                <div class="flex-grow w-full">
                    <label for="name" class="sr-only">Nama Tag</label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        class="form-input"
                        placeholder="Nama tag baru..."
                    />
                    @error("name")
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="addTag"
                    class="bg-sky-500 hover:bg-sky-600 cursor-pointer text-white font-semibold px-5 py-2.5 rounded-lg w-full md:w-auto"
                >
                    <div class="flex items-center justify-center">
                        <x-loading
                            wire:loading
                            wire:target="addTag"
                            class="loading-dots mr-2"
                        />
                        <x-icon
                            name="lucide.plus"
                            wire:loading.remove
                            wire:target="addTag"
                            class="mr-2"
                        />
                        Tambah
                    </div>
                </button>
            </form>
        </div>

        <x-notification />

        <!-- Tags Table -->
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
                        @forelse ($tags as $tag)
                            <tr wire:key="{{ $tag->id }}">
                                <td class="truncate">
                                    @if ($editing?->id === $tag->id)
                                        <input
                                            type="text"
                                            wire:model="editingName"
                                            wire:keydown.enter="update"
                                            wire:keydown.escape="cancelEdit"
                                            class="form-input text-sm p-1"
                                            x-init="$nextTick(() => $el.focus())"
                                            x-ref="editInput{{ $tag->id }}_name"
                                            @click.away="$wire.cancelEdit()"
                                            x-trap.noscroll
                                        />
                                    @else
                                        <p
                                            wire:click="edit({{ $tag->id }})"
                                            class="font-semibold text-white cursor-pointer hover:bg-slate-700 p-1 rounded"
                                        >
                                            {{ $tag->name }}
                                        </p>
                                    @endif
                                </td>
                                <td class="text-slate-400">
                                    {{ $tag->slug }}
                                </td>
                                <td class="text-slate-400">
                                    {{ $tag->posts_count }}
                                </td>
                                <td>
                                    <div>
                                        <button
                                            wire:click="prepareToDelete({{ $tag->id }})"
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
                                    Belum ada tag.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="p-4 border-t border-slate-800">
                {{ $tags->links("livewire.tailwind-custom") }}
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
                <h2 class="text-2xl font-bold text-white">Hapus Tag?</h2>
                <p class="text-slate-400 mt-2">
                    Anda yakin ingin menghapus tag ini? Tag ini akan dihapus
                    dari semua postingan yang terkait.
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
                        wire:click="deleteTag"
                        wire:loading.attr="disabled"
                        wire:target="deleteTag"
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-lg w-full cursor-pointer"
                    >
                        <div class="flex items-center justify-center">
                            <x-loading
                                wire:loading
                                wire:target="deleteTag"
                                class="loading-dots mr-2"
                            />
                            <x-icon
                                name="lucide.trash-2"
                                wire:loading.remove
                                wire:target="deleteTag"
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
