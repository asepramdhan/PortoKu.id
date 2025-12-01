<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use Illuminate\Validation\ValidationException;

new class extends Component {
    use WithPagination, WithFileUploads;

    public ?User $deleting = null;
    public $showDeleteModal = false;

    #[Url(as: "q")]
    public string $search = "";

    // === INLINE EDITING PROPERTIES ===
    public $editingId = null;
    public $editingField = null;
    public $editingValue = "";

    // === PHOTO UPLOAD PROPERTIES ===
    public $photoUpload;
    public $userIdForPhotoUpload;

    public function editField($userId, $field)
    {
        // Mencegah admin mengedit perannya sendiri atau nama pengguna lain
        if ($userId === Auth::id() && $field === "is_admin") {
            return;
        }
        if ($userId !== Auth::id() && $field === "name") {
            return;
        }

        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $this->editingId = $userId;
        $this->editingField = $field;

        if ($field === "name") {
            $this->editingValue = $user->name;
        } elseif ($field === "is_admin") {
            $this->editingValue = $user->is_admin;
        }
    }

    public function saveField()
    {
        if (! $this->editingId || ! $this->editingField) {
            return;
        }

        $user = User::find($this->editingId);

        if ($this->editingField === "is_admin") {
            $this->validate(["editingValue" => "required|boolean"]);
            $user->update(["is_admin" => $this->editingValue]);
            session()->flash("message", "Peran pengguna berhasil diperbarui.");
        }

        if ($this->editingField === "name") {
            $this->validate(["editingValue" => "required|string|max:255"]);
            $user->update(["name" => $this->editingValue]);
            session()->flash("message", "Nama berhasil diperbarui.");
        }

        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->reset("editingId", "editingField", "editingValue");
    }

    // Metode ini akan berjalan otomatis saat gambar baru dipilih
    public function updatedPhotoUpload()
    {
        try {
            $this->validate([
                "photoUpload" => "required|image|max:1024", // Maks 1MB
            ]);
        } catch (ValidationException $e) {
            // Jika validasi gagal, tampilkan notifikasi error
            session()->flash(
                "error",
                "Gagal: Ukuran gambar terlalu besar (maks 1MB).",
            );
            // Reset properti unggahan untuk membatalkan
            $this->reset("photoUpload", "userIdForPhotoUpload");
            return;
        }

        $user = User::find($this->userIdForPhotoUpload);
        if ($user) {
            // Hapus foto lama jika ada
            if ($user->profile_photo_path) {
                Storage::disk("public")->delete($user->profile_photo_path);
            }
            // Simpan foto baru
            $path = $this->photoUpload->store("profile-photos", "public");
            $user->update(["profile_photo_path" => $path]);
            $this->reset("photoUpload", "userIdForPhotoUpload");
            session()->flash("message", "Foto profil berhasil diperbarui.");
        }
    }

    public function prepareToDelete(User $user)
    {
        // Mencegah admin menghapus akunnya sendiri
        if ($user->id === Auth::id()) {
            return;
        }

        $this->deleting = $user;
        $this->showDeleteModal = true;
    }

    public function deleteUser()
    {
        if ($this->deleting) {
            $this->deleting->delete();
            session()->flash("message", "Pengguna berhasil dihapus.");
        }
        $this->showDeleteModal = false;
    }

    public function with(): array
    {
        $query = User::orderBy("created_at", "desc");

        if ($this->search) {
            $query->where(function ($q) {
                $q->where("name", "like", "%" . $this->search . "%")->orWhere(
                    "email",
                    "like",
                    "%" . $this->search . "%",
                );
            });
        }

        return [
            "users" => $query->paginate(10),
        ];
    }
}; ?>

<div>
    <!-- Page Content -->
    <div class="flex flex-col md:flex-row justify-between md:items-center mb-6">
        <h1 class="text-3xl font-bold text-white">Kelola Pengguna</h1>
    </div>

    <!-- Search Input -->
    <div class="mb-6">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Cari pengguna berdasarkan nama atau email..."
            class="form-input w-full md:w-1/3"
        />
    </div>

    <x-notification />

    <!-- Users Table -->
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="truncate">Gambar Profil</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th class="truncate">Tanggal Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="{{ $user->id }}">
                            <td class="truncate">
                                @if ($user->id === Auth::id())
                                    <label
                                        for="photo-upload-{{ $user->id }}"
                                        class="cursor-pointer relative"
                                        wire:click="$set('userIdForPhotoUpload', {{ $user->id }})"
                                    >
                                        @if ($userIdForPhotoUpload === $user->id)
                                            <div
                                                wire:loading
                                                wire:target="photoUpload"
                                                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"
                                            >
                                                <x-loading
                                                    class="loading-dots text-sky-400"
                                                />
                                            </div>
                                        @endif

                                        @if ($user->profile_photo_path)
                                            <div
                                                class="group hover:opacity-75 relative"
                                            >
                                                <img
                                                    src="{{ asset("storage/" . $user->profile_photo_path) }}"
                                                    alt="Foto Profil"
                                                    class="w-12 h-auto object-cover rounded-full"
                                                />
                                                <div
                                                    wire:loading.remove
                                                    wire:target="photoUpload"
                                                    class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 hidden group-hover:block"
                                                >
                                                    <x-icon
                                                        name="lucide.upload-cloud"
                                                        class="text-sky-400"
                                                    />
                                                </div>
                                            </div>
                                        @else
                                            <div
                                                class="group hover:opacity-75 relative"
                                            >
                                                <img
                                                    src="https://placehold.co/48x48/0EA5E9/FFFFFF?text={{ substr($user->name, 0, 1) }}"
                                                    alt="Avatar"
                                                    class="w-12 h-auto rounded-full"
                                                />
                                                <div
                                                    class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 hidden group-hover:block"
                                                >
                                                    <x-icon
                                                        name="lucide.upload-cloud"
                                                        class="text-orange-400"
                                                    />
                                                </div>
                                            </div>
                                        @endif
                                    </label>
                                    <input
                                        type="file"
                                        id="photo-upload-{{ $user->id }}"
                                        wire:model="photoUpload"
                                        class="hidden"
                                    />
                                @else
                                    <img
                                        src="{{ $user->profile_photo_path ? asset("storage/" . $user->profile_photo_path) : "https://placehold.co/48x48/0EA5E9/FFFFFF?text=" . substr($user->name, 0, 1) }}"
                                        alt="Avatar"
                                        class="w-12 h-12 rounded-full"
                                    />
                                @endif
                            </td>
                            <td class="truncate">
                                @if ($editingId === $user->id && $editingField === "name")
                                    <input
                                        type="text"
                                        wire:model="editingValue"
                                        wire:keydown.enter="saveField"
                                        wire:keydown.escape="cancelEdit"
                                        class="form-input text-sm p-1"
                                        x-init="$nextTick(() => $el.focus())"
                                        x-ref="editInput{{ $user->id }}_name"
                                        @click.away="$wire.cancelEdit()"
                                        x-trap.noscroll
                                    />
                                @else
                                    <p
                                        @if($user->id === Auth::id()) wire:click="editField({{ $user->id }}, 'name')" class="font-semibold text-white cursor-pointer hover:bg-slate-700 p-1 rounded" @else class="font-semibold text-white p-1" @endif
                                    >
                                        {{ Str::limit($user->name, 10) }}
                                    </p>
                                @endif
                            </td>
                            <td class="text-slate-300">
                                {{ Str::limit($user->email, 10) }}
                            </td>
                            <td class="truncate">
                                @if ($editingId === $user->id && $editingField === "is_admin")
                                    <select
                                        wire:model="editingValue"
                                        wire:change="saveField"
                                        class="form-input bg-slate-800 text-white appearance-none text-sm p-1"
                                        x-init="$nextTick(() => $el.focus())"
                                        x-ref="editInput{{ $user->id }}_is_admin"
                                        @click.away="$wire.cancelEdit()"
                                        x-trap.noscroll
                                    >
                                        <option value="1">Admin</option>
                                        <option value="0">Pengguna</option>
                                    </select>
                                @else
                                    <div
                                        wire:click="editField({{ $user->id }}, 'is_admin')"
                                        class="{{ $user->id !== Auth::id() ? "cursor-pointer" : "cursor-not-allowed" }}"
                                    >
                                        @if ($user->is_admin)
                                            <span
                                                class="status-badge bg-sky-500/20 text-sky-400"
                                            >
                                                Admin
                                            </span>
                                        @else
                                            <span
                                                class="status-badge bg-slate-500/20 text-slate-400"
                                            >
                                                Pengguna
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="text-slate-300 truncate">
                                {{ $user->created_at->format("d M Y") }}
                            </td>
                            <td>
                                <div class="flex space-x-2">
                                    {{-- Mencegah tombol hapus muncul untuk admin yang sedang login --}}
                                    @if ($user->id !== Auth::id())
                                        <button
                                            wire:click="prepareToDelete({{ $user->id }})"
                                            class="text-slate-400 hover:text-red-500 cursor-pointer"
                                        >
                                            <x-icon
                                                name="lucide.trash-2"
                                                class="w-5 h-5"
                                            />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="text-center py-8 text-slate-400"
                            >
                                Tidak ada pengguna yang cocok dengan pencarian
                                Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="p-4 border-t border-slate-800">
            {{ $users->links("livewire.tailwind-custom") }}
        </div>
    </div>

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
                <h2 class="text-2xl font-bold text-white">Hapus Pengguna?</h2>
                <p class="text-slate-400 mt-2">
                    Anda yakin ingin menghapus pengguna ini? Semua data terkait
                    (transaksi, postingan, dll.) akan ikut terhapus.
                </p>
                <div class="mt-6 flex justify-center gap-4">
                    <button
                        type="button"
                        @click="show = false"
                        class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-6 py-2 rounded-lg w-full"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="deleteUser"
                        wire:loading.attr="disabled"
                        wire:target="deleteUser"
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-lg w-full"
                    >
                        <div class="flex items-center justify-center">
                            <x-loading
                                wire:loading
                                wire:target="deleteUser"
                                class="loading-dots mr-2"
                            />
                            <x-icon
                                name="lucide.trash-2"
                                wire:loading.remove
                                wire:target="deleteUser"
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
