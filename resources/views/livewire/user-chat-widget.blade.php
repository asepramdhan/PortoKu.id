<?php

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;

new class extends Component {
    public ?Conversation $conversation = null;
    public Collection $messageList;
    public string $newMessage = "";
    public $hasMore = true;
    public int $unreadCount = 0;

    protected function rules(): array
    {
        return [
            "newMessage" => "required|string|max:1000",
        ];
    }

    public function mount(): void
    {
        if (Auth::check()) {
            if (Auth::user()->is_admin) {
                // Admin bisa ambil percakapan terakhir misalnya
                $this->conversation = Conversation::latest(
                    "last_message_at",
                )->first();
            } else {
                $this->conversation = Conversation::firstOrCreate(
                    ["user_id" => Auth::id()],
                    ["last_message_at" => now()],
                );

                $this->loadMessages();
            }
        }

        $this->updateUnreadCount();
    }

    public function updateReadAt(): void
    {
        if ($this->conversation) {
            $this->conversation->touch("last_message_at");

            $this->conversation
                ->messages()
                ->where("user_id", "!=", Auth::id())
                ->update(["read_at" => now()]);
        }
    }

    #[On("refreshUserChat")]
    public function loadMessages(): void
    {
        if ($this->conversation) {
            $this->messageList = $this->conversation
                ->messages()
                ->with("user")
                ->latest()
                ->take(10)
                ->get()
                ->reverse();

            // update count unread
            $this->updateUnreadCount();

            // cek apakah masih ada sisa pesan
            $this->hasMore =
                $this->conversation->messages()->count() >
                $this->messageList->count();
        }
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
            ->take(5)
            ->get()
            ->reverse();

        $this->messageList = $oldestMessage->merge($this->messageList);

        // update status hasMore
        $this->hasMore = $this->conversation
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
        // Panggil validasi tanpa parameter
        $this->validate();

        if (! $this->conversation) {
            return;
        }

        $this->conversation->messages()->create([
            "user_id" => Auth::id(),
            "content" => $this->newMessage,
        ]);

        $this->conversation->touch("last_message_at");
        $this->reset("newMessage");
        $this->loadMessages(); // Muat ulang pesan setelah mengirim

        // kirim agar refresh count message ke admin
        $this->dispatch("new-message-received");

        // Scroll ke bawah setelah kirim pesan
        $this->js("window.dispatchEvent(new CustomEvent('scroll-bottom'))");
    }

    public function updateUnreadCount(): void
    {
        // User: pastikan conversation ada
        if (! $this->conversation) {
            $this->unreadCount = 0;
            return;
        }

        // Hitung pesan admin yang belum dibaca user
        $this->unreadCount = $this->conversation
            ->messages()
            ->whereNull("read_at")
            ->whereHas("user", fn ($q) => $q->where("is_admin", true))
            ->count();
    }

    public function with(): array
    {
        return [
            "messageList" => $this->messageList ?? collect(),
        ];
    }
}; ?>

<div class="fixed bottom-4 right-4 z-50">
    <div
        x-data="{
            open: false,
            showBubbleChat: false,
            showBadgeCount: false,
            showCallout: false,

            init() {
                setTimeout(() => {
                    this.showBubbleChat = true
                        setTimeout(() => {
                            if (@this.unreadCount > 0) {
                                // sound notification
                                if (! sessionStorage.getItem('notificationPlayed')) {
                                    this.$refs.audioNotification.play()
                                    sessionStorage.setItem('notificationPlayed', 'true')
                                }

                                @this.updateUnreadCount()
                                this.showBadgeCount = true
                            }
                        }, 1300)
                }, 1000)

                // Hanya tampilkan jika belum pernah muncul di sesi ini
                if (! sessionStorage.getItem('portokuChatCalloutShown')) {
                    // Tampilkan setelah 2 detik
                    setTimeout(() => {
                        this.showCallout = true
                        // Sembunyikan setelah 5 detik
                        setTimeout(() => {
                            this.showCallout = false
                            // Tandai sudah pernah muncul
                            sessionStorage.setItem('portokuChatCalloutShown', 'true')
                        }, 5000)
                    }, 3000)
                }

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
        x-init="init()"
        class="relative"
    >
        <!-- Audio untuk notifikasi -->
        <audio x-ref="audioNotification" preload="auto">
            <source src="/sounds/notify.mp3" type="audio/mpeg" />
        </audio>

        <!-- Callout Badge -->
        <div
            x-show="showCallout"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-2"
            class="absolute bottom-20 right-0 bg-slate-700 text-white px-4 py-2 rounded-lg shadow-lg text-sm font-semibold whitespace-nowrap"
            x-cloak
        >
            Butuh bantuan? Hubungi kami!
        </div>

        <!-- Chat Bubble Button -->
        <button
            x-show="showBubbleChat"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-4"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-4"
            @click="open = !open; showCallout = false; sessionStorage.removeItem('notificationPlayed'); $nextTick(() => { $refs.chatBox.scrollTop = $refs.chatBox.scrollHeight }); $wire.loadMessages(); $wire.updateReadAt(); $wire.updateUnreadCount()"
            class="bg-sky-500 text-white rounded-full w-16 h-16 flex items-center justify-center shadow-lg hover:bg-sky-600 transition-transform hover:scale-110 cursor-pointer"
        >
            <x-icon name="lucide.message-circle" class="w-8 h-8" />

            @if ($unreadCount > 0)
                <div
                    x-show="showBadgeCount"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform translate-y-1"
                    class="absolute top-0 left-0 bg-slate-700 text-white w-5 h-5 flex items-center justify-center rounded-full shadow-lg text-xs font-semibold"
                >
                    {{ $this->unreadCount }}
                </div>
            @endif
        </button>

        <!-- Chat Window -->
        <div
            x-show="open"
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-4"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-4"
            class="absolute bottom-20 right-0 w-80 sm:w-96 h-[60vh] bg-slate-800 rounded-xl shadow-2xl flex flex-col border border-slate-700"
            x-cloak
        >
            <!-- Header -->
            <div
                class="p-4 border-b border-slate-700 flex justify-between items-center"
            >
                <h3 class="font-bold text-slate-800">Hubungi Admin</h3>
                <button
                    @click="open = false"
                    class="text-slate-400 hover:text-slate-600 cursor-pointer"
                >
                    <x-icon name="lucide.x" class="w-5 h-5" />
                </button>
            </div>

            <!-- Messages Area -->
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
                        @unless ($messageList->isEmpty() || $messageList->count() < 5)
                            <span class="text-slate-600 font-semibold text-sm">
                                Tidak ada chat lagi
                            </span>
                        @endunless
                    @endif
                </div>
                @forelse ($messageList as $message)
                    <div
                        class="flex {{ $message->user->is_admin ? "justify-start" : "justify-end" }}"
                    >
                        <div
                            class="max-w-[80%] p-3 rounded-lg {{ $message->user->is_admin ? "bg-slate-700 text-slate-200" : "bg-sky-500 text-white" }}"
                        >
                            <p class="text-sm">{{ $message->content }}</p>
                            <p
                                class="text-xs mt-1 opacity-70 {{ $message->user->is_admin ? "" : "text-right" }}"
                            >
                                {{ $message->created_at->format("H:i") }}
                                @if (! $message->user->is_admin)
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
                @empty
                    <p class="p-4 text-center text-slate-400">
                        Tidak ada percakapan.
                    </p>
                @endforelse
            </div>

            <!-- Input Area -->
            <div x-trap="open" class="p-4 border-t border-slate-700">
                <form
                    wire:submit.prevent="sendMessage"
                    class="flex items-center gap-2"
                >
                    <input
                        type="text"
                        @mouseenter="$wire.loadMessages(); $wire.updateReadAt(); $wire.updateUnreadCount()"
                        wire:model="newMessage"
                        placeholder="Ketik pesan Anda..."
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
        </div>
    </div>
</div>
