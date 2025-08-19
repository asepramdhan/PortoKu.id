<?php

use App\Models\FinancialEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
  public $filterRange = 'this_month';

  public $summary;
  public $incomeExpenseChartData = [];
  public $expenseCategoryChartData = [];

  public function mount(): void
  {
    $this->loadReportData();
  }

  public function updatedFilterRange(): void
  {
    $this->loadReportData();
  }

  public function loadReportData(): void
  {
    [$startDate, $endDate] = $this->getDateRange();

    $entries = FinancialEntry::where('user_id', Auth::id())
      ->whereBetween('transaction_date', [$startDate, $endDate])
      ->get();

    // Calculate Summary
    $totalIncome = $entries->where('type', 'income')->sum('amount');
    $totalExpense = $entries->where('type', 'expense')->sum('amount');
    $netCashFlow = $totalIncome - $totalExpense;

    $this->summary = (object) [
      'total_income' => $totalIncome,
      'total_expense' => $totalExpense,
      'net_cash_flow' => $netCashFlow,
    ];

    // Prepare Income vs Expense Chart Data (Bar Chart)
    $this->incomeExpenseChartData = [
      'labels' => ['Pemasukan', 'Pengeluaran'],
      'data' => [$totalIncome, $totalExpense],
    ];

    // Prepare Expense Category Chart Data (Pie Chart)
    $expenseByCategory = $entries->where('type', 'expense')
      ->groupBy('category')
      ->map->sum('amount');

    $this->expenseCategoryChartData = [
      'labels' => $expenseByCategory->keys()->map(fn($label) => Str::title($label))->toArray(),
      'data' => $expenseByCategory->values()->toArray(),
    ];

    $this->dispatch('update-charts', [
      'incomeExpenseData' => $this->incomeExpenseChartData,
      'expenseCategoryData' => $this->expenseCategoryChartData,
    ]);
  }

  protected function getDateRange(): array
  {
    switch ($this->filterRange) {
      case 'last_month':
        return [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];
      case 'this_year':
        return [now()->startOfYear(), now()->endOfYear()];
      default: // this_month
        return [now()->startOfMonth(), now()->endOfMonth()];
    }
  }

  public function downloadReport($type = 'csv')
  {
    [$startDate, $endDate] = $this->getDateRange();

    $entries = FinancialEntry::with('asset')->where('user_id', Auth::id())
      ->whereBetween('transaction_date', [$startDate, $endDate])
      ->orderBy('transaction_date', 'asc')
      ->get();

    if ($entries->isEmpty()) {
      session()->flash('message', 'Tidak ada data untuk diunduh pada rentang waktu ini.');
      return;
    }

    $fileName = 'laporan-keuangan-' . Str::slug($this->filterRange) . '-' . now()->format('Y-m-d');

    if ($type === 'pdf') {
      $pdf = Pdf::loadView('livewire.update.reports.pdf-view', [
        'entries' => $entries,
        'startDate' => $startDate,
        'endDate' => $endDate,
      ]);
      return response()->streamDownload(function () use ($pdf) {
        echo $pdf->stream();
      }, $fileName . '.pdf');
    }

    // Default to CSV
    $headers = [
      'Content-type' => 'text/csv',
      'Content-Disposition' => "attachment; filename=$fileName.csv",
      'Pragma' => 'no-cache',
      'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
      'Expires' => '0',
    ];

    $callback = function () use ($entries) {
      $file = fopen('php://output', 'w');
      fputcsv($file, ['Tanggal', 'Tipe', 'Kategori/Aset', 'Jumlah (IDR)', 'Catatan']);

      foreach ($entries as $entry) {
        fputcsv($file, [
          $entry->transaction_date->format('Y-m-d'),
          Str::title($entry->type),
          $entry->asset->name ?? $entry->category,
          $entry->amount,
          $entry->notes,
        ]);
      }
      fclose($file);
    };

    return response()->stream($callback, 200, $headers);
  }

  public function with(): array
  {
    return [
      'summaryData' => $this->summary,
      'incomeExpenseData' => $this->incomeExpenseChartData,
      'expenseCategoryData' => $this->expenseCategoryChartData,
    ];
  }
}; ?>

<div>
  <!-- Page Content -->
  <div class="mb-6 flex flex-col justify-between md:flex-row md:items-center">
    <h1 class="text-3xl font-bold text-white">Laporan Keuangan</h1>
    <div class="mt-4 flex items-center gap-4 md:mt-0">
      <select wire:model.live="filterRange" class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
        <option value="this_month">Bulan Ini</option>
        <option value="last_month">Bulan Lalu</option>
        <option value="this_year">Tahun Ini</option>
      </select>
      <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" wire:loading.attr="disabled" class="flex cursor-pointer items-center gap-2 rounded-lg bg-sky-500 px-4 py-2 font-semibold text-white transition-colors hover:bg-sky-600">
          <x-loading wire:loading wire:target="downloadReport" class="loading-dots" />
          <span wire:loading.remove wire:target="downloadReport">
            <div class="flex items-center gap-2">
              <x-icon name="lucide.download" class="h-5 w-5" />
              <span>Unduh Laporan</span>
            </div>
          </span>
        </button>

        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-20 mt-2 w-40 rounded-md bg-slate-700 shadow-lg" x-cloak>
          <a href="#" wire:click.prevent="downloadReport('csv')" @click="open = false" class="block rounded-t-md px-4 py-2 text-sm text-slate-200 hover:bg-slate-600">Unduh sebagai
            CSV</a>
          <a href="#" wire:click.prevent="downloadReport('pdf')" @click="open = false" class="block rounded-b-md px-4 py-2 text-sm text-slate-200 hover:bg-slate-600">Unduh sebagai
            PDF</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
    <div class="card p-6">
      <h3 class="mb-2 font-medium text-slate-400">Total Pemasukan</h3>
      <p class="text-3xl font-bold text-green-500">+ Rp {{ number_format($summaryData->total_income, 0, ',', '.') }}
      </p>
    </div>
    <div class="card p-6">
      <h3 class="mb-2 font-medium text-slate-400">Total Pengeluaran</h3>
      <p class="text-3xl font-bold text-red-500">- Rp {{ number_format($summaryData->total_expense, 0, ',', '.') }}
      </p>
    </div>
    <div class="card p-6">
      <h3 class="mb-2 font-medium text-slate-400">Arus Kas Bersih</h3>
      <p class="text-3xl font-bold {{ $summaryData->net_cash_flow >= 0 ? 'text-white' : 'text-red-500' }}">
        Rp {{ number_format($summaryData->net_cash_flow, 0, ',', '.') }}
      </p>
    </div>
  </div>

  <!-- Charts -->
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-5">
    <!-- Main Chart -->
    <div class="card lg:col-span-3 p-6" x-data="{
        chart: null,
        initChart(labels, data) {
          if (this.chart) this.chart.destroy();
          const ctx = this.$refs.canvas.getContext('2d');
          this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [{
                label: 'Jumlah (Rp)',
                data: data,
                backgroundColor: ['rgba(56, 189, 248, 0.5)', 'rgba(249, 115, 22, 0.5)'],
                borderColor: ['#38bdf8', '#f97316'],
                borderWidth: 1
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
                    color: '#1e2d3b'
                  }
                },
                x: {
                  ticks: {
                    color: '#94a3b8'
                  },
                  grid: {
                    display: false
                  }
                }
              },
              plugins: {
                legend: {
                  display: false
                }
              }
            }
          });
        }
      }" x-init="initChart(@js($incomeExpenseData['labels']), @js($incomeExpenseData['data']))" @update-charts.window="initChart($event.detail.incomeExpenseData.labels, $event.detail.incomeExpenseData.data)">
      <h3 class="mb-4 text-xl font-bold text-white">Pemasukan vs Pengeluaran</h3>
      <div class="flex h-80 items-center justify-center rounded-lg bg-slate-800 p-4">
        <canvas x-ref="canvas"></canvas>
      </div>
    </div>
    <!-- Pie Chart -->
    <div class="card lg:col-span-2 p-6" x-data="{
        chart: null,
        initChart(labels, data) {
          if (this.chart) this.chart.destroy();
          const ctx = this.$refs.pieCanvas.getContext('2d');
          this.chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [{
                data: data,
                backgroundColor: ['#38bdf8', '#fb923c', '#a78bfa', '#f472b6', '#4ade80', '#facc15', '#22d3ee'],
                borderColor: '#0f172a',
                borderWidth: 2
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: {
                    color: '#94a3b8'
                  }
                }
              }
            }
          });
        }
      }" x-init="initChart(@js($expenseCategoryData['labels']), @js($expenseCategoryData['data']))" @update-charts.window="initChart($event.detail.expenseCategoryData.labels, $event.detail.expenseCategoryData.data)">
      <h3 class="mb-4 text-xl font-bold text-white">Alokasi Pengeluaran</h3>
      <div class="flex h-80 items-center justify-center rounded-lg bg-slate-800 p-4">
        <canvas x-ref="pieCanvas"></canvas>
      </div>
    </div>
  </div>
</div>
