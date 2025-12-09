<?php

use App\Models\User;
use App\Models\Post;
use App\Models\ShopeeAd;
use App\Models\FinancialEntry;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    // Card Atas (Statistik Pengunjung)
    public int $viewsToday;
    public int $views7Days;
    public int $views30Days;

    // Card iklan (Statistik Klik)
    public int $totalClickAds;
    public int $clicksToday;
    public int $clicks7Days;
    public int $clicks30Days;

    public int $siteVisitorsToday;

    // Card Bawah (Statistik Total)
    public int $totalUsers;
    public int $totalPosts;
    public int $totalAds;
    public int $totalTransactions;

    // Data untuk Chart
    public array $chartData;

    // Latest Transactions
    public $latestTransactions;

    public function mount(): void
    {
        $this->updateData();
    }

    public function updateData(): void
    {
        // 1. Hitung data untuk 3 kartu atas
        $this->viewsToday = \App\Models\PostView::whereDate(
            "created_at",
            today(),
        )->count();
        $this->views7Days = \App\Models\PostView::where(
            "created_at",
            ">=",
            now()->subDays(7),
        )->count();
        $this->views30Days = \App\Models\PostView::where(
            "created_at",
            ">=",
            now()->subDays(30),
        )->count();

        // Hitung pengunjung
        $this->siteVisitorsToday = \App\Models\SiteVisitor::whereDate(
            "created_at",
            today(),
        )->count();

        // Hitung klik iklan
        $this->clicksToday = ShopeeAd::whereDate("created_at", today())->sum(
            "clicks_count",
        );
        $this->clicks7Days = ShopeeAd::where(
            "created_at",
            ">=",
            now()->subDays(7),
        )->sum("clicks_count");
        $this->clicks30Days = ShopeeAd::where(
            "created_at",
            ">=",
            now()->subDays(30),
        )->sum("clicks_count");

        // 2. Hitung data untuk 3 kartu bawah
        $this->totalUsers = User::count();
        $this->totalPosts = Post::count();
        $this->totalAds = ShopeeAd::count();
        $this->totalTransactions = FinancialEntry::count();

        // Hitung total click ads
        $this->totalClickAds = ShopeeAd::sum("clicks_count");

        // Latest Transactions
        $this->latestTransactions = FinancialEntry::query()
            ->with("asset", "user")
            ->orderBy("created_at", "DESC")
            ->limit(10)
            ->get();

        // 3. Siapkan data untuk chart (pengunjung per hari selama 30 hari terakhir)
        $viewsData = \App\Models\PostView::query()
            ->where("created_at", ">=", now()->subDays(30))
            ->groupBy("date")
            ->orderBy("date", "ASC")
            ->get([
                DB::raw("DATE(created_at) as date"),
                DB::raw("count(*) as views"),
            ])
            ->pluck("views", "date"); // Hasilnya: ['2025-08-05' => 10, '2025-08-06' => 15]

        // Format data agar bisa dibaca oleh Chart.js
        $labels = [];
        $data = [];
        // Loop selama 30 hari dari sekarang mundur ke belakang
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format("d M"); // Format label (misal: 04 Sep)
            $data[] = $viewsData[$date->format("Y-m-d")] ?? 0; // Ambil data view, jika tidak ada isi 0
        }

        $this->chartData = [
            "labels" => $labels,
            "data" => $data,
        ];

        // Kirim event ke browser untuk memberitahu bahwa data chart sudah diperbarui
        $this->dispatch("update-admin-chart", chartData: $this->chartData);
    }

    public function with(): array
    {
        return [
            "siteVisitorsToday" => $this->siteVisitorsToday,
            "viewsToday" => $this->viewsToday,
            "views7Days" => $this->views7Days,
            "views30Days" => $this->views30Days,
            "clicksToday" => $this->clicksToday,
            "clicks7Days" => $this->clicks7Days,
            "clicks30Days" => $this->clicks30Days,
            "chartData" => $this->chartData,
            "totalUsers" => $this->totalUsers,
            "totalPosts" => $this->totalPosts,
            "totalAds" => $this->totalAds,
            "totalClickAds" => $this->totalClickAds,
            "totalTransactions" => $this->totalTransactions,
            "latestTransactions" => $this->latestTransactions,
        ];
    }
}; ?>

<div wire:poll.60s="updateData">
    <!-- Page Content -->
    <h1 class="text-3xl font-bold text-white mb-6">Dashboard Admin</h1>

    <!-- Summary Cards -->
    <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 text-center"
    >
        <!-- Total Pengunjung Website Hari Ini -->
        <div class="card p-6 flex items-center gap-6">
            <div class="bg-teal-500/10 p-4 rounded-lg">
                <x-icon name="lucide.globe" class="w-8 h-8 text-teal-400" />
            </div>
            <div>
                <p class="text-slate-400 font-medium">Website (Hari Ini)</p>
                <p class="text-3xl font-bold text-white text-center">
                    {{ number_format($siteVisitorsToday) }}
                </p>
            </div>
        </div>

        <div class="card p-6 flex items-center gap-6">
            <div class="bg-indigo-500/10 p-4 rounded-lg">
                <x-icon name="lucide.eye" class="w-8 h-8 text-indigo-400" />
            </div>
            <div>
                <p class="text-slate-400 font-medium">Pengunjung (Hari Ini)</p>
                <p class="text-3xl font-bold text-white">
                    {{ number_format($viewsToday) }}
                </p>
            </div>
        </div>
        <div class="card p-6 flex items-center gap-6">
            <div class="bg-indigo-500/10 p-4 rounded-lg">
                <x-icon
                    name="lucide.calendar-days"
                    class="w-8 h-8 text-indigo-400"
                />
            </div>
            <div>
                <p class="text-slate-400 font-medium">Pengunjung (7 Hari)</p>
                <p class="text-3xl font-bold text-white">
                    {{ number_format($views7Days) }}
                </p>
            </div>
        </div>
        <div class="card p-6 flex items-center gap-6">
            <div class="bg-indigo-500/10 p-4 rounded-lg">
                <x-icon
                    name="lucide.calendar-range"
                    class="w-8 h-8 text-indigo-400"
                />
            </div>
            <div>
                <p class="text-slate-400 font-medium">Pengunjung (30 Hari)</p>
                <p class="text-3xl font-bold text-white">
                    {{ number_format($views30Days) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Ads Cards -->
    <!-- Menampilkan data iklan klik hari ini, 7 hari, dan 30 hari -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="card p-6 flex items-center gap-6">
            <div class="bg-pink-500/10 p-4 rounded-lg">
                <x-icon
                    name="lucide.mouse-pointer-click"
                    class="w-8 h-8 text-pink-400"
                />
            </div>
            <div class="text-center">
                <p class="text-slate-400 font-medium">Iklan Klik (Hari Ini)</p>
                <p class="text-3xl font-bold text-white">
                    {{ number_format($clicksToday) }}
                </p>
            </div>
        </div>
        <div class="card p-6 flex items-center gap-6">
            <div class="bg-pink-500/10 p-4 rounded-lg">
                <x-icon
                    name="lucide.square-dashed-mouse-pointer"
                    class="w-8 h-8 text-pink-400"
                />
            </div>
            <div class="text-center">
                <p class="text-slate-400 font-medium">Iklan Klik (7 Hari)</p>
                <p class="text-3xl font-bold text-white">
                    {{ number_format($clicks7Days) }}
                </p>
            </div>
        </div>
        <div class="card p-6 flex items-center gap-6">
            <div class="bg-pink-500/10 p-4 rounded-lg">
                <x-icon
                    name="lucide.square-mouse-pointer"
                    class="w-8 h-8 text-pink-400"
                />
            </div>
            <div class="text-center">
                <p class="text-slate-400 font-medium">Iklan Klik (30 Hari)</p>
                <p class="text-3xl font-bold text-white">
                    {{ number_format($clicks30Days) }}
                </p>
            </div>
        </div>
    </div>

    <div class="card p-6 mb-8">
        <h3 class="text-xl font-bold text-white mb-4">
            <span class="hidden lg:block">
                Tren Pengunjung Blog (30 Hari Terakhir)
            </span>
            <span class="lg:hidden">Tren (30 Hari Terakhir)</span>
        </h3>
        <div
            wire:ignore
            x-data="{
                chart: null,
                // KITA HAPUS FUNGSI 'updateChart' DAN KEMBALI KE LOGIKA ASLI ANDA
                initChart(labels, data) {
                    if (this.chart) {
                        this.chart.destroy()
                    }
                    const ctx = this.$refs.canvas.getContext('2d')
                    const gradient = ctx.createLinearGradient(0, 0, 0, 300)
                    gradient.addColorStop(0, 'rgba(129, 140, 248, 0.5)') // Warna Indigo
                    gradient.addColorStop(1, 'rgba(129, 140, 248, 0)')

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Pengunjung per Hari',
                                    data: data,
                                    backgroundColor: gradient,
                                    borderColor: 'rgba(129, 140, 248, 1)',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 5,
                                    fill: true,
                                    tension: 0.4,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    ticks: { color: '#94a3b8' },
                                    grid: { color: '#1e293b' },
                                },
                                x: {
                                    ticks: { color: '#94a3b8', maxTicksLimit: 8 },
                                    grid: { display: false },
                                },
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    backgroundColor: '#1e293b',
                                    titleColor: '#f1f5f9',
                                    bodyColor: '#cbd5e1',
                                    padding: 10,
                                    cornerRadius: 4,
                                },
                            },
                        },
                    })
                },
            }"
            x-init="initChart(@js($chartData["labels"]), @js($chartData["data"]))"
            {{-- SEKARANG EVENT UPDATE AKAN MEMANGGIL 'initChart', SAMA SEPERTI DI DASHBOARD USER --}}
            @update-admin-chart.window="initChart($event.detail.chartData.labels, $event.detail.chartData.data)"
            class="h-80"
        >
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 text-center">
        {{-- Card Pengguna --}}
        <a href="/admin/users" wire:navigate>
            <div class="card p-6 flex items-center gap-6">
                <div class="bg-sky-500/10 p-4 rounded-lg">
                    <x-icon name="lucide.users" class="w-8 h-8 text-sky-400" />
                </div>
                <div>
                    <p class="text-slate-400 font-medium">Total Pengguna</p>
                    <p class="text-3xl font-bold text-white">
                        {{ number_format($totalUsers) }}
                    </p>
                </div>
            </div>
        </a>
        {{-- Card Postingan --}}
        <a href="/admin/blog" wire:navigate>
            <div class="card p-6 flex items-center gap-6">
                <div class="bg-green-500/10 p-4 rounded-lg">
                    <x-icon
                        name="lucide.file-text"
                        class="w-8 h-8 text-green-400"
                    />
                </div>
                <div>
                    <p class="text-slate-400 font-medium">Total Postingan</p>
                    <p class="text-3xl font-bold text-white">
                        {{ number_format($totalPosts) }}
                    </p>
                </div>
            </div>
        </a>
        {{-- Card Iklan --}}
        <a href="/admin/ads" wire:navigate>
            <div class="card p-6 flex items-center gap-6">
                <div class="bg-pink-500/10 p-4 rounded-lg">
                    <x-icon
                        name="lucide.megaphone"
                        class="w-8 h-8 text-pink-400"
                    />
                </div>
                <div>
                    <p class="text-slate-400 font-medium">Total Iklan</p>
                    <p class="text-3xl font-bold text-white">
                        {{ number_format($totalAds) }}
                    </p>
                </div>
            </div>
        </a>
        {{-- Card Transaksi --}}
        <a href="/admin/transactions" wire:navigate>
            <div class="card p-6 flex items-center gap-6">
                <div class="bg-orange-500/10 p-4 rounded-lg">
                    <x-icon
                        name="lucide.arrow-right-left"
                        class="w-8 h-8 text-orange-400"
                    />
                </div>
                <div>
                    <p class="text-slate-400 font-medium">Total Transaksi</p>
                    <p class="text-3xl font-bold text-white">
                        {{ number_format($totalTransactions) }}
                    </p>
                </div>
            </div>
        </a>
    </div>

    <!-- Quick Actions -->
    <div class="card p-6 mb-8">
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
                href="/admin/transactions"
                wire:navigate
                class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-5 py-3 rounded-lg flex items-center gap-2 transition-colors"
            >
                <x-icon name="lucide.arrow-right-left" class="w-5 h-5" />
                Kelola Transaksi
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
                href="/admin/ads"
                wire:navigate
                class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-5 py-3 rounded-lg flex items-center gap-2 transition-colors"
            >
                <x-icon name="lucide.megaphone" class="w-5 h-5" />
                Kelola Iklan
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
                <x-icon name="lucide.settings" class="w-5 h-5" />
                Pengaturan Profil
            </a>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="grid grid-cols-1">
        <div class="card p-6 mb-8">
            {{--
                <div class="flex flex-col md:flex-row justify-between md:items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Kelola Postingan Blog</h1>
                <a
                href="/admin/blog/create"
                wire:navigate
                class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors mt-4 md:mt-0"
                >
                <x-icon name="lucide.plus-circle" class="h-5 w-5" />
                Buat Postingan Baru
                </a>
                </div>
            --}}
            <div class="flex flex-row justify-between mb-4">
                <h3 class="text-xl font-bold text-white">
                    Aktivitas Transaksi
                </h3>
                <a
                    href="/admin/transactions"
                    wire:navigate
                    class="text-sky-500 hover:text-sky-600 font-semibold"
                >
                    Semua
                    <span class="hidden md:inline">Transaksi</span>
                </a>
            </div>
            <div class="space-y-4">
                <div class="table-wrapper">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left truncate">Profil</th>
                                <th class="text-left">Nama</th>
                                <th class="text-left">Tanggal</th>
                                <th class="text-left">Type</th>
                                <th class="text-left">Aset/Kategori</th>
                                <th class="text-left">Jumlah</th>
                                <th class="text-left">Nilai (IDR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestTransactions as $transaction)
                                <tr>
                                    <td class="truncate">
                                        <img
                                            src="{{ $transaction->user->profile_photo_path ? asset("storage/" . $transaction->user->profile_photo_path) : "https://placehold.co/48x48/0EA5E9/FFFFFF?text=" . substr($transaction->user->name, 0, 1) }}"
                                            alt="{{ $transaction->user->name }}"
                                            class="w-10 h-10 object-cover rounded-full"
                                        />
                                    </td>
                                    <td class="text-left truncate">
                                        {{ Str::limit($transaction->user->name, 15) }}
                                    </td>
                                    <td class="text-left text-sm truncate">
                                        {{ $transaction->created_at->format("d M Y H:i") }}
                                        <br />
                                        <span
                                            class="text-slate-400 text-xs truncate"
                                        >
                                            {{ $transaction->updated_at->format("d M Y H:i") }}
                                        </span>
                                    </td>
                                    <td class="text-left">
                                        @if ($transaction->type == "buy")
                                            <p
                                                class="font-semibold text-green-400"
                                            >
                                                Beli
                                            </p>
                                        @elseif ($transaction->type == "sell")
                                            <p
                                                class="font-semibold text-red-400"
                                            >
                                                Jual
                                            </p>
                                        @elseif ($transaction->type == "income")
                                            <p
                                                class="font-semibold text-sky-400"
                                            >
                                                Pemasukan
                                            </p>
                                        @else
                                            <p
                                                class="font-semibold text-orange-400"
                                            >
                                                Pengeluaran
                                            </p>
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        <p
                                            class="font-semibold text-white p-1 rounded"
                                        >
                                            {{ Str::title($transaction->asset->name ?? $transaction->category) }}
                                        </p>
                                    </td>
                                    <td class="text-left">
                                        <p class="font-semibold text-white">
                                            {{ $transaction->quantity }}
                                        </p>
                                    </td>
                                    <td class="text-left">
                                        <p
                                            class="font-semibold text-white truncate"
                                        >
                                            {{ number_format($transaction->amount) }}
                                        </p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="3"
                                        class="text-center py-8 text-slate-400"
                                    >
                                        Belum ada aktivitas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
