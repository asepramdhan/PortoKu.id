<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new class extends Component {
    use WithFileUploads;

    public string $name = "";
    public string $email = "";
    public ?string $about = "";
    public $photo;
    public ?TemporaryUploadedFile $previous_photo = null;

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->about = $user->about;
    }

    public function updatedPhoto(TemporaryUploadedFile $value): void
    {
        $this->previous_photo?->delete();
        $this->previous_photo = $value;
    }

    public function save()
    {
        $validated = $this->validate([
            "photo" => ["nullable", "image", "max:1024"],
            "name" => "required|string|max:255",
            "about" => "nullable|string|max:200", // Batasi 200 karakter
        ]);

        $user = Auth::user();

        if ($this->photo) {
            // Hapus foto lama jika ada
            if ($user->profile_photo_path) {
                Storage::disk("public")->delete($user->profile_photo_path);
            }
            // Simpan foto baru
            $validated["profile_photo_path"] = $this->photo->store(
                "profile-photos",
                "public",
            );
        }

        unset($validated["photo"]); // Hapus 'photo' dari data yang akan disimpan

        Auth::user()->fill($validated);

        if (Auth::user()->isDirty("email")) {
            Auth::user()->email_verified_at = null;
        }

        Auth::user()->save();

        $this->reset("photo", "previous_photo");
        // Beri notifikasi dengan event
        $this->dispatch("profile-updated");
    }
}; ?>

<div>
    <x-notification />

    <main class="flex-1 p-6 md:p-8">
        <h1 class="text-3xl font-bold text-white mb-6">Edit Profil</h1>
        <form wire:submit.prevent="save">
            <div class="card p-6 md:p-8">
                <div class="space-y-6">
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-300 mb-2"
                        >
                            Foto Profil
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                {{-- Spinner Overlay --}}
                                <div
                                    wire:loading
                                    wire:target="photo"
                                    class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"
                                >
                                    <x-loading class="loading-dots" />
                                </div>
                                {{-- Image Preview --}}
                                <div
                                    wire:loading.class="opacity-50"
                                    wire:target="photo"
                                >
                                    @if ($photo)
                                        <img
                                            src="{{ $photo->temporaryUrl() }}"
                                            class="w-20 h-20 rounded-full object-cover"
                                        />
                                    @elseif (Auth::user()->profile_photo_path)
                                        <img
                                            src="{{ asset("storage/" . Auth::user()->profile_photo_path) }}"
                                            class="w-20 h-20 rounded-full object-cover"
                                        />
                                    @else
                                        <div
                                            class="w-20 h-20 rounded-full bg-slate-700 flex items-center justify-center"
                                        >
                                            <x-icon
                                                name="lucide.user"
                                                class="w-10 h-10 text-slate-400"
                                            />
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <label
                                    for="photo"
                                    class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-4 py-2 rounded-lg text-sm cursor-pointer"
                                >
                                    Unggah Foto
                                </label>
                                <input
                                    type="file"
                                    id="photo"
                                    wire:model="photo"
                                    class="hidden"
                                />
                                <p class="text-xs text-slate-500 mt-2">
                                    JPG, GIF atau PNG. Ukuran maks 1MB.
                                </p>
                            </div>
                        </div>
                        @error("photo")
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div>
                        <label
                            for="name"
                            class="block text-sm font-medium text-slate-300 mb-2"
                        >
                            Nama
                        </label>
                        <input
                            type="text"
                            id="name"
                            wire:model="name"
                            class="form-input @error("name") input-error @enderror"
                        />
                        @error("name")
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-slate-300 mb-2"
                        >
                            Email
                        </label>
                        <input
                            type="email"
                            id="email"
                            wire:model="email"
                            class="form-input @error("email") input-error @enderror"
                            readonly
                        />
                        @error("email")
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div>
                        <label
                            for="about"
                            class="block text-sm font-medium text-slate-300 mb-2"
                        >
                            About
                        </label>
                        <textarea
                            type="about"
                            id="about"
                            wire:model="about"
                            class="form-input @error("about") input-error @enderror"
                            placeholder="Penulis di PortoKu.id yang berfokus pada..."
                        ></textarea>
                        @error("about")
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div
                    class="pt-6 mt-6 border-t border-slate-800 flex justify-end gap-4"
                    x-data="{ saved: false }"
                    @profile-updated.window="saved = true; setTimeout(() => saved = false, 2000)"
                >
                    <p
                        x-show="saved"
                        x-transition
                        class="text-sm text-slate-400"
                    >
                        Tersimpan.
                    </p>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors cursor-pointer"
                    >
                        <div class="flex items-center">
                            <x-loading
                                wire:loading
                                wire:target="save"
                                class="loading-dots mr-2"
                            />
                            <x-icon
                                name="lucide.save"
                                wire:loading.remove
                                wire:target="save"
                                class="mr-2"
                            />
                            Simpan Perubahan
                        </div>
                    </button>
                </div>
            </div>
        </form>
    </main>
</div>
