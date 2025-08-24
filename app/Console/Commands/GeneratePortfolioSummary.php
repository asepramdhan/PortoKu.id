<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\FinancialEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PortfolioDailySummary; // Kita akan buat ini selanjutnya

class GeneratePortfolioSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-portfolio-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily portfolio summary for all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating portfolio summaries...');

        // Ambil harga BTC saat ini dan kemarin (simulasi)
        try {
            $response = Http::get('https://api.coingecko.com/api/v3/coins/bitcoin/market_chart', [
                'vs_currency' => 'idr',
                'days' => '1',
                'interval' => 'daily',
            ])->json();
            $priceToday = $response['prices'][1][1] ?? 1950000000;
            $priceYesterday = $response['prices'][0][1] ?? 1940000000;
        } catch (\Exception $e) {
            $priceToday = 1950000000;
            $priceYesterday = 1940000000;
        }

        $users = User::all();

        foreach ($users as $user) {
            $entries = FinancialEntry::where('user_id', $user->id)->whereNotNull('asset_id')->get();
            if ($entries->isEmpty()) {
                continue;
            }

            $quantityToday = $entries->where('type', 'buy')->sum('quantity') - $entries->where('type', 'sell')->sum('quantity');
            $valueToday = $quantityToday * $priceToday;

            $entriesYesterday = $entries->where('transaction_date', '<', now()->startOfDay());
            $quantityYesterday = $entriesYesterday->where('type', 'buy')->sum('quantity') - $entriesYesterday->where('type', 'sell')->sum('quantity');
            $valueYesterday = $quantityYesterday * $priceYesterday;

            if ($valueYesterday > 0) {
                $percentageChange = (($valueToday - $valueYesterday) / $valueYesterday) * 100;
            } else {
                $percentageChange = $valueToday > 0 ? 100 : 0;
            }

            // Kirim notifikasi ke user
            $user->notify(new PortfolioDailySummary($percentageChange, $valueToday));
        }

        $this->info('Done.');
        return 0;
    }
}
