<?php

use Livewire\Volt\Component;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    public int $unreadCount = 0;

    public function mount(): void
    {
        // $this->updateUnreadCount();
    }

    #[On("test-dari-browser")]
    public function handleTestDariBrowser(): void
    {
        dd("Sinyal dari BROWSER berhasil diterima oleh SERVER!");
    }

    public function updateUnreadCount(): void
    {
        // TAMBAHKAN BARIS INI UNTUK DEBUGGING
        dd("DEBUG: Sinyal BERHASIL DITERIMA oleh komponen unread-count!");
        $this->unreadCount = Message::whereNull("read_at")
            ->whereHas("user", fn ($q) => $q->where("is_admin", false))
            ->count();
    }
}; ?>

<div
    x-data="{
        showAlert() {
            alert('Sinyal DITERIMA oleh AlpineJS di Komponen unread-count!')
            // Setelah alert, kita panggil method Livewire
            $wire.updateUnreadCount()
        },
    }"
    x-on:new-message-received.window="showAlert()"
>
    Kelola Chat
    @if ($this->unreadCount > 0)
        <span
            id="teks-warna"
            {{-- @if($this->isPolling) wire:poll.5s @endif --}}
            class="px-2 py-1 ms-2 text-xs font-bold text-white bg-slate-700 rounded"
        >
            {{ $this->unreadCount }}
        </span>
    @endif
</div>
