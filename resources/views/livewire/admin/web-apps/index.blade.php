<?php

use App\Models\WebApp;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithPagination, WithFileUploads;

    // Properti untuk Modal & Form
    public bool $showWebAppModal = false;
    public ?WebApp $editingWebApp = null;
    public string $title = "",
        $slug = "",
        $description = "",
        $demo_link = "",
        $shopee_link = "",
        $tags = "";
    public $image_path;
    public bool $is_published = false;
    public bool $is_demo = false;

    // public function mount(): void
    // {
    //     $this->description = WebApp::first()->description;
    //     dd($this->description);
    //     if ($webApp->exists) {
    //         $this->editingWebApp = $webApp;
    //         $this->title = $webApp->title;
    //         $this->slug = $webApp->slug;
    //         $this->description = $webApp->description;
    //         $this->demo_link = $webApp->demo_link;
    //         $this->shopee_link = $webApp->shopee_link;
    //         $this->tags = $webApp->tags ? implode(", ", $webApp->tags) : "";
    //         $this->image_path = $webApp->image_path;
    //         $this->is_published = $webApp->is_published;
    //         $this->is_demo = $webApp->is_demo;
    //     }
    // }

    public function create(): void
    {
        $this->reset();
        $this->isEditMode = false;
        $this->dispatch("trix-clear", id: "description-editor");
        $this->showWebAppModal = true;
    }

    public function saveWebApp(): void
    {
        // dd($this->description);
        $this->slug = Str::slug($this->title);
        $rules = [
            "title" => "required|string|max:255",
            "slug" =>
                "required|string|unique:web_apps,slug," .
                $this->editingWebApp?->id,
            "description" => "required|string",
            "demo_link" => "required|url",
            "shopee_link" => "nullable|url",
            "tags" => "nullable|string",
            "image_path" =>
                $this->image_path && ! is_string($this->image_path)
                    ? "required|image|max:1024"
                    : "nullable",
            "is_published" => "required|boolean",
            "is_demo" => "required|boolean",
        ];

        $validated = $this->validate($rules);

        // Proses tags: ubah string "tag1, tag2" menjadi array
        $validated["tags"] = $this->tags
            ? array_map("trim", explode(",", $this->tags))
            : null;

        // Proses upload gambar jika ada file baru
        if ($this->image_path && ! is_string($this->image_path)) {
            // Hapus gambar lama jika sedang mengedit
            if ($this->editingWebApp?->image_path) {
                Storage::disk("public")->delete(
                    Str::after($this->editingWebApp->image_path, "/storage/"),
                );
            }
            $path = $this->image_path->store("web-app-images", "public");
            $validated["image_path"] = Storage::disk("public")->url($path);
        } else {
            // Jika tidak ada gambar baru, jangan ubah path gambar yang sudah ada
            unset($validated["image_path"]);
        }

        if ($this->editingWebApp) {
            $this->editingWebApp->update($validated);
            session()->flash("message", "Aplikasi web berhasil diperbarui.");
        } else {
            WebApp::create($validated);
            session()->flash("message", "Aplikasi web berhasil ditambahkan.");
        }

        $this->showWebAppModal = false;
    }

    public function edit(WebApp $webApp): void
    {
        $this->resetErrorBag();
        $this->editingWebApp = $webApp;
        $this->title = $webApp->title;
        $this->description = $webApp->description;
        // dd($this->description);
        $this->demo_link = $webApp->demo_link;
        $this->shopee_link = $webApp->shopee_link;
        $this->tags = $webApp->tags ? implode(", ", $webApp->tags) : "";
        $this->image_path = $webApp->image_path;
        $this->is_published = $webApp->is_published;
        $this->is_demo = $webApp->is_demo;

        $this->dispatch(
            "trix-set-content",
            id: "description-editor",
            content: $this->description,
        );
        $this->showWebAppModal = true;
    }

    public function delete(WebApp $webApp): void
    {
        if ($webApp->image_path) {
            Storage::disk("public")->delete(
                Str::after($webApp->image_path, "/storage/"),
            );
        }
        $webApp->delete();
        session()->flash("message", "Aplikasi web berhasil dihapus.");
    }

    public function toggleDemo(WebApp $webApp): void
    {
        $webApp->update(["is_demo" => ! $webApp->is_demo]);
    }

    public function togglePublished(WebApp $webApp): void
    {
        $webApp->update(["is_published" => ! $webApp->is_published]);
    }

    public function with(): array
    {
        return [
            "webApps" => WebApp::latest()->paginate(10),
        ];
    }
}; ?>

<div>
    <!-- Page Content -->
    <div class="flex flex-col md:flex-row justify-between md:items-center mb-6">
        <h1 class="text-3xl font-bold text-white">Kelola Aplikasi Web</h1>
        <button
            wire:click="create"
            class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors mt-4 md:mt-0 cursor-pointer"
        >
            <x-icon name="lucide.plus-circle" class="h-5 w-5" />
            Tambah Aplikasi
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
                        <th>Link Demo</th>
                        <th>Link Shopee</th>
                        <th>Status | Demo</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($webApps as $app)
                        <tr wire:key="{{ $app->id }}">
                            <td>
                                <img
                                    src="{{ $app->image_path ?? "https://placehold.co/120x80/1E293B/FFFFFF?text=No+Img" }}"
                                    alt="Thumbnail"
                                    class="w-20 h-12 object-cover rounded-md"
                                />
                            </td>
                            <td class="font-semibold text-white truncate">
                                {{ Str::limit($app->title, 20) }}
                            </td>
                            <td class="truncate">
                                <a
                                    href="{{ $app->demo_link }}"
                                    target="_blank"
                                    class="text-sky-400 hover:underline"
                                >
                                    Lihat Demo
                                </a>
                            </td>
                            <td class="truncate">
                                @if ($app->shopee_link)
                                    <a
                                        href="{{ $app->shopee_link }}"
                                        target="_blank"
                                        class="text-sky-400 hover:underline"
                                    >
                                        Lihat di Shopee
                                    </a>
                                @else
                                    <div class="text-center">-</div>
                                @endif
                            </td>
                            <td>
                                <button
                                    wire:click="toggleDemo({{ $app->id }})"
                                    class="flex items-center gap-2 mb-2 text-sm cursor-pointer {{ $app->is_demo ? "text-blue-400" : "text-slate-400" }}"
                                >
                                    <div
                                        class="relative w-10 h-5 rounded-full {{ $app->is_demo ? "bg-blue-500/30" : "bg-slate-700" }}"
                                    >
                                        <div
                                            class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform {{ $app->is_demo ? "translate-x-5" : "translate-x-1" }}"
                                        ></div>
                                    </div>
                                    <span>
                                        {{ $app->is_demo ? "Demo" : "Produksi" }}
                                    </span>
                                </button>
                                <button
                                    wire:click="togglePublished({{ $app->id }})"
                                    class="flex items-center gap-2 text-sm cursor-pointer {{ $app->is_published ? "text-green-400" : "text-slate-400" }}"
                                >
                                    <div
                                        class="relative w-10 h-5 rounded-full {{ $app->is_published ? "bg-green-500/30" : "bg-slate-700" }}"
                                    >
                                        <div
                                            class="absolute w-3.5 h-3.5 bg-white rounded-full top-0.5 transition-transform {{ $app->is_published ? "translate-x-5" : "translate-x-1" }}"
                                        ></div>
                                    </div>
                                    <span>
                                        {{ $app->is_published ? "Published" : "Draft" }}
                                    </span>
                                </button>
                            </td>
                            <td>
                                <div class="flex space-x-2">
                                    <button
                                        wire:click="edit({{ $app->id }})"
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
                                                $wire.delete({{ $app->id }})
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
                                Belum ada aplikasi web yang ditambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $webApps->links("livewire.tailwind-custom") }}
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div
        x-data="{ show: @entangle("showWebAppModal") }"
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
                    {{ $editingWebApp ? "Edit Aplikasi Web" : "Tambah Aplikasi Web Baru" }}
                </h2>
            </div>
            <form
                wire:submit.prevent="saveWebApp"
                class="flex-grow overflow-y-auto p-6 space-y-4"
            >
                <div>
                    <label for="title" class="form-label">Judul Aplikasi</label>
                    <input
                        type="text"
                        id="title"
                        wire:model="title"
                        class="form-input @error("title") input-error @enderror"
                        required
                    />
                    @error("title")
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label for="description-editor" class="label">
                        Deskripsi Aplikasi
                    </label>
                    <x-trix-web-apps
                        id="description-editor"
                        wire:model="description"
                    />
                    @error("description")
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="demo_link" class="form-label">Link Demo</label>
                    <input
                        type="url"
                        id="demo_link"
                        wire:model="demo_link"
                        class="form-input @error("demo_link") input-error @enderror"
                        placeholder="https://gofutsal.portoku.id"
                        required
                    />
                    @error("demo_link")
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label for="shopee_link" class="form-label">
                        Link Shopee
                    </label>
                    <input
                        type="url"
                        id="shopee_link"
                        wire:model="shopee_link"
                        class="form-input @error("shopee_link") input-error @enderror"
                        placeholder="https://shopee.co.id/..."
                        required
                    />
                    @error("shopee_link")
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label for="tags" class="form-label">
                        Tags (pisahkan dengan koma)
                    </label>
                    <input
                        type="text"
                        id="tags"
                        wire:model="tags"
                        class="form-input @error("tags") input-error @enderror"
                        placeholder="Laravel, Livewire, Volt..."
                    />
                    @error("tags")
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
                <div class="flex gap-4">
                    <label class="form-label flex items-center gap-2">
                        <input
                            type="checkbox"
                            wire:model="is_published"
                            class="form-checkbox"
                        />
                        Publish
                    </label>
                    <label class="form-label flex items-center gap-2">
                        <input
                            type="checkbox"
                            wire:model="is_demo"
                            class="form-checkbox"
                        />
                        Demo
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
                    wire:target="saveWebApp"
                    wire:click="saveWebApp"
                    class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg cursor-pointer"
                >
                    <div class="flex items-center">
                        <x-loading
                            wire:loading
                            wire:target="saveWebApp"
                            class="loading-dots mr-2"
                        />
                        <x-icon
                            name="lucide.save"
                            wire:loading.remove
                            wire:target="saveWebApp"
                            class="mr-2"
                        />
                        Simpan
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>
