<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $notifications;
    public $unreadCount = 0;

    public function mount()
    {
        $this->loadNotifications();
    }

    #[On("notifications-updated")]
    public function loadNotifications()
    {
        if (Auth::check()) {
            $this->notifications = Auth::user()->unreadNotifications;
            $this->unreadCount = $this->notifications->count();
        }
    }

    public function markAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->loadNotifications(); // Refresh

        // FIX: Kirim event global untuk memastikan semua komponen diperbarui
        $this->dispatch("notifications-updated");
    }
}; ?>

<div
    x-data="{ open: false }"
    @mouseenter="open = true; $wire.loadNotifications()"
    @mouseleave="open = false"
    class="relative text-slate-400 hover:text-white"
>
    <button class="relative text-slate-400 hover:text-white cursor-pointer">
        @if ($unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"
                ></span>
                <span
                    class="relative inline-flex rounded-full h-3 w-3 bg-sky-500"
                ></span>
            </span>
        @endif

        <x-icon name="lucide.bell" class="w-6 h-6" />
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-transition
        class="absolute right-0 mt-2 lg:w-80 w-72 bg-slate-800 rounded-md shadow-lg z-20 border border-slate-700"
        x-cloak
    >
        <div
            class="p-4 flex justify-between items-center border-b border-slate-700"
        >
            <h3 class="font-semibold text-white">Notifikasi</h3>
            @if ($unreadCount > 0)
                <button
                    wire:click="markAsRead"
                    class="text-xs text-sky-500 hover:underline cursor-pointer"
                >
                    Tandai semua dibaca
                </button>
            @endif
        </div>
        <div class="p-2 max-h-96 overflow-y-auto">
            @forelse ($notifications as $notification)
                <div class="p-3 rounded-lg hover:bg-slate-700">
                    @php
                        $change = $notification->data["percentage_change"];
                        $isPositive = $change >= 0;
                        $formattedChange = number_format(abs($change), 2, ",", ".");
                        $currentValue = number_format($notification->data["current_value"], 0, ",", ".");
                    @endphp

                    <p class="text-sm font-semibold text-slate-200">
                        Ringkasan Portofolio Harian
                    </p>
                    <p class="text-sm text-slate-400">
                        Portofolio Anda {{ $isPositive ? "naik" : "turun" }}
                        <span
                            class="{{ $isPositive ? "text-green-500" : "text-red-500" }} font-bold"
                        >
                            {{ $formattedChange }}%
                        </span>
                        dibanding kemarin. Nilai saat ini: Rp
                        {{ $currentValue }}.
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $notification->created_at->diffForHumans() }}
                    </p>
                </div>
            @empty
                <p class="p-4 text-center text-sm text-slate-400">
                    Tidak ada notifikasi baru.
                </p>
            @endforelse
        </div>
    </div>
</div>
