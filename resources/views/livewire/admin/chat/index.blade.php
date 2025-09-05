<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Illuminate\Database\Eloquent\Collection;

new class extends Component {
    public Collection $conversations;
    public ?Conversation $activeConversation = null;
    public Collection $messageList;
    public string $newMessage = "";
    public $hasMore = true;

    protected function rules(): array
    {
        return [
            "newMessage" => "required|string|max:1000",
        ];
    }

    public function mount(): void
    {
        $this->loadConversations();

        // Secara otomatis pilih percakapan pertama jika ada
        if ($this->conversations->isNotEmpty()) {
            $this->selectConversation($this->conversations->first()->id);

            // update read_at
            // $this->activeConversation->messages()->update(["read_at" => now()]);
        }
    }

    public function loadConversations(): void
    {
        $this->conversations = Conversation::with("user")
            ->withCount([
                "messages as unread_count" => function ($q) {
                    $q->whereNull("read_at")->whereHas(
                        "user",
                        fn ($q2) => $q2->where("is_admin", false),
                    );
                },
            ])
            ->whereHas("user", fn ($q) => $q->where("is_admin", false))
            ->orderBy("last_message_at", "desc")
            ->get();
    }

    public function loadMessages(): void
    {
        if ($this->activeConversation) {
            $this->messageList = $this->activeConversation
                ->messages()
                ->with("user")
                ->latest()
                ->take(10)
                ->get()
                ->reverse();

            $this->activeConversation
                ->messages()
                ->whereNull("read_at")
                ->whereHas("user", fn ($q) => $q->where("is_admin", false))
                ->update(["read_at" => now()]);

            // cek apakah masih ada sisa pesan
            $this->hasMore =
                $this->activeConversation->messages()->count() >
                $this->messageList->count();
        }
    }

    public function selectConversation($conversationId): void
    {
        $this->activeConversation = Conversation::with(
            "user",
            "messages.user",
        )->findOrFail($conversationId);

        $this->loadMessages();
    }

    public function loadMoreMessages($oldHeight): void
    {
        if (! $this->hasMore) {
            return;
        }

        $firstVisibleMessage = $this->messageList->first();

        $oldestMessage = Message::with("user")
            ->where("id", "<", $firstVisibleMessage->id)
            ->latest()
            ->take(10)
            ->get()
            ->reverse();

        $this->messageList = $oldestMessage->merge($this->messageList);

        // update status hasMore
        $this->hasMore = $this->activeConversation
            ->messages()
            ->where("id", "<", $this->messageList->first()->id)
            ->exists();

        // Simpan posisi scroll sekarang
        if ($oldHeight) {
            $this->js("
            let box = document.getElementById('chat-box'); 
            window.dispatchEvent(new CustomEvent('scroll-keep', { detail: { position: box.scrollHeight - {$oldHeight} } }))
            ");
        }
    }

    public function sendMessage(): void
    {
        $this->validate();

        if (! $this->activeConversation) {
            return;
        }

        $this->activeConversation->messages()->create([
            "user_id" => Auth::id(), // Admin yang sedang login
            "content" => $this->newMessage,
        ]);

        $this->activeConversation->touch("last_message_at");
        $this->reset("newMessage");
        $this->loadConversations(); // Muat ulang daftar percakapan untuk urutan
        $this->selectConversation($this->activeConversation->id); // Muat ulang pesan

        // Scroll ke bawah setelah kirim pesan
        $this->js("window.dispatchEvent(new CustomEvent('scroll-bottom'))");
    }

    public function with(): array
    {
        return [
            "conversationsList" => $this->conversations,
            "activeConversationData" => $this->activeConversation,
            "messageList" => $this->messageList ?? collect(),
        ];
    }
}; ?>

<div
    x-data="{ 
            init() {
                let box = this.$refs.chatBox

                // Auto-scroll ke bawah saat mount
                this.$nextTick(() => {
                    box.scrollTop = box.scrollHeight
                })

                // Listen event dari Livewire untuk scroll bawah
                window.addEventListener('scroll-bottom', () => {
                    box.scrollTo({
                        top: box.scrollHeight,
                        behavior: 'instant',
                    })
                })

                // Scroll stabil saat load more
                window.addEventListener('scroll-keep', (e) => {
                    box.scrollTo({
                        top: e.detail.position,
                        behavior: 'instant',
                    })
                })

                // Detect scroll ke atas
                box.addEventListener('scroll', () => {
                    if (box.scrollTop === 0 && @this.get('hasMore')) {
                        $wire.loadMoreMessages(box.scrollHeight - box.clientHeight)
                    }
                })
            },
        }"
    class="flex flex-col md:flex-row"
>
    <div class="md:w-1/3 border-r border-slate-700">
        <div class="p-4 border-b border-slate-700">
            <a href="/admin/chat" wire:navigate>
                <h3 class="font-semibold text-white">Percakapan</h3>
            </a>
        </div>
        <div class="overflow-y-auto">
            @foreach ($conversationsList as $conv)
                <div
                    @click="$wire.selectConversation({{ $conv->id }}) .then(() => { $refs.chatBox.scrollTop = $refs.chatBox.scrollHeight })"
                    class="p-4 border-b border-slate-800 cursor-pointer hover:bg-slate-800 {{ $activeConversationData?->id === $conv->id ? "bg-slate-800" : "" }}"
                >
                    <div class="flex items-center">
                        <div class="relative">
                            <img
                                src="{{ $conv->user->profile_photo_path ? asset("storage/" . $conv->user->profile_photo_path) : "https://placehold.co/40x40/0EA5E9/FFFFFF?text=" . substr($conv->user->name, 0, 1) }}"
                                class="w-12 h-12 rounded-full mr-4"
                                alt="{{ $conv->user->name }}"
                            />
                            @if ($conv->messages->where("read_at", null)->where("user_id", "!=", Auth::id())->count() > 0)
                                <span
                                    class="absolute top-0 left-0 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center"
                                >
                                    {{ $conv->messages->where("read_at", null)->where("user_id", "!=", Auth::id())->count() }}
                                </span>
                            @endif
                        </div>

                        <div>
                            <p class="font-semibold text-white">
                                {{ $conv->user->name }}
                            </p>
                            <p class="text-sm text-slate-400 truncate">
                                {{ Str::limit($conv->messages->last()->content, 50) ?? "Belum ada pesan." }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ $conv->last_message_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="flex-1 md:pl-6">
        <div class="h-screen flex flex-col">
            @if ($activeConversationData)
                <div class="flex p-4 border-b border-slate-700">
                    <img
                        src="{{ $activeConversationData->user->profile_photo_path ? asset("storage/" . $activeConversationData->user->profile_photo_path) : "https://placehold.co/40x40/0EA5E9/FFFFFF?text=" . substr($activeConversationData->user->name, 0, 1) }}"
                        alt="Avatar"
                        class="w-10 h-10 rounded-full"
                    />
                    <div class="ml-4">
                        <h3 class="font-bold text-white">
                            {{ $activeConversationData->user->name }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            {{ $activeConversationData->user->email }}
                        </p>
                    </div>
                </div>
                <div
                    x-ref="chatBox"
                    id="chat-box"
                    class="flex-1 p-4 space-y-4 overflow-y-auto"
                >
                    {{-- load more --}}
                    <div class="flex justify-center">
                        @if ($hasMore)
                            <button
                                wire:loading.attr="disabled"
                                wire:target="loadMoreMessages"
                                class="text-slate-600 font-semibold text-sm"
                            >
                                <div class="flex items-center">
                                    <x-loading
                                        wire:loading
                                        wire:target="loadMoreMessages"
                                        class="loading-dots"
                                    />
                                </div>
                            </button>
                        @else
                            <span class="text-slate-600 font-semibold text-sm">
                                Tidak ada chat lagi
                            </span>
                        @endif
                    </div>
                    @foreach ($messageList as $message)
                        <div
                            class="flex {{ $message->user->is_admin ? "justify-end" : "justify-start" }}"
                        >
                            <div
                                class="max-w-[80%] p-3 rounded-lg {{ $message->user->is_admin ? "bg-sky-500 text-white" : "bg-slate-700 text-slate-200" }}"
                            >
                                <p class="text-sm">
                                    {{ $message->content }}
                                </p>
                                <p
                                    class="text-xs mt-1 opacity-70 {{ $message->user->is_admin ? "text-right" : "text-left" }}"
                                >
                                    {{ $message->created_at->format("H:i") }}
                                    @if ($message->user->is_admin)
                                        @if ($message->read_at == null)
                                            <x-icon
                                                name="lucide.check"
                                                class="w-4 h-4 inline-block"
                                            />
                                        @else
                                            <x-icon
                                                name="lucide.check-check"
                                                class="w-4 h-4 text-green-400 inline-block"
                                            />
                                        @endif
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="p-4 border-t border-slate-700">
                    <form
                        wire:submit.prevent="sendMessage"
                        class="flex items-center gap-2"
                    >
                        <input
                            type="text"
                            @mouseenter="$wire.loadMessages()"
                            wire:model="newMessage"
                            placeholder="Ketik balasan Anda..."
                            class="form-input flex-1 !py-2"
                            autocomplete="off"
                        />
                        <button
                            type="submit"
                            class="bg-sky-500 text-white rounded-full w-10 h-10 flex-shrink-0 flex items-center justify-center cursor-pointer"
                        >
                            <x-icon name="lucide.send" class="w-5 h-5" />
                        </button>
                    </form>
                </div>
            @else
                <div class="flex-1 flex items-center justify-center">
                    <p class="text-slate-400">
                        Pilih percakapan untuk memulai.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
