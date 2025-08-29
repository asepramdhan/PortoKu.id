<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public string $name = "";
    public string $email = "";
    public ?string $about = "";

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->about = $user->about;
    }

    public function save()
    {
        $this->validate([
            "name" => "required|string|max:255",
            "about" => "nullable|string|max:200", // Batasi 200 karakter
        ]);

        $user = Auth::user();
        $user->update([
            "name" => $this->name,
            "about" => $this->about,
        ]);

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
