<?php

use App\Models\ShopeeAd;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithPagination, WithFileUploads;

    // Properti untuk Modal & Form
    public bool $showAdModal = false;
    public ?ShopeeAd $editingAd = null;
    public string $product_name = "",
        $description = "",
        $ad_link = "";
    public $image_path;
    public bool $is_published = false;

    public function create(): void
    {
        $this->reset();
        $this->isEditMode = false;
        $this->dispatch("trix-clear", id: "description-editor");
        $this->showAdModal = true;
    }

    public function saveAd(): void
    {
        $rules = [
            "product_name" => "required|string",
            "description" => "required|string",
            "ad_link" => "required|url",
            "image_path" =>
                $this->image_path && ! is_string($this->image_path)
                    ? "required|image|max:1024"
                    : "nullable",
            "is_published" => "required|boolean",
        ];

        $validated = $this->validate($rules);

        // Proses upload gambar jika ada file baru
        if ($this->image_path && ! is_string($this->image_path)) {
            // Hapus gambar lama jika sedang mengedit
            if ($this->editingAd?->image_path) {
                Storage::disk("public")->delete(
                    Str::after($this->editingAd->image_path, "/storage/"),
                );
            }
            $path = $this->image_path->store("ad-images", "public");
            $validated["image_path"] = Storage::disk("public")->url($path);
        } else {
            // Jika tidak ada gambar baru, jangan ubah path gambar yang sudah ada
            unset($validated["image_path"]);
        }

        if ($this->editingAd) {
            $this->editingAd->update($validated);
            session()->flash("message", "Iklan berhasil diperbarui.");
        } else {
            ShopeeAd::create($validated);
            session()->flash("message", "Iklan berhasil ditambahkan.");
        }

        $this->showAdModal = false;
    }

    public function edit(ShopeeAd $ad): void
    {
        $this->resetErrorBag();
        $this->editingAd = $ad;
        $this->product_name = $ad->product_name;
        $this->description = $ad->description;
        $this->ad_link = $ad->ad_link;
        $this->image_path = $ad->image_path;
        $this->is_published = $ad->is_published;

        $this->dispatch(
            "trix-set-content",
            id: "description-editor",
            content: $this->description,
        );
        $this->showAdModal = true;
    }

    public function delete(ShopeeAd $ad): void
    {
        if ($ad->image_path) {
            Storage::disk("public")->delete(
                Str::after($ad->image_path, "/storage/"),
            );
        }
        $ad->delete();
        session()->flash("message", "Iklan berhasil dihapus.");
    }

    public function togglePublished(ShopeeAd $ad): void
    {
        $ad->update(["is_published" => ! $ad->is_published]);
    }

    public function with(): array
    {
        return [
            "shoppeAds" => ShopeeAd::latest()->paginate(10),
        ];
    }
}; ?>

<div>
    <!-- Page Content -->
    <div class="flex flex-col md:flex-row justify-between md:items-center mb-6">
        <h1 class="text-3xl font-bold text-white">Kelola Iklan</h1>
        <button
            wire:click="create"
            class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors mt-4 md:mt-0 cursor-pointer"
        >
            <x-icon name="lucide.plus-circle" class="h-5 w-5" />
            Tambah Iklan
        </button>
    </div>

    <x-notification />

    <!-- Web Apps Table -->
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Judul</th>
                        <th class="truncate">Link Afiliasi</th>
                        <th>Status</th>
                        <th>Klik</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shoppeAds as $ad)
                        <tr wire:key="{{ $ad->id }}">
                            <td>
                                <img
                                    src="{{ $ad->image_path ?? "https://placehold.co/120x80/1E293B/FFFFFF?text=No+Img" }}"
                                    alt="Thumbnail"
                                    class="w-20 h-12 object-cover rounded-md"
                                />
                            </td>
                            <td class="font-semibold text-white truncate">
                                {{ Str::limit($ad->product_name, 35) }}
                            </td>
                            <td class="truncate">
                                @if ($ad->ad_link)
                                    <a
                                        href="{{ $ad->ad_link }}"
                                        target="_blank"
                                        class="text-sky-400 hover:underline"
                                    >
                                        Link Afiliasi
                                    </a>
                                @else
                                    <div class="text-center">-</div>
                                @endif
                            </td>
                            <td>
                                <button
                                    wire:click="togglePublished({{ $ad->id }})"
                                    class="flex items-center gap-2 text-sm cursor-pointer {{ $ad->is_published ? "text-green-400" : "text-slate-400" }}"
                                >
                                    <div
                                        class="relative w-10 h-5 rounded-full {{ $ad->is_published ? "bg-green-500/30" : "bg-slate-700" }}"
                                    >
                                        <div
                                            class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform {{ $ad->is_published ? "translate-x-5" : "translate-x-1" }}"
                                        ></div>
                                    </div>
                                    <span>
                                        {{ $ad->is_published ? "Published" : "Draft" }}
                                    </span>
                                </button>
                            </td>
                            <td>
                                {{-- KOLOM KLIK BARU --}}
                                <span class="font-bold text-lg text-sky-400">
                                    {{ number_format($ad->clicks_count, 0, ",", ".") }}
                                </span>
                            </td>
                            <td>
                                <div class="flex space-x-2">
                                    <button
                                        wire:click="edit({{ $ad->id }})"
                                        class="text-slate-400 hover:text-sky-400 cursor-pointer"
                                        title="Edit"
                                    >
                                        <x-icon
                                            name="lucide.square-pen"
                                            class="w-5 h-5"
                                        />
                                    </button>
                                    <button
                                        x-on:click="
                                            if (confirm('Anda yakin ingin menghapus aplikasi ini?')) {
                                                $wire.delete({{ $ad->id }})
                                            }
                                        "
                                        class="text-slate-400 hover:text-red-500 cursor-pointer"
                                        title="Hapus"
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
                                colspan="5"
                                class="text-center py-8 text-slate-400"
                            >
                                Belum ada iklan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $shoppeAds->links("livewire.tailwind-custom") }}
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div
        x-data="{ show: @entangle("showAdModal") }"
        x-show="show"
        @keydown.escape.window="show = false"
        class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4"
        x-cloak
    >
        <div
            @click.away="show = false"
            class="card w-full max-w-2xl max-h-[90vh] flex flex-col"
        >
            <div class="p-6 border-b border-slate-800">
                <h2 class="text-2xl font-bold text-white">
                    {{ $editingAd ? "Edit Iklan" : "Tambah Iklan" }}
                </h2>
            </div>
            <form
                wire:submit.prevent="saveAd"
                class="flex-grow overflow-y-auto p-6 space-y-4"
            >
                <div>
                    <label for="product_name" class="form-label">
                        Judul Produk
                    </label>
                    <input
                        type="text"
                        id="product_name"
                        wire:model="product_name"
                        class="form-input @error("product_name") input-error @enderror"
                        required
                    />
                    @error("product_name")
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label for="description-editor" class="label">
                        Deskripsi Produk
                    </label>
                    <x-trix-ads
                        id="description-editor"
                        wire:model="description"
                    />
                    @error("description")
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ad_link" class="form-label">
                        Link Afiliasi
                    </label>
                    <input
                        type="url"
                        id="ad_link"
                        wire:model="ad_link"
                        class="form-input @error("ad_link") input-error @enderror"
                        placeholder="https://s.shopee.co.id/5L4..."
                        required
                    />
                    @error("ad_link")
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label for="image_path" class="form-label">
                        Gambar Thumbnail
                    </label>
                    <input
                        type="file"
                        id="image_path"
                        wire:model="image_path"
                        class="form-input @error("image_path") input-error @enderror !p-0 file:mr-4 file:py-2 file:px-4 file:rounded-l-md file:border-0 file:bg-slate-700 file:text-slate-300 hover:file:bg-slate-600"
                    />
                    @error("image_path")
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                    @if ($image_path && is_string($image_path))
                        <img
                            src="{{ $image_path }}"
                            class="mt-4 w-32 h-auto rounded-md"
                        />
                    @elseif ($image_path)
                        <img
                            src="{{ $image_path->temporaryUrl() }}"
                            class="mt-4 w-32 h-auto rounded-md"
                        />
                    @endif
                </div>
                <div>
                    <label class="form-label flex items-center gap-2">
                        <input
                            type="checkbox"
                            wire:model="is_published"
                            class="form-checkbox"
                        />
                        Publish
                    </label>
                </div>
            </form>
            <div class="p-6 border-t border-slate-800 flex justify-end gap-4">
                <button
                    type="button"
                    @click="show = false"
                    class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-6 py-2 rounded-lg cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="button"
                    wire:loading.attr="disabled"
                    wire:target="saveAd"
                    wire:click="saveAd"
                    class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg cursor-pointer"
                >
                    <div class="flex items-center">
                        <x-loading
                            wire:loading
                            wire:target="saveAd"
                            class="loading-dots mr-2"
                        />
                        <x-icon
                            name="lucide.save"
                            wire:loading.remove
                            wire:target="saveAd"
                            class="mr-2"
                        />
                        Simpan
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>
