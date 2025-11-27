<?php

use App\Models\FinancialEntry;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    public $summary;
    public Collection $recentTransactions;
    public Collection $recentPosts;
    public array $chartData = [
        "labels" => [],
        "data" => [],
    ];

    public function mount(): void
    {
        $this->updateData();
        $this->loadRecentTransactions();
        $this->loadRecentPost();
    }

    public function getGreeting(): string
    {
        $hour = now()->hour;

        if ($hour >= 3 && $hour < 11) {
            return "Pagi";
        } elseif ($hour >= 11 && $hour < 15) {
            return "Siang";
        } elseif ($hour >= 15 && $hour < 18) {
            return "Sore";
        } else {
            return "Malam";
        }
    }

    // FIX: Gabungkan semua kalkulasi ke dalam satu metode
    public function updateData(): void
    {
        $this->calculateSummary();
        $this->loadChartData();

        // Kirim event ke browser untuk memberitahu bahwa data chart sudah diperbarui
        $this->dispatch("update-dashboard-chart", chartData: $this->chartData);
    }

    public function calculateSummary(): void
    {
        // FIX: Gunakan Cache::remember() untuk menyimpan hasil API selama 5 menit
        $currentPricePerUnit = Cache::remember(
            "bitcoin_price_idr",
            now()->addMinutes(5),
            function () {
                try {
                    $response = Http::get(
                        "https://api.coingecko.com/api/v3/simple/price",
                        [
                            "ids" => "bitcoin",
                            "vs_currencies" => "idr",
                        ],
                    )->json();
                    // Kembalikan harga, atau null jika gagal
                    return $response["bitcoin"]["idr"] ?? null;
                } catch (\Exception $e) {
                    // Jika API error, kembalikan null
                    return null;
                }
            },
        );

        // Gunakan harga statis sebagai fallback jika cache atau API gagal
        $currentPricePerUnit = $currentPricePerUnit ?? 1950000000;

        $this->currentBtcPrice = $currentPricePerUnit;

        $entries = FinancialEntry::with("asset")
            ->where("user_id", Auth::id())
            ->get();

        // Kalkulasi untuk Aset Kripto
        $groupedByAsset = $entries
            ->whereNotNull("asset_id")
            ->groupBy("asset_id");

        $cryptoPortfolioValue = 0;
        $totalBtcQuantity = 0;

        $groupedByAsset->each(function ($assetEntries) use (
            &$cryptoPortfolioValue,
            &$totalBtcQuantity,
            $currentPricePerUnit,
        ) {
            $totalQuantity =
                $assetEntries->where("type", "buy")->sum("quantity") -
                $assetEntries->where("type", "sell")->sum("quantity");
            if ($totalQuantity <= 0) {
                return;
            }

            $cryptoPortfolioValue += $totalQuantity * $currentPricePerUnit;

            if ($assetEntries->first()->asset->symbol === "BTC") {
                $totalBtcQuantity += $totalQuantity;
            }
        });

        $totalInvestment = $entries->where("type", "buy")->sum("amount");
        $totalSellValue = $entries->where("type", "sell")->sum("amount");
        $totalProfitLoss =
            $cryptoPortfolioValue + $totalSellValue - $totalInvestment;

        $overallPnlPercentage =
            $totalInvestment > 0
                ? ($totalProfitLoss / $totalInvestment) * 100
                : 0;

        // 1 BTC = 100.000.000 Satoshi
        $totalSatoshi = $totalBtcQuantity * 100000000;

        $this->summary = (object) [
            "total_asset_value" => $cryptoPortfolioValue,
            "total_btc_quantity" => $totalBtcQuantity,
            "total_satoshi" => $totalSatoshi, // Masukkan ke object summary
            "total_pnl" => $totalProfitLoss,
            "overall_pnl_percentage" => $overallPnlPercentage,
        ];
    }

    public function loadRecentTransactions(): void
    {
        $this->recentTransactions = FinancialEntry::with("asset")
            ->where("user_id", Auth::id())
            ->orderBy("transaction_date", "desc")
            ->limit(5)
            ->get();
    }

    public function loadRecentPost(): void
    {
        $this->recentPosts = Post::orderBy("created_at", "desc")
            ->limit(6)
            ->get();
    }

    public function loadChartData(): void
    {
        $entries = FinancialEntry::where("user_id", Auth::id())
            ->whereIn("type", ["buy", "sell"])
            ->orderBy("transaction_date", "asc")
            ->get();

        if ($entries->isEmpty()) {
            return;
        }

        $labels = [];
        $data = [];
        $startDate = now()->subDays(30);

        // Mock historical prices
        $priceToday = $this->currentBtcPrice;
        $historicalPrices = [];
        for ($i = 0; $i <= 30; $i++) {
            $date = now()->subDays($i);
            // price fluctuation
            $dayBasedFluctuation = ($date->day % 7) - 3;
            $fluctuation = $dayBasedFluctuation / 100;

            $historicalPrices[$date->format("Y-m-d")] =
                $priceToday * (1 - $i * 0.01 + $fluctuation);
        }

        for ($date = $startDate->copy(); $date->lte(now()); $date->addDay()) {
            $labels[] = $date->format("d M");

            $entriesUpToDate = $entries->where("transaction_date", "<=", $date);

            $totalQuantity =
                $entriesUpToDate->where("type", "buy")->sum("quantity") -
                $entriesUpToDate->where("type", "sell")->sum("quantity");

            $priceOnDate =
                $historicalPrices[$date->format("Y-m-d")] ?? $priceToday;

            $data[] = $totalQuantity * $priceOnDate;
        }

        $this->chartData = [
            "labels" => $labels,
            "data" => $data,
        ];
    }

    public function with(): array
    {
        return [
            "summaryData" => $this->summary,
            "latestTransactions" => $this->recentTransactions,
            "latestPosts" => $this->recentPosts,
            "chartData" => $this->chartData,
        ];
    }
}; ?>

<div wire:poll.60s="updateData">
    <!-- Page Content -->
    <h1 class="mb-6 text-2xl lg:text-3xl font-bold text-white">
        {{ $this->getGreeting() }}, {{ auth()->user()->name }}! 👋
    </h1>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Card 1: Harga BTC Saat Ini -->
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-slate-400 font-medium">
                    Harga BTC Saat Ini
                    <i class="text-xs">{{ now()->format("d M Y") }}</i>
                </h3>
                <x-icon name="lucide.bitcoin" class="text-slate-500" />
            </div>
            <p class="text-3xl font-bold text-white">
                Rp {{ number_format($this->currentBtcPrice, 0, ",", ".") }}
            </p>
            <p class="mt-1 text-sm text-slate-400">Update real-time</p>
        </div>
        <!-- Card 2: Total Aset -->
        <div class="card p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-medium text-slate-400">Total Nilai Aset</h3>
                <x-icon name="lucide.landmark" class="text-slate-500" />
            </div>
            <p class="text-3xl font-bold text-white">
                Rp
                {{ number_format($summaryData->total_asset_value, 0, ",", ".") }}
            </p>
            @if ($summaryData->total_pnl != 0)
                <p
                    class="mt-1 flex items-center text-sm {{ $summaryData->overall_pnl_percentage >= 0 ? "text-green-500" : "text-red-500" }}"
                >
                    @if ($summaryData->overall_pnl_percentage >= 0)
                        <x-icon name="lucide.arrow-up" class="mr-1 h-4 w-4" />
                    @else
                        <x-icon name="lucide.arrow-down" class="mr-1 h-4 w-4" />
                    @endif
                    {{ number_format($summaryData->overall_pnl_percentage, 2, ",", ".") }}%
                </p>
            @endif
        </div>
        <!-- Card 3: Saldo Bitcoin -->
        <div class="card p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-medium text-slate-400">Portofolio Bitcoin</h3>
                <x-icon name="lucide.bitcoin" class="text-slate-500" />
            </div>
            <p class="text-3xl font-bold text-white">
                {{ rtrim(rtrim(number_format($summaryData->total_btc_quantity, 8, ".", "."), "0"), ".") }}
                BTC
            </p>
            <p class="mt-1 text-sm text-slate-400">
                ~ Rp
                {{ number_format($summaryData->total_asset_value, 0, ",", ".") }}
                =
                <span class="text-green-600 font-bold">
                    {{ number_format($summaryData->total_satoshi, 0, ",", ".") }}
                </span>
                Satoshi
            </p>
        </div>
        <!-- Card 4: Laba / Rugi -->
        <div class="card p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-medium text-slate-400">Total Laba/Rugi</h3>
                @if ($summaryData->total_pnl >= 0)
                    <x-icon name="lucide.trending-up" class="text-slate-500" />
                @else
                    <x-icon
                        name="lucide.trending-down"
                        class="text-slate-500"
                    />
                @endif
            </div>
            <p
                class="text-3xl font-bold {{ $summaryData->total_pnl >= 0 ? "text-green-500" : "text-red-500" }}"
            >
                {{ $summaryData->total_pnl >= 0 ? "+" : "" }} Rp
                {{ number_format($summaryData->total_pnl, 0, ",", ".") }}
            </p>
            <p class="mt-1 text-sm text-slate-400">
                Sejak bergabung ~
                {{ auth()->user()->created_at->diffForHumans() }}
            </p>
        </div>
    </div>

    <!-- Chart & Recent Transactions -->
    <div class="grid grid-cols-1 gap-8">
        <!-- Chart -->
        <div class="card p-6">
            <h3 class="mb-4 text-xl font-bold text-white">
                Portofolio (30 Hari Terakhir)
            </h3>
            <div
                wire:ignore
                x-data="{
                    chart: null,
                    updateChart(newLabels, newData) {
                        if (this.chart) {
                            this.chart.data.labels = newLabels
                            this.chart.data.datasets[0].data = newData
                            this.chart.update()
                        }
                    },
                    initChart(labels, data) {
                        if (this.chart) {
                            this.chart.destroy()
                        }
                        const ctx = this.$refs.canvas.getContext('2d')
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300)
                        gradient.addColorStop(0, 'rgba(56, 189, 248, 0.5)')
                        gradient.addColorStop(1, 'rgba(56, 189, 248, 0)')
                        this.chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: 'Nilai Portofolio (Rp)',
                                        data: data,
                                        backgroundColor: gradient,
                                        borderColor: '#38bdf8',
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
                                    datalabels: { display: false },
                                    legend: { display: false },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: '#1e293b',
                                        titleColor: '#f1f5f9',
                                        bodyColor: '#cbd5e1',
                                        padding: 10,
                                        cornerRadius: 4,
                                        callbacks: {
                                            label: function (context) {
                                                let label = context.dataset.label || ''
                                                if (label) {
                                                    label += ': '
                                                }
                                                if (context.parsed.y !== null) {
                                                    label += new Intl.NumberFormat('id-ID', {
                                                        style: 'currency',
                                                        currency: 'IDR',
                                                        minimumFractionDigits: 0,
                                                    }).format(context.parsed.y)
                                                }
                                                return label
                                            },
                                        },
                                    },
                                },
                            },
                        })
                    },
                }"
                x-init="initChart(@js($chartData["labels"]), @js($chartData["data"]))"
                @update-dashboard-chart.window="initChart($event.detail.chartData.labels, $event.detail.chartData.data)"
                class="h-80"
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="grid lg:grid-cols-3 lg:gap-6 grid-cols-1">
            <div class="card p-6 mb-8 lg:mb-0 lg:col-span-2">
                <h3 class="mb-4 text-xl font-bold text-white">
                    Aktivitas Terkini
                </h3>
                <div class="space-y-4">
                    @forelse ($latestTransactions as $transaction)
                        <!-- Transaction Item 1 -->
                        <div class="flex items-center">
                            <div
                                class="mr-4 flex h-10 w-10 items-center justify-center rounded-full @if ($transaction->type == 'buy') bg-green-500/10 @elseif($transaction->type == 'sell') bg-red-500/10 @elseif($transaction->type == 'income') bg-sky-500/10 @else bg-orange-500/10 @endif"
                            >
                                @if ($transaction->type == "buy")
                                    <x-icon
                                        name="lucide.arrow-down-left"
                                        class="h-5 w-5 text-green-500"
                                    />
                                @elseif ($transaction->type == "sell")
                                    <x-icon
                                        name="lucide.arrow-up-right"
                                        class="h-5 w-5 text-red-500"
                                    />
                                @elseif ($transaction->type == "income")
                                    <x-icon
                                        name="lucide.wallet"
                                        class="h-5 w-5 text-sky-500"
                                    />
                                @else
                                    <x-icon
                                        name="lucide.minus"
                                        class="h-5 w-5 text-orange-500"
                                    />
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-white">
                                    {{ Str::title($transaction->asset->name ?? $transaction->category) }}
                                </p>
                                <p class="text-sm text-slate-400">
                                    {{ ($transaction->transaction_date ?? $transaction->updated_at)?->format('d M Y') ?? '-' }}
                                </p>
                            </div>

                            @if ($transaction->type == "buy" || $transaction->type == "sell")
                                <div>
                                    <p
                                        class="font-semibold {{ $transaction->type == "buy" ? "text-green-500" : "text-red-500" }}"
                                    >
                                        {{ $transaction->type == "buy" ? "+" : "-" }}{{ rtrim(rtrim(number_format($transaction->quantity, 8, ".", "."), "0"), ".") }}
                                        {{ $transaction->asset->symbol }}
                                    </p>
                                    <p class="text-xs text-slate-400 text-end">
                                        {{ $transaction->type == "buy" ? "Beli" : "Jual" }}
                                        :
                                        {{ number_format($transaction->price_per_unit, 0, ",", ".") }}
                                        <br />
                                        {{ number_format($transaction->amount, 0, ",", ".") }}
                                        <br />
                                        <span class="text-green-600">
                                            {{ number_format($transaction->quantity * 100000000, 0, ",", ".") }}
                                            Sats
                                        </span>
                                    </p>
                                </div>
                            @else
                                <p class="font-semibold text-white">
                                    {{ $transaction->type == "income" ? "+" : "-" }}Rp
                                    {{ number_format($transaction->amount, 0, ",", ".") }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="py-4 text-center text-slate-400">
                            Belum ada aktivitas.
                        </p>
                    @endforelse
                </div>
            </div>
            <div class="card p-6">
                <h3 class="mb-3 text-md font-bold text-white">
                    Artikel Terkini
                </h3>
                <div class="space-y-4">
                    @forelse ($latestPosts as $post)
                        <!-- Berita Item 1 -->
                        <div class="flex items-center">
                            <div class="mr-4">
                                <a
                                    href="/blog/show/{{ $post->slug }}"
                                    target="_blank"
                                >
                                    <img
                                        src="{{ $post->featured_image_path ?? "https://placehold.co/600x400/1E293B/FFFFFF?text=PortoKu.id" }}"
                                        alt="berita"
                                        class="w-15 h-15 object-cover"
                                    />
                                </a>
                            </div>
                            <div class="flex-1">
                                <p
                                    class="font-semibold text-white lg:block hidden"
                                >
                                    <a
                                        href="/blog/show/{{ $post->slug }}"
                                        target="_blank"
                                        class="hover:text-sky-400"
                                    >
                                        {{ Str::limit($post->title, 20) }}
                                    </a>
                                </p>
                                <p class="font-semibold text-white lg:hidden">
                                    <a
                                        href="/blog/show/{{ $post->slug }}"
                                        target="_blank"
                                        class="hover:text-sky-400"
                                    >
                                        {{ Str::limit($post->title, 25) }}
                                    </a>
                                </p>
                                <p class="text-sm text-slate-400">
                                    {{ $post->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-slate-400">
                            Belum ada berita.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
