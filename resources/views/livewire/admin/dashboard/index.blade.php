<?php

use App\Models\User;
use App\Models\Post;
use App\Models\FinancialEntry;
use Livewire\Volt\Component;

new class extends Component {
    public int $totalUsers;
    public int $totalPosts;
    public int $totalTransactions;

    public function mount(): void
    {
        $this->totalUsers = User::count();
        $this->totalPosts = Post::count();
        $this->totalTransactions = FinancialEntry::count();
    }

    public function updateData(): void
    {
        $this->totalUsers = User::count();
        $this->totalPosts = Post::count();
        $this->totalTransactions = FinancialEntry::count();
    }

    public function with(): array
    {
        return [
            "totalUsers" => $this->totalUsers,
            "totalPosts" => $this->totalPosts,
            "totalTransactions" => $this->totalTransactions,
        ];
    }
}; ?>

<div wire:poll.60s="updateData">
    <!-- Page Content -->
    <main class="flex-1 p-6 md:p-8">
        <h1 class="text-3xl font-bold text-white mb-6">Dashboard Admin</h1>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Pengguna -->
            <div class="card p-6 flex items-center gap-6">
                <div class="bg-sky-500/10 p-4 rounded-lg">
                    <x-icon name="lucide.users" class="w-8 h-8 text-sky-400" />
                </div>
                <div>
                    <p class="text-slate-400 font-medium">Total Pengguna</p>
                    <p class="text-3xl font-bold text-white text-center">
                        {{ $totalUsers }}
                    </p>
                </div>
            </div>
            <!-- Total Postingan -->
            <div class="card p-6 flex items-center gap-6">
                <div class="bg-green-500/10 p-4 rounded-lg">
                    <x-icon
                        name="lucide.file-text"
                        class="w-8 h-8 text-green-400"
                    />
                </div>
                <div>
                    <p class="text-slate-400 font-medium">Total Postingan</p>
                    <p class="text-3xl font-bold text-white text-center">
                        {{ $totalPosts }}
                    </p>
                </div>
            </div>
            <!-- Total Transaksi -->
            <div class="card p-6 flex items-center gap-6">
                <div class="bg-orange-500/10 p-4 rounded-lg">
                    <x-icon
                        name="lucide.arrow-right-left"
                        class="w-8 h-8 text-orange-400"
                    />
                </div>
                <div>
                    <p class="text-slate-400 font-medium">Total Transaksi</p>
                    <p class="text-3xl font-bold text-white text-center">
                        {{ $totalTransactions }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card p-6">
            <h3 class="text-xl font-bold text-white mb-4">Aksi Cepat</h3>
            <div class="flex flex-wrap gap-4">
                <a
                    href="/admin/assets"
                    wire:navigate
                    class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-5 py-3 rounded-lg flex items-center gap-2 transition-colors"
                >
                    <x-icon name="lucide.wallet" class="w-5 h-5" />
                    Kelola Aset
                </a>
                <a
                    href="/admin/blog"
                    wire:navigate
                    class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-5 py-3 rounded-lg flex items-center gap-2 transition-colors"
                >
                    <x-icon name="lucide.book-open" class="w-5 h-5" />
                    Kelola Blog
                </a>
                <a
                    href="/admin/blog/categories"
                    wire:navigate
                    class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-5 py-3 rounded-lg flex items-center gap-2 transition-colors"
                >
                    <x-icon name="lucide.tag" class="w-5 h-5" />
                    Kelola Kategori
                </a>
                <a
                    href="/admin/blog/tags"
                    wire:navigate
                    class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-5 py-3 rounded-lg flex items-center gap-2 transition-colors"
                >
                    <x-icon name="lucide.tags" class="w-5 h-5" />
                    Kelola Tag
                </a>
                <a
                    href="/admin/users"
                    wire:navigate
                    class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-5 py-3 rounded-lg flex items-center gap-2 transition-colors"
                >
                    <x-icon name="lucide.users" class="w-5 h-5" />
                    Kelola Pengguna
                </a>
                <a
                    href="/admin/profile/edit"
                    wire:navigate
                    class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-5 py-3 rounded-lg flex items-center gap-2 transition-colors"
                >
                    <x-icon name="lucide.info" class="w-5 h-5" />
                    Tentang
                </a>
            </div>
        </div>
    </main>
</div>
