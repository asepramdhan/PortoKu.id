<?php

use Livewire\Volt\Component;
use App\Models\Message;

new class extends Component {
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->updateUnreadCount();
    }

    public function updateUnreadCount(): void
    {
        $this->unreadCount = Message::whereNull("read_at")
            ->where("user_id", "!=", auth()->id())
            ->count();
    }
}; ?>

<div class="relative">
    @unless ($this->unreadCount == 0)
        <span
            class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
        >
            {{ $this->unreadCount }}
        </span>
    @endunless
    Kelola Chat
</div>
