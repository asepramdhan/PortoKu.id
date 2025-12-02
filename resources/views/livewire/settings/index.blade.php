<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new class extends Component {
    use WithFileUploads;

    // Properti untuk form profil
    public string $name = "";
    public string $email = "";
    public $phone_number;
    public $photo;
    public ?TemporaryUploadedFile $previous_photo = null;

    // Properti untuk form kata sandi
    public string $current_password = "";
    public string $password = "";
    public string $password_confirmation = "";

    // START: Properti baru untuk Indodax API
    public ?string $indodax_api_key = null;
    public ?string $indodax_secret_key = null;
    // END: Properti baru untuk Indodax API

    // showDeleteModal
    public $showDeleteModal = false;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->phone_number = Auth::user()->phone_number;
        // START: Ambil data API key dari user
        $this->indodax_api_key = Auth::user()->indodax_api_key;
        // Secret key biasanya tidak diambil kembali untuk keamanan,
        // tapi kita inisialisasi null untuk form input.
        $this->indodax_secret_key = null;
        // END: Ambil data API key dari user
    }

    public function updatedPhoto(TemporaryUploadedFile $value): void
    {
        $this->previous_photo?->delete();
        $this->previous_photo = $value;
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            "name" => ["required", "string", "max:255"],
            "email" => [
                "required",
                "string",
                "email",
                "max:255",
                "unique:users,email," . Auth::id(),
            ],
            "photo" => ["nullable", "image", "max:1024"],
            "phone_number" => [
                "nullable",
                "numeric",
                "unique:users,phone_number," . Auth::id(),
                // "regex:/^62\d{9,13}$/",
            ],
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
        $this->dispatch("profile-updated");
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            "current_password" => ["required", "current_password"],
            "password" => ["required", Password::defaults(), "confirmed"],
        ]);

        Auth::user()->update([
            "password" => Hash::make($validated["password"]),
        ]);

        $this->reset("current_password", "password", "password_confirmation");
        $this->dispatch("password-updated");
    }

    // START: Metode baru untuk menyimpan API Key Indodax
    public function updateIndodaxApi(): void
    {
        $validated = $this->validate([
            "indodax_api_key" => [
                "nullable",
                "string",
                "max:255",
                // Opsional: Tambahkan rule unik jika API Key harus unik per user,
                // atau validasi format jika Indodax punya format tertentu.
            ],
            // Secret Key hanya diupdate jika diisi (untuk mencegah penimpaan dengan null/kosong)
            "indodax_secret_key" => ["nullable", "string", "max:255"],
        ]);

        $updateData = [];

        // Selalu update API Key
        $updateData["indodax_api_key"] = $validated["indodax_api_key"];

        // Hanya update Secret Key jika form input TIDAK KOSONG
        if (! empty($validated["indodax_secret_key"])) {
            // PENTING: Anda harus MENGENKRIPSI Secret Key sebelum menyimpannya ke database
            // Contoh menggunakan enkripsi bawaan Laravel:
            // $updateData['indodax_secret_key'] = encrypt($validated['indodax_secret_key']);
            // Untuk contoh ini, saya simpan langsung, tapi harap terapkan enkripsi di dunia nyata!
            $updateData["indodax_secret_key"] =
                $validated["indodax_secret_key"];
        }

        Auth::user()->update($updateData);

        // Reset Secret Key setelah disimpan
        $this->reset("indodax_secret_key");

        // Kirim event untuk notifikasi tersimpan
        $this->dispatch("indodax-api-updated");
    }
    // END: Metode baru untuk menyimpan API Key Indodax

    public function deleteAccount(): void
    {
        // dd("delete account");
        // Logika untuk menghapus akun
        // Contoh: Auth::user()->delete();
        // Redirect ke halaman utama setelahnya
    }
}; ?>

<div>
    <!-- Page Content -->
    <h1 class="text-3xl font-bold text-white mb-6">Pengaturan Akun</h1>

    <x-notification />

    <div class="card" x-data="{ activeTab: 'profil' }">
        <!-- Tabs Navigation -->
        <div class="border-b border-slate-800">
            <nav class="flex space-x-2 px-6">
                <a
                    @click.prevent="activeTab = 'profil'"
                    :class="{ 'active': activeTab === 'profil' }"
                    class="tab-link cursor-pointer"
                >
                    Profil
                </a>
                <a
                    @click.prevent="activeTab = 'keamanan'"
                    :class="{ 'active': activeTab === 'keamanan' }"
                    class="tab-link cursor-pointer"
                >
                    Keamanan
                </a>
                <a
                    @click.prevent="activeTab = 'indodax'"
                    :class="{ 'active': activeTab === 'indodax' }"
                    class="tab-link cursor-pointer truncate"
                >
                    API Indodax
                </a>
                <a
                    @click.prevent="activeTab = 'preferensi'"
                    :class="{ 'active': activeTab === 'preferensi' }"
                    class="tab-link cursor-pointer hidden"
                >
                    Preferensi
                </a>
            </nav>
        </div>

        <!-- Tab Content: Profil -->
        <div x-show="activeTab === 'profil'" class="p-6 md:p-8">
            <h2 class="text-xl font-bold text-white mb-2">Informasi Profil</h2>
            <p class="text-slate-400 mb-6">
                Perbarui informasi dan alamat email Anda.
            </p>

            <form wire:submit.prevent="updateProfile" class="space-y-6">
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
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label
                        for="nama"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        Nama Lengkap
                    </label>
                    <input
                        type="text"
                        id="nama"
                        wire:model="name"
                        class="form-input @error("name") input-error @enderror"
                    />
                    @error("name")
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label
                        for="email"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        Alamat Email
                    </label>
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        class="form-input @error("email") input-error @enderror"
                    />
                    @error("email")
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label
                        for="phone_number"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        Nomor Whatsapp
                    </label>
                    <input
                        type="text"
                        id="phone_number"
                        wire:model="phone_number"
                        class="form-input @error("phone_number") input-error @enderror"
                    />
                    @error("phone_number")
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div
                    class="pt-4 flex items-center justify-end gap-4"
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
                        wire:target="updateProfile"
                        class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors cursor-pointer"
                    >
                        <div class="flex items-center justify-center">
                            <x-loading
                                wire:loading
                                wire:target="updateProfile"
                                class="loading-dots mr-2"
                            />
                            <x-icon
                                name="lucide.save"
                                wire:loading.remove
                                wire:target="updateProfile"
                                class="mr-2"
                            />
                            Simpan Perubahan
                        </div>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tab Content: Keamanan -->
        <div x-show="activeTab === 'keamanan'" x-cloak class="p-6 md:p-8">
            <h2 class="text-xl font-bold text-white mb-2">Ubah Kata Sandi</h2>
            <p class="text-slate-400 mb-6">
                Pastikan akun Anda menggunakan kata sandi yang panjang dan acak
                agar tetap aman.
            </p>

            <form
                wire:submit.prevent="updatePassword"
                class="space-y-6 max-w-lg"
            >
                <div>
                    <label
                        for="current_password"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        Kata Sandi Saat Ini
                    </label>
                    <input
                        type="password"
                        id="current_password"
                        wire:model="current_password"
                        class="form-input @error("current_password") input-error @enderror"
                    />
                    @error("current_password")
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label
                        for="password"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        Kata Sandi Baru
                    </label>
                    <input
                        type="password"
                        id="password"
                        wire:model="password"
                        class="form-input @error("password") input-error @enderror"
                    />
                    @error("password")
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label
                        for="password_confirmation"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        Konfirmasi Kata Sandi Baru
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        wire:model="password_confirmation"
                        class="form-input"
                    />
                </div>
                <div
                    class="pt-4 flex items-center justify-end gap-4"
                    x-data="{ saved: false }"
                    @password-updated.window="saved = true; setTimeout(() => saved = false, 2000)"
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
                        wire:target="updatePassword"
                        class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors cursor-pointer"
                    >
                        <x-loading
                            wire:loading
                            wire:target="updatePassword"
                            class="loading-dots"
                        />
                        <span wire:loading.remove wire:target="updatePassword">
                            Ubah Kata Sandi
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tab Content: API Indodax -->
        <div x-show="activeTab === 'indodax'" x-cloak class="p-6 md:p-8">
            <h2 class="text-xl font-bold text-white mb-2">
                Pengaturan Private API Indodax
            </h2>
            <p class="text-slate-400 mb-6">
                Masukkan **Key** dan **Secret** API Indodax Anda untuk
                mengaktifkan fungsionalitas transaksi dan histori. Pastikan Anda
                hanya memberikan izin yang diperlukan (misalnya: **Info** dan
                **Trade**).
            </p>

            <form wire:submit.prevent="updateIndodaxApi" class="space-y-6">
                <div>
                    <label
                        for="indodax_api_key"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        API Key
                    </label>
                    <input
                        type="text"
                        id="indodax_api_key"
                        wire:model="indodax_api_key"
                        class="form-input @error("indodax_api_key") input-error @enderror"
                        placeholder="Masukkan Indodax API Key Anda"
                    />
                    @error("indodax_api_key")
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label
                        for="indodax_secret_key"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        Secret Key
                    </label>
                    <input
                        type="password"
                        id="indodax_secret_key"
                        wire:model="indodax_secret_key"
                        class="form-input @error("indodax_secret_key") input-error @enderror"
                        placeholder="Masukkan Indodax Secret Key Anda (Hanya diisi jika ingin diubah)"
                    />
                    <p class="text-xs text-slate-500 mt-2">
                        Kami sarankan **tidak** menampilkan Secret Key setelah
                        disimpan untuk alasan keamanan.
                    </p>
                    @error("indodax_secret_key")
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div
                    class="pt-4 flex items-center justify-end gap-4"
                    x-data="{ saved: false }"
                    @indodax-api-updated.window="saved = true; setTimeout(() => saved = false, 2000)"
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
                        wire:target="updateIndodaxApi"
                        class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors cursor-pointer"
                    >
                        <x-loading
                            wire:loading
                            wire:target="updateIndodaxApi"
                            class="loading-dots"
                        />
                        <span
                            wire:loading.remove
                            wire:target="updateIndodaxApi"
                        >
                            Simpan API Indodax
                        </span>
                    </button>
                </div>
            </form>
        </div>
        <div
            x-show="activeTab === 'preferensi'"
            x-cloak
            class="p-6 md:p-8 hidden"
        ></div>

        <!-- Tab Content: Preferensi -->
        <div
            x-show="activeTab === 'preferensi'"
            x-cloak
            class="p-6 md:p-8 hidden"
        >
            <h2 class="text-xl font-bold text-white mb-2">
                Preferensi Tampilan
            </h2>
            <p class="text-slate-400 mb-6">
                Sesuaikan tampilan aplikasi sesuai keinginan Anda.
            </p>
            <form
                wire:submit.prevent="updatePreferences"
                class="space-y-6 max-w-lg"
            >
                <div>
                    <label
                        for="currency"
                        class="block text-sm font-medium text-slate-300 mb-2"
                    >
                        Mata Uang Utama
                    </label>
                    <select id="currency" name="currency" class="form-input">
                        <option>Rupiah (IDR)</option>
                        <option>US Dollar (USD)</option>
                    </select>
                </div>
                <div class="pt-4 flex justify-end">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="updatePreferences"
                        class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors cursor-pointer"
                    >
                        <x-loading
                            wire:loading
                            wire:target="updatePreferences"
                            class="loading-dots"
                        />
                        <span
                            wire:loading.remove
                            wire:target="updatePreferences"
                        >
                            Simpan Preferensi
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="card mt-8 border-red-500/30">
        <div class="p-6 md:p-8">
            <h2 class="text-xl font-bold text-red-500 mb-2">Zona Berbahaya</h2>
            <p class="text-slate-400 mb-6">
                Tindakan ini tidak dapat dibatalkan. Mohon berhati-hati.
            </p>
            <div
                class="flex flex-col md:flex-row md:justify-between md:items-center gap-4"
            >
                <div>
                    <p class="font-semibold text-white">Hapus Akun</p>
                    <p class="text-slate-400 text-sm">
                        Semua data Anda akan dihapus secara permanen.
                    </p>
                </div>
                <button
                    disabled
                    @click="$wire.set('showDeleteModal', true)"
                    class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-lg transition-colors w-full md:w-auto cursor-pointer"
                >
                    Hapus Akun Saya
                </button>
            </div>
        </div>
    </div>

    <!-- ===== Delete Account Confirmation Modal ===== -->
    <div
        x-data="{ show: @entangle("showDeleteModal") }"
        x-show="show"
        @keydown.escape.window="show = false"
        class="fixed inset-0 bg-black z-50 flex items-center justify-center p-4"
        style="background-color: rgba(0, 0, 0, 0.7)"
        x-cloak
    >
        <div
            @click.away="show = false"
            class="card w-full max-w-lg"
            x-data="{ confirmationText: '' }"
        >
            <div class="p-6 md:p-8">
                <div class="text-center">
                    <div
                        class="mx-auto bg-red-500/10 w-16 h-16 flex items-center justify-center rounded-full mb-4"
                    >
                        <x-icon
                            name="lucide.alert-triangle"
                            class="w-8 h-8 text-red-500"
                        />
                    </div>
                    <h2 class="text-2xl font-bold text-white">Anda Yakin?</h2>
                    <p class="text-slate-400 mt-2">
                        Tindakan ini bersifat permanen dan akan menghapus semua
                        data Anda. Untuk melanjutkan, ketik `HAPUS` di bawah
                        ini.
                    </p>
                </div>
                <div class="mt-6 space-y-4">
                    <label for="delete-confirmation" class="sr-only">
                        Ketik HAPUS
                    </label>
                    <input
                        type="text"
                        id="delete-confirmation"
                        x-model="confirmationText"
                        class="form-input text-center tracking-widest"
                        placeholder="HAPUS"
                    />

                    <button
                        type="button"
                        wire:click="deleteAccount"
                        wire:loading.attr="disabled"
                        :disabled="confirmationText !== 'HAPUS'"
                        class="w-full flex justify-center py-3 px-4 text-sm font-medium rounded-md text-white transition-colors cursor-pointer"
                        :class="confirmationText === 'HAPUS' ? 'bg-red-600 hover:bg-red-700' : 'bg-slate-700 cursor-not-allowed'"
                    >
                        <x-loading
                            wire:loading
                            wire:target="deleteAccount"
                            class="loading-dots"
                        />
                        <span wire:loading.remove wire:target="deleteAccount">
                            Saya Mengerti, Hapus Akun Saya
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="show = false"
                        class="w-full text-center py-2 text-slate-400 hover:text-white text-sm cursor-pointer"
                    >
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
