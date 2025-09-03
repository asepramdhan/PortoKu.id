<?php

use App\Models\User;
use App\Models\Post;
use App\Models\FinancialEntry;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    // Card Atas (Statistik Pengunjung)
    public int $viewsToday;
    public int $views7Days;
    public int $views30Days;

    // Card Bawah (Statistik Total)
    public int $totalUsers;
    public int $totalPosts;
    public int $totalTransactions;

    // Data untuk Chart
    public array $chartData;

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

        // 2. Hitung data untuk 3 kartu bawah
        $this->totalUsers = User::count();
        $this->totalPosts = Post::count();
        $this->totalTransactions = FinancialEntry::count();

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
            "totalUsers" => $this->totalUsers,
            "totalPosts" => $this->totalPosts,
            "totalTransactions" => $this->totalTransactions,
            "viewsToday" => $this->viewsToday,
            "views7Days" => $this->views7Days,
            "views30Days" => $this->views30Days,
            "chartData" => $this->chartData,
        ];
    }
}; ?>

<div wire:poll.60s="updateData">
    <!-- Page Content -->
    <main class="flex-1 p-6 md:p-8">
        <h1 class="text-3xl font-bold text-white mb-6">Dashboard Admin</h1>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 text-center">
            <div class="card p-6 flex items-center gap-6">
                <div class="bg-indigo-500/10 p-4 rounded-lg">
                    <x-icon name="lucide.eye" class="w-8 h-8 text-indigo-400" />
                </div>
                <div>
                    <p class="text-slate-400 font-medium">
                        Pengunjung (Hari Ini)
                    </p>
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
                    <p class="text-slate-400 font-medium">
                        Pengunjung (7 Hari)
                    </p>
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
                    <p class="text-slate-400 font-medium">
                        Pengunjung (30 Hari)
                    </p>
                    <p class="text-3xl font-bold text-white">
                        {{ number_format($views30Days) }}
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 text-center">
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
