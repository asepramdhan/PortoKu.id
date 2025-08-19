<?php

use App\Models\FinancialEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

new class extends Component {
  public $summary;
  public Collection $recentTransactions;
  public $currentBtcPrice = 0;
  public array $chartData = [
    'labels' => [],
    'data' => [],
  ];

  public function mount(): void
  {
    $this->calculateSummary();
    $this->loadRecentTransactions();
    $this->loadChartData();
  }

  public function calculateSummary(): void
  {
    // 1. Ambil harga Bitcoin saat ini dari API
    try {
      $response = Http::get('https://api.coingecko.com/api/v3/simple/price', [
        'ids' => 'bitcoin',
        'vs_currencies' => 'idr',
      ])->json();
      // Ambil harga, jika gagal, gunakan harga statis sebagai fallback
      $currentPricePerUnit = $response['bitcoin']['idr'] ?? 1950000000; 
    } catch (\Exception $e) {
      // Jika API error, gunakan harga statis
      $currentPricePerUnit = 1950000000;
    }

    $this->currentBtcPrice = $currentPricePerUnit;

    $entries = FinancialEntry::with('asset')
      ->where('user_id', Auth::id())
      ->get();

    // Kalkulasi untuk Aset Kripto
    $groupedByAsset = $entries->whereNotNull('asset_id')->groupBy('asset_id');

    $cryptoPortfolioValue = 0;
    $totalBtcQuantity = 0;

    // Kita akan menghitung nilai dan jumlah total dari semua aset kripto
    $groupedByAsset->each(function ($assetEntries) use (&$cryptoPortfolioValue, &$totalBtcQuantity, $currentPricePerUnit) {
      $totalQuantity = $assetEntries->where('type', 'buy')->sum('quantity') - $assetEntries->where('type', 'sell')->sum('quantity');
      if ($totalQuantity <= 0) {
        return;
      }

      // TODO: Ganti dengan API harga dinamis berdasarkan simbol aset
      // $currentPricePerUnit = 1950000000;
      $cryptoPortfolioValue += $totalQuantity * $currentPricePerUnit;

      // Khusus untuk kartu Saldo Bitcoin
      if ($assetEntries->first()->asset->symbol === 'BTC') {
        $totalBtcQuantity += $totalQuantity;
      }
    });

    $totalInvestment = $entries->where('type', 'buy')->sum('amount');
    $totalSellValue = $entries->where('type', 'sell')->sum('amount');
    $totalProfitLoss = ($cryptoPortfolioValue + $totalSellValue) - $totalInvestment;

    $overallPnlPercentage = ($totalInvestment > 0) ? ($totalProfitLoss / $totalInvestment) * 100 : 0;

    $this->summary = (object) [
      'total_asset_value' => $cryptoPortfolioValue,
      'total_btc_quantity' => $totalBtcQuantity,
      'total_pnl' => $totalProfitLoss,
      'overall_pnl_percentage' => $overallPnlPercentage,
    ];
  }

  public function loadRecentTransactions(): void
  {
    $this->recentTransactions = FinancialEntry::with('asset')
      ->where('user_id', Auth::id())
      ->orderBy('transaction_date', 'desc')
      ->limit(5)
      ->get();
  }

  public function loadChartData(): void
  {
    $entries = FinancialEntry::where('user_id', Auth::id())
      ->whereIn('type', ['buy', 'sell'])
      ->orderBy('transaction_date', 'asc')
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
      $dayBasedFluctuation = (($date->day % 7) - 3);
      $fluctuation = ($dayBasedFluctuation / 100);
      
      $historicalPrices[$date->format('Y-m-d')] = $priceToday * (1 - ($i * 0.01) + $fluctuation);
    }

    for ($date = $startDate->copy(); $date->lte(now()); $date->addDay()) {
      $labels[] = $date->format('d M');

      $entriesUpToDate = $entries->where('transaction_date', '<=', $date);

      $totalQuantity = $entriesUpToDate->where('type', 'buy')->sum('quantity') - $entriesUpToDate->where('type', 'sell')->sum('quantity');

      $priceOnDate = $historicalPrices[$date->format('Y-m-d')] ?? $priceToday;

      $data[] = $totalQuantity * $priceOnDate;
    }

    $this->chartData = [
      'labels' => $labels,
      'data' => $data,
    ];
  }

  public function with(): array
  {
    return [
      'summaryData' => $this->summary,
      'latestTransactions' => $this->recentTransactions,
      'chartData' => $this->chartData,
    ];
  }
}; ?>

<div>
  <!-- Page Content -->
  <h1 class="mb-6 text-2xl lg:text-3xl font-bold text-white">Selamat Datang Kembali! 👋</h1>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Card 1: Harga BTC Saat Ini -->
    <div class="card p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-slate-400 font-medium">Harga BTC Saat Ini</h3>
        <x-icon name="lucide.bitcoin" class="text-slate-500" />
      </div>
      <p class="text-3xl font-bold text-white">Rp {{ number_format($this->currentBtcPrice, 0, ',', '.') }}</p>
    </div>
    <!-- Card 2: Total Aset -->
    <div class="card p-6">
      <div class="mb-4 flex items-center justify-between">
        <h3 class="font-medium text-slate-400">Total Nilai Aset</h3>
        <x-icon name="lucide.landmark" class="text-slate-500" />
      </div>
      <p class="text-3xl font-bold text-white">Rp {{ number_format($summaryData->total_asset_value, 0, ',', '.') }}</p>
      @if ($summaryData->total_pnl != 0)
      <p class="mt-1 flex items-center text-sm {{ $summaryData->overall_pnl_percentage >= 0 ? 'text-green-500' : 'text-red-500' }}">
        @if ($summaryData->overall_pnl_percentage >= 0)
        <x-icon name="lucide.arrow-up" class="mr-1 h-4 w-4" />
        @else
        <x-icon name="lucide.arrow-down" class="mr-1 h-4 w-4" />
        @endif
        {{ number_format($summaryData->overall_pnl_percentage, 2, ',', '.') }}% Sejak Awal
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
        {{ rtrim(rtrim(number_format($summaryData->total_btc_quantity, 8, '.', '.'), '0'), '.') }} BTC</p>
      <p class="mt-1 text-sm text-slate-400">
        ~ Rp {{ number_format($summaryData->total_asset_value, 0, ',', '.') }}
      </p>
    </div>
    <!-- Card 4: Laba / Rugi -->
    <div class="card p-6">
      <div class="mb-4 flex items-center justify-between">
        <h3 class="font-medium text-slate-400">Total Laba/Rugi</h3>
        <x-icon name="lucide.trending-up" class="text-slate-500" />
      </div>
      <p class="text-3xl font-bold {{ $summaryData->total_pnl >= 0 ? 'text-green-500' : 'text-red-500' }}">
        {{ $summaryData->total_pnl >= 0 ? '+' : '' }} Rp {{ number_format($summaryData->total_pnl, 0, ',', '.') }}</p>
      <p class="mt-1 text-sm text-slate-400">
        Sejak bergabung
      </p>
    </div>
  </div>

  <!-- Chart & Recent Transactions -->
  <div class="grid grid-cols-1 gap-8 xl:grid-cols-3">
    <!-- Chart -->
    <div class="card p-6 xl:col-span-2">
      <h3 class="mb-4 text-xl font-bold text-white">Portofolio (30 Hari Terakhir)</h3>
      <div x-data="{
          labels: @js($chartData['labels']),
          data: @js($chartData['data']),
          init() {
            if (typeof Chart === 'undefined') {
              console.error('Chart.js is not loaded.');
              return;
            }
            const ctx = this.$refs.canvas.getContext('2d');

            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(56, 189, 248, 0.5)');
            gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

            new Chart(ctx, {
              type: 'line',
              data: {
                labels: this.labels,
                datasets: [{
                  label: 'Nilai Portofolio (Rp)',
                  data: this.data,
                  backgroundColor: gradient,
                  borderColor: '#38bdf8',
                  borderWidth: 2,
                  pointBackgroundColor: '#38bdf8',
                  pointBorderColor: '#0f172a',
                  pointHoverBackgroundColor: '#fff',
                  pointHoverBorderColor: '#38bdf8',
                  fill: true,
                  tension: 0.4
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  y: {
                    ticks: {
                      color: '#94a3b8'
                    },
                    grid: {
                      color: '#1e293b'
                    }
                  },
                  x: {
                    ticks: {
                      color: '#94a3b8',
                      maxTicksLimit: 8 // Batasi jumlah label pada sumbu X
                    },
                    grid: {
                      display: false
                    }
                  }
                },
                plugins: {
                  datalabels: {
                    display: false // <-- TAMBAHKAN INI untuk menyembunyikan label
                  },
                  legend: {
                    display: false
                  },
                  tooltip: {
                    mode: 'index',
                    intersect: false, 
                    backgroundColor: '#1e293b',
                    titleColor: '#f1f5f9',
                    bodyColor: '#cbd5e1',
                    padding: 10,
                    cornerRadius: 4,
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                          label += ': ';
                        }
                        if (context.parsed.y !== null) {
                          label += new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            minimumFractionDigits: 0
                          }).format(context.parsed.y);
                        }
                        return label;
                      }
                    }
                  }
                }
              }
            });
          }
        }" class="flex h-80 p-4 items-center justify-center rounded-lg bg-slate-800">
        <canvas x-ref="canvas"></canvas>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card p-6">
      <h3 class="mb-4 text-xl font-bold text-white">Aktivitas Terkini</h3>
      <div class="space-y-4">
        @forelse ($latestTransactions as $transaction)
        <!-- Transaction Item 1 -->
        <div class="flex items-center">
          <div class="mr-4 flex h-10 w-10 items-center justify-center rounded-full @if ($transaction->type == 'buy') bg-green-500/10 @elseif($transaction->type == 'sell') bg-red-500/10 @elseif($transaction->type == 'income') bg-sky-500/10 @else bg-orange-500/10 @endif">
            @if ($transaction->type == 'buy')
            <x-icon name="lucide.arrow-down-left" class="h-5 w-5 text-green-500" />
            @elseif($transaction->type == 'sell')
            <x-icon name="lucide.arrow-up-right" class="h-5 w-5 text-red-500" />
            @elseif($transaction->type == 'income')
            <x-icon name="lucide.wallet" class="h-5 w-5 text-sky-500" />
            @else
            <x-icon name="lucide.minus" class="h-5 w-5 text-orange-500" />
            @endif
          </div>
          <div class="flex-1">
            <p class="font-semibold text-white">{{ Str::title($transaction->asset->name ?? $transaction->category) }}</p>
            <p class="text-sm text-slate-400">
              {{ ($transaction->transaction_date ?? $transaction->updated_at)?->format('d M Y') ?? '-' }}
            </p>
          </div>
          @if ($transaction->type == 'buy' || $transaction->type == 'sell')
          <p class="font-semibold {{ $transaction->type == 'buy' ? 'text-green-500' : 'text-red-500' }}">
            {{ $transaction->type == 'buy' ? '+' : '-' }}{{ rtrim(rtrim(number_format($transaction->quantity, 8, '.', '.'), '0'), '.') }}
            {{ $transaction->asset->symbol }}
          </p>
          @else
          <p class="font-semibold text-white">
            {{ $transaction->type == 'income' ? '+' : '-' }}Rp
            {{ number_format($transaction->amount, 0, ',', '.') }}
          </p>
          @endif
        </div>
        @empty
        <p class="py-4 text-center text-slate-400">Belum ada aktivitas.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
