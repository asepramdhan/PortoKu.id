<?php

use Livewire\Volt\Component;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->updateUnreadCount();
    }

    public function updateUnreadCount(): void
    {
        $this->unreadCount = Message::whereNull("read_at")
            ->whereHas("user", fn ($q) => $q->where("is_admin", false))
            ->count();
    }
}; ?>

<div wire:poll.60s="updateUnreadCount">
    Kelola Chat
    @if ($this->unreadCount > 0)
        <span
            {{-- @if($this->isPolling) wire:poll.5s @endif --}}
            class="px-2 py-1 ms-2 text-xs font-bold text-white bg-slate-700 rounded"
        >
            {{ $this->unreadCount }}
        </span>
    @endif
</div>
