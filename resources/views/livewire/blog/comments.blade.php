<?php

use App\Models\Comment;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public $post;
    public string $commentContent = "";

    public ?Comment $editing = null;
    public string $editingContent = "";

    public function addComment(): void
    {
        // Pastikan user sudah login
        if (! Auth::check()) {
            $this->redirect("/login", navigation: true);
            return;
        }

        $validated = $this->validate([
            "commentContent" => "required|string|max:1000",
        ]);

        // Buat komentar baru
        $this->post->comments()->create([
            "user_id" => Auth::id(),
            "content" => $validated["commentContent"],
        ]);

        // Kosongkan textarea setelah submit
        $this->reset("commentContent");

        // Hapus cache pagination agar komentar baru muncul di halaman pertama
        $this->resetPage();
    }

    public function edit(Comment $comment): void
    {
        // Keamanan: Pastikan hanya pemilik komentar yang bisa mengedit
        if (Auth::id() !== $comment->user_id) {
            abort(403); // Akses ditolak
        }

        // Siapkan state untuk inline editing
        $this->editing = $comment;
        $this->editingContent = $comment->content;
    }

    public function update(): void
    {
        // Pastikan kita sedang dalam mode edit
        if (! $this->editing) {
            return;
        }

        // Keamanan: Cek lagi izin sebelum update
        if (Auth::id() !== $this->editing->user_id) {
            abort(403); // Akses ditolak
        }

        // Validasi input
        $validated = $this->validate([
            "editingContent" => "required|string|max:1000",
        ]);

        // Update komentar di database
        $this->editing->update([
            "content" => $validated["editingContent"],
        ]);

        // Keluar dari mode edit
        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        // Reset state editing
        $this->reset("editing", "editingContent");
    }

    public function delete(Comment $comment): void
    {
        // Keamanan: Pastikan hanya pemilik komentar atau admin yang bisa menghapus
        // Kita gunakan Gate atau Policy untuk best practice, tapi ini cukup untuk sekarang
        if (! Auth::user() !== $comment->user_id && ! Auth::user()?->is_admin) {
            abort(403); // Akses ditolak
        }

        $comment->delete();
    }

    public function with(): array
    {
        return [
            "comments" => $this->post
                ->comments()
                ->with("user")
                ->latest()
                ->paginate(5),
        ];
    }
}; ?>

<div class="mt-16 pt-8 border-t border-slate-800">
    <h2 class="text-2xl font-bold text-white mb-6">
        Komentar ({{ $comments->total() }})
    </h2>

    <!-- Form untuk Menambah Komentar -->
    @auth
        <form wire:submit.prevent="addComment" class="mb-8">
            <textarea
                wire:model="commentContent"
                rows="4"
                class="form-input"
                placeholder="Tulis komentar Anda di sini..."
                required
            ></textarea>
            @error("commentContent")
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="mt-4 bg-sky-500 hover:bg-sky-600 text-white font-semibold px-5 py-2.5 rounded-lg cursor-pointer transition-colors"
            >
                <span wire:loading.remove wire:target="addComment">
                    Kirim Komentar
                </span>
                <span wire:loading wire:target="addComment">Mengirim...</span>
            </button>
        </form>
    @else
        <div class="mb-8 p-4 bg-slate-800 rounded-lg text-center">
            <p class="text-slate-400">
                Silakan
                <a
                    href="{{ route("login") }}"
                    wire:navigate
                    class="text-sky-400 hover:underline font-semibold"
                >
                    login
                </a>
                atau
                <a
                    href="{{ route("register") }}"
                    wire:navigate
                    class="text-sky-400 hover:underline font-semibold"
                >
                    daftar
                </a>
                untuk berpartisipasi dalam diskusi.
            </p>
        </div>
    @endauth

    <!-- Daftar Komentar yang Sudah Ada -->
    <div class="space-y-4">
        @forelse ($comments as $comment)
            <div
                class="flex items-start space-x-4 p-4 border-b border-slate-800"
                wire:key="comment-{{ $comment->id }}"
            >
                <img
                    src="{{ $comment->user->profile_photo_path ? asset("storage/" . $comment->user->profile_photo_path) : "https://placehold.co/48x48/0EA5E9/FFFFFF?text=" . substr($comment->user->name, 0, 1) }}"
                    alt="{{ $comment->user->name }}"
                    class="w-10 h-10 rounded-full flex-shrink-0 mt-1"
                />

                <div class="flex-grow">
                    <div class="flex items-baseline justify-between">
                        <div class="flex items-baseline space-x-2">
                            <p class="font-semibold text-white">
                                {{ $comment->user->name }}
                            </p>
                            <span class="text-xs text-slate-400">
                                {{ $comment->created_at->diffForHumans() }}
                            </span>
                        </div>

                        {{-- Tombol Edit hanya muncul untuk pemilik komentar --}}
                        @if (Auth::id() === $comment->user_id || (Auth::user()?->is_admin && ! $editing?->is($comment)))
                            <div
                                class="flex items-center space-x-2 flex-shrink-0"
                            >
                                <button
                                    wire:click="edit({{ $comment->id }})"
                                    class="text-xs text-sky-400 cursor-pointer"
                                >
                                    <x-icon
                                        name="lucide.square-pen"
                                        class="h-4 w-4"
                                    />
                                </button>
                                <span class="text-xs text-slate-600">|</span>
                                <button
                                    x-on:click="
                                        if (confirm('Anda yakin ingin menghapus komentar ini?')) {
                                            $wire.delete({{ $comment->id }})
                                        }
                                    "
                                    class="text-xs text-red-500 cursor-pointer"
                                >
                                    <x-icon
                                        name="lucide.trash-2"
                                        class="h-4 w-4"
                                    />
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- "SAKLAR" ANTARA TAMPILAN BIASA DAN FORM EDIT --}}
                    @if ($editing?->is($comment))
                        {{-- TAMPILAN SAAT EDIT --}}
                        <div class="mt-2">
                            <textarea
                                wire:model="editingContent"
                                rows="3"
                                class="form-input"
                                required
                            ></textarea>
                            @error("editingContent")
                                <p class="mt-1 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror

                            <div class="flex items-center gap-2 mt-2">
                                <button
                                    wire:click="update"
                                    class="text-xs bg-sky-500 hover:bg-sky-600 text-white font-semibold px-3 py-1 rounded-md cursor-pointer transition-colors"
                                >
                                    Simpan
                                </button>
                                <button
                                    wire:click="cancelEdit"
                                    class="text-xs text-slate-400 hover:underline cursor-pointer"
                                >
                                    Batal
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- TAMPILAN BIASA --}}
                        <div
                            class="prose prose-sm prose-invert mt-2 max-w-none text-slate-300"
                        >
                            <p>{{ $comment->content }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-slate-400 text-center py-8">
                Belum ada komentar. Jadilah yang pertama!
            </p>
        @endforelse
    </div>

    <!-- Pagination -->
    @if ($comments->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $comments->links("livewire.tailwind-custom") }}
        </div>
    @endif
</div>
