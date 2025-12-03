<?php

use Livewire\Volt\Component;
use App\Models\FinancialEntry;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    public function with(): array
    {
        return [
            "transactions" => FinancialEntry::latest()->paginate(10),
        ];
    }
}; ?>

<div>
    <!-- Page Content -->
    <h1 class="text-3xl font-bold text-white mb-6">Riwayat Semua Transaksi</h1>

    <!-- Filters -->
    {{--
        <div class="card mb-6 p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <input
        type="text"
        wire:model.live="search"
        placeholder="Cari berdasarkan kategori..."
        class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
        />
        <select
        wire:model.live="filterType"
        class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
        >
        <option value="">Semua Tipe</option>
        <option value="buy">Beli</option>
        <option value="sell">Jual</option>
        <option value="income">Pemasukan</option>
        <option value="expense">Pengeluaran</option>
        </select>
        <select
        wire:model.live="filterAsset"
        class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
        >
        <option value="">Semua Aset</option>
        @foreach ($assets as $asset)
        <option value="{{ $asset->id }}">
        {{ $asset->name }}
        </option>
        @endforeach
        </select>
        <input
        type="date"
        wire:model.live="filterDate"
        class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500"
        />
        </div>
        </div>
    --}}

    <!-- Transactions Table -->
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="text-left truncate">Profil</th>
                        <th class="text-left">Tanggal</th>
                        <th class="text-left">Tipe</th>
                        <th class="text-left">Aset/Kategori</th>
                        <th class="text-left">Jumlah</th>
                        <th class="text-left">Nilai (IDR)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr wire:key="{{ $transaction->id }}">
                            <td class="truncate">
                                <img
                                    src="{{ $transaction->user->profile_photo_path ? asset("storage/" . $transaction->user->profile_photo_path) : "https://placehold.co/48x48/0EA5E9/FFFFFF?text=" . substr($transaction->user->name, 0, 1) }}"
                                    alt="{{ $transaction->user->name }}"
                                    class="w-10 h-10 object-cover rounded-full mb-2"
                                />
                                <p class="text-slate-400 text-sm">
                                    {{ Str::limit($transaction->user->name, 15) }}
                                </p>
                            </td>
                            <td class="text-left text-sm truncate">
                                {{ $transaction->created_at->format("d M Y H:i") }}
                                <br />
                                <span class="text-slate-400 text-xs truncate">
                                    {{ $transaction->updated_at->format("d M Y H:i") }}
                                </span>
                            </td>
                            <td class="text-left">
                                @if ($transaction->type == "buy")
                                    <p class="font-semibold text-green-400">
                                        Beli
                                    </p>
                                @elseif ($transaction->type == "sell")
                                    <p class="font-semibold text-red-400">
                                        Jual
                                    </p>
                                @elseif ($transaction->type == "income")
                                    <p class="font-semibold text-sky-400">
                                        Pemasukan
                                    </p>
                                @else
                                    <p class="font-semibold text-orange-400">
                                        Pengeluaran
                                    </p>
                                @endif
                            </td>
                            <td class="text-left truncate">
                                <p class="font-semibold text-white">
                                    {{ Str::title($transaction->asset->name ?? $transaction->category) }}
                                </p>
                            </td>
                            <td class="text-left truncate">
                                @if ($transaction->quantity && $transaction->asset)
                                    <span class="font-semibold text-white">
                                        {{ rtrim(rtrim(number_format($transaction->quantity, 8, ".", ","), "0"), ".") }}
                                        {{ $transaction->asset->symbol }}
                                    </span>
                                    <br />
                                    <span
                                        class="text-xs text-slate-400 truncate"
                                    >
                                        {{ $transaction->type == "buy" ? "Beli" : "Jual" }}
                                        :
                                        {{ number_format($transaction->price_per_unit, 0, ",", ".") }}
                                    </span>
                                @else
                                        -
                                @endif
                            </td>
                            <td class="text-left truncate">
                                Rp
                                {{ number_format($transaction->amount, 0, ",", ".") }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="py-8 text-center text-slate-400"
                            >
                                Tidak ada transaksi yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="border-t border-slate-800 p-4">
            {{ $transactions->links("livewire.tailwind-custom") }}
        </div>
    </div>
</div>
