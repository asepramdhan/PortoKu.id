<?php

use Livewire\Volt\Component;

new class extends Component {
    public int $unreadCount = 0;
    public int $previousUnreadCount = 0;

    public function mount(): void
    {
        $this->updateUnreadCount();
    }

    public function updateUnreadCount(): void
    {
        // Simpan jumlah notifikasi sebelumnya
        $this->previousUnreadCount = $this->unreadCount;

        // Panggil endpoint API untuk mendapatkan jumlah pesan
        // URL endpoint bisa diakses melalui URL helper
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, url("/api/messages/unread-count"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response);

        // Simpan jumlah notifikasi terbaru dari API
        if ($data && property_exists($data, "unreadCount")) {
            $this->unreadCount = $data->unreadCount;
        } else {
            // Untuk sementara, kita set ke 0
            $this->unreadCount = 0;
        }

        // Cek apakah ada notifikasi baru (jumlah notifikasi bertambah)
        if ($this->unreadCount > $this->previousUnreadCount) {
            $this->dispatch("play-sound");
        }
    }
}; ?>

<div
    x-data
    x-init="
        $wire.on('play-sound', () => {
            $refs.audioNotification.play()
        })
    "
    wire:poll.60s="updateUnreadCount"
>
    <audio x-ref="audioNotification" preload="auto">
        <source src="/sounds/notify.mp3" type="audio/mpeg" />
    </audio>

    Kelola Chat
    @if ($this->unreadCount > 0)
        <span
            class="px-2 py-1 ms-2 text-xs font-bold text-white bg-slate-700 rounded"
        >
            {{ $this->unreadCount }}
        </span>
    @endif
</div>
