<?php

use App\Models\Comment;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

new class extends Component {
    use WithPagination;

    #[Url]
    public string $search = "";

    // Properti untuk inline edit
    public ?Comment $editing = null;
    public string $editingContent = "";

    // Properti untuk hapus
    public ?Comment $deleting = null;
    public bool $showDeleteModal = false;

    public ?Comment $replying = null;
    public string $replyContent = "";

    public function edit(Comment $comment): void
    {
        $this->editing = $comment;
        $this->editingContent = $comment->content;
    }

    public function update(): void
    {
        if (! $this->editing) {
            return;
        }

        $validated = $this->validate([
            "editingContent" => "required|string|max:1000",
        ]);
        $this->editing->update(["content" => $validated["editingContent"]]);
        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->reset("editing", "editingContent");
    }

    public function prepareToDelete(Comment $comment): void
    {
        $this->deleting = $comment;
        $this->showDeleteModal = true;
    }

    public function deleteComment(): void
    {
        if ($this->deleting) {
            $this->deleting->delete();
            session()->flash("message", "Komentar berhasil dihapus.");
        }
        $this->showDeleteModal = false;
    }

    public function startReply(Comment $comment): void
    {
        // Hanya komentar induk yang bisa dibalas, balasan tidak bisa dibalas lagi
        if ($comment->parent_id) {
            session()->flash(
                "error",
                "Anda tidak bisa membalas sebuah balasan.",
            );
            return;
        }
        $this->replying = $comment;
        $this->replyContent = ""; // Kosongkan field setiap kali memulai balasan
    }

    public function addReply(): void
    {
        if (! $this->replying) {
            return;
        }

        $validated = $this->validate([
            "replyContent" => "required|string|max:1000",
        ]);

        // Buat komentar baru sebagai balasan
        Comment::create([
            "user_id" => auth()->id(), // Admin yang sedang login
            "post_id" => $this->replying->post_id, // Post yang sama dengan induknya
            "content" => $validated["replyContent"],
            "parent_id" => $this->replying->id, // Ini kuncinya
        ]);

        session()->flash("message", "Komentar berhasil dibalas.");
        $this->cancelReply();
    }

    public function cancelReply(): void
    {
        $this->reset("replying", "replyContent");
    }

    public function with(): array
    {
        $comments = Comment::query()
            ->with(["user", "post", "replies"]) // Eager load relasi untuk performa
            ->when($this->search, function ($query) {
                $query
                    ->where("content", "like", "%" . $this->search . "%")
                    ->orWhereHas(
                        "user",
                        fn ($q) => $q->where(
                            "name",
                            "like",
                            "%" . $this->search . "%",
                        ),
                    )
                    ->orWhereHas(
                        "post",
                        fn ($q) => $q->where(
                            "title",
                            "like",
                            "%" . $this->search . "%",
                        ),
                    );
            })
            ->latest() // Urutkan dari yang terbaru
            ->paginate(10);

        return [
            "comments" => $comments,
        ];
    }
}; ?>

<div>
    <main class="flex-1 p-6 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-white">Kelola Komentar</h1>
        </div>

        <x-notification />

        <div class="card p-4 mb-8">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari berdasarkan isi komentar, nama user, atau judul postingan..."
                class="form-input"
            />
        </div>

        <div class="card">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Komentar</th>
                            <th>Pengguna</th>
                            <th>Pada Postingan</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($comments as $comment)
                            <tr
                                wire:key="comment-{{ $comment->id }}"
                                class="{{ $comment->parent_id ? "bg-slate-800/50" : "" }}"
                            >
                                <td class="truncate">
                                    @if($comment->parent_id)
                                        <div class="pl-4 border-l-2 border-sky-500">
                                            <p class="text-xs text-slate-400 italic mb-1">Membalas: "{{ Str::limit($comment->parent->content, 30) }}"</p>
                                    @endif
                                    @if ($editing?->is($comment))
                                        <div class="space-y-2">
                                            <textarea
                                                wire:model="editingContent"
                                                rows="3"
                                                class="form-input text-sm"
                                            ></textarea>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <button
                                                    wire:click="update"
                                                    class="text-xs bg-sky-500 hover:bg-sky-600 text-white font-semibold px-3 py-1 rounded-md cursor-pointer"
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
                                        <p class="text-slate-300">
                                            {{ Str::limit($comment->content, 35) }}
                                        </p>

                                        @if(!$comment->parent_id && $comment->replies->isNotEmpty())
                                            <span class="text-xs mt-1 inline-block bg-green-500/20 text-green-400 px-2 py-0.5 rounded-md">
                                                <x-icon name="lucide.check-circle" class="inline-block w-3 h-3 mr-1" />
                                                Sudah Dibalas
                                            </span>
                                        @endif
                                    @endif
                                    @if($comment->parent_id)
                                        </div>
                                    @endif
                                </td>
                               <td class="text-slate-300 truncate">
                                    <a href="#" class="hover:text-sky-400 inline-flex items-center">
                                        <span>{{ $comment->user->name }}</span>

                                        {{-- Tambahkan badge jika user adalah admin --}}
                                        @if ($comment->user->is_admin)
                                            <span class="ml-2 text-xs font-semibold px-2 py-0.5 bg-sky-500/20 text-sky-400 rounded-md">
                                                Admin
                                            </span>
                                        @endif
                                    </a>
                                </td>
                                <td class="text-slate-400 truncate">
                                    <a
                                        href="{{ url("blog/show", $comment->post->slug) }}"
                                        target="_blank"
                                        class="hover:text-sky-400"
                                    >
                                        {{ Str::limit($comment->post->title, 30) }}
                                    </a>
                                </td>
                                <td class="text-slate-400 whitespace-nowrap">
                                    {{ $comment->created_at->format("d M Y") }}
                                </td>
                                <td>
                                   <div class="flex items-center space-x-2">
                                        @if(!$comment->parent_id)
                                        <button wire:click="startReply({{ $comment->id }})" class="text-slate-400 hover:text-green-400 cursor-pointer" title="Balas">
                                            <x-icon name="lucide.reply" class="w-5 h-5" />
                                        </button>
                                        @endif
                                        <button
                                            wire:click="edit({{ $comment->id }})"
                                            class="text-slate-400 hover:text-sky-400 cursor-pointer"
                                            title="Edit"
                                        >
                                            <x-icon
                                                name="lucide.square-pen"
                                                class="w-5 h-5"
                                            />
                                        </button>
                                        <button
                                            wire:click="prepareToDelete({{ $comment->id }})"
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
                            @if($replying?->is($comment))
                                <tr>
                                    <td colspan="5" class="p-4 bg-slate-800">
                                        <form wire:submit.prevent="addReply">
                                            <h4 class="text-white font-semibold text-sm mb-2">Balas Komentar:</h4>
                                            <textarea wire:model="replyContent" rows="3" class="form-input text-sm" placeholder="Tulis balasan Anda sebagai admin..."></textarea>
                                            @error('replyContent') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                            <div class="flex items-center gap-2 mt-2">
                                                <button type="submit" class="text-xs bg-sky-500 hover:bg-sky-600 text-white font-semibold px-3 py-1 rounded-md cursor-pointer">Kirim Balasan</button>
                                                <button type="button" wire:click="cancelReply" class="text-xs text-slate-400 hover:underline cursor-pointer">Batal</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="text-center py-8 text-slate-400"
                                >
                                    Tidak ada komentar ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-800">
                {{ $comments->links("livewire.tailwind-custom") }}
            </div>
        </div>
    </main>

    <div
        x-data="{ show: @entangle("showDeleteModal") }"
        x-show="show"
        @keydown.escape.window="show = false"
        class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4"
        x-cloak
    >
        <div @click.away="show = false" class="card w-full max-w-md">
            <div class="p-6 md:p-8 text-center">
                <h2 class="text-2xl font-bold text-white">Hapus Komentar?</h2>
                <p class="text-slate-400 mt-2">
                    Anda yakin ingin menghapus komentar ini secara permanen?
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
                        wire:click="deleteComment"
                        wire:loading.attr="disabled"
                        wire:target="deleteComment"
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-lg w-full cursor-pointer"
                    >
                        <div class="flex items-center justify-center">
                            <x-loading
                                wire:loading
                                wire:target="deleteComment"
                                class="loading-dots mr-2"
                            />
                            <x-icon
                                name="lucide.trash-2"
                                wire:loading.remove
                                wire:target="deleteComment"
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
