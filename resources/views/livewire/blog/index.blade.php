<?php

use App\Models\Post;
use App\Models\ShopeeAd;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use Illuminate\Pagination\LengthAwarePaginator; // Import ini untuk paginasi

new class extends Component {
    use WithPagination; // Membuat pencarian muncul di URL

    #[Url(as: "q")]
    public string $search = "";

    public function with(): array
    {
        // Ambil 2 postingan terpopuler, diurutkan descending
        $trends = Post::orderByDesc("views_count")
            ->take(2)
            ->get();

        // Ekstrak semua ID dari postingan yang sudah diambil (yang trending)
        $trendIds = $trends->pluck("id")->toArray();

        $query = Post::whereNotNull("published_at")
            ->whereNotIn("id", $trendIds)
            ->where("published_at", "<=", now())
            ->orderBy("published_at", "desc");

        // FIX: Tambahkan logika untuk memfilter berdasarkan pencarian
        if ($this->search) {
            $query->where(function ($q) {
                $q->where("title", "like", "%" . $this->search . "%")->orWhere(
                    "content",
                    "like",
                    "%" . $this->search . "%",
                );
            });
        }

        // buat iklan shopee secara random
        // Ambil hanya iklan yang dipublikasikan
        $ads = ShopeeAd::where("is_published", true)->get();
        // Inisialisasi $ad sebagai null atau object kosong
        $ad = null;
        // Cek apakah ada iklan yang tersedia sebelum memanggil random()
        if ($ads->isNotEmpty()) {
            // Ambil iklan secara acak hanya jika ada data
            $ad = $ads->random();
        }

        return [
            "posts" => $query->paginate(9),
            "trends" => $trends,
            "ad" => $ad,
        ];
    }
}; ?>

<div>
    <main>
        <!-- ===== Page Header ===== -->
        <section class="py-20 text-center">
            <div class="container mx-auto px-2 lg:px-6">
                <h1 class="text-4xl md:text-5xl font-extrabold text-white">
                    Blog & Wawasan Keuangan
                </h1>
                <p
                    class="mt-4 text-lg md:text-xl text-slate-400 max-w-3xl mx-auto"
                >
                    Temukan artikel terbaru seputar dunia kripto, strategi
                    investasi, dan tips manajemen keuangan pribadi dari tim
                    kami.
                </p>
            </div>
        </section>

        <!-- ===== Blog Grid Section ===== -->
        <section class="py-20">
            <div class="container mx-auto px-2 lg:px-6">
                {{-- FIX: Tambahkan form pencarian --}}
                <div class="mb-12 max-w-lg mx-auto">
                    <div class="relative">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3"
                        >
                            <i
                                data-lucide="search"
                                class="w-5 h-5 text-slate-400"
                            ></i>
                        </span>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari artikel berdasarkan judul..."
                            class="form-input w-full pl-10 !py-3"
                        />
                    </div>
                </div>

                @if ($posts->count() > 0)
                    <h2
                        class="text-2xl text-center md:text-3xl font-bold text-slate-700 mb-6"
                    >
                        Tranding
                        <span class="animate-pulse">🔥</span>
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                        @foreach ($trends as $post)
                            <div class="blog-card flex flex-col">
                                <a
                                    href="/blog/show/{{ $post->slug }}"
                                    wire:navigate
                                    class="block"
                                >
                                    <img
                                        src="{{ $post->featured_image_path ?? "https://placehold.co/600x400/1E293B/FFFFFF?text=PortoKu.id" }}"
                                        alt="Gambar thumbnail untuk {{ $post->title }}"
                                        class="w-full h-48 lg:h-78 object-cover"
                                    />
                                </a>
                                <div class="p-6 flex flex-col flex-grow">
                                    @if ($post->category)
                                        {{-- FIX: Tautan kategori sekarang bisa diklik --}}
                                        <a
                                            href="/blog/category/{{ $post->category->slug }}"
                                            wire:navigate
                                            class="text-sm font-semibold text-sky-400 uppercase hover:underline"
                                        >
                                            {{ $post->category->name }}
                                        </a>
                                    @endif

                                    <h2
                                        class="mt-2 text-xl font-bold text-white group-hover:text-sky-400 transition-colors"
                                    >
                                        <span class="animate-pulse">🔥</span>
                                        {{ $post->title }}
                                    </h2>
                                    <p class="mt-3 text-slate-400 text-sm">
                                        {{ Str::limit(strip_tags($post->content), 120) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- START: SLOT IKLAN AFFILIATE SHOPEE - POSISI 1 --}}

                    @if ($ad)
                        <div class="my-10 lg:p-6 text-center">
                            <p class="text-lg font-bold text-sky-400 mb-4">
                                Produk Tranding di Shopee
                            </p>

                            {{-- GANTI DENGAN KODE IKLAN SHOPEE AFFILIATE ANDA --}}

                            <div class="p-4 lg:mx-65 rounded-lg blog-card">
                                <img
                                    src="{{ $ad->image_path ?? "https://placehold.co/600x400/1E293B/FFFFFF?text=PortoKu.id" }}"
                                    alt="Gambar thumbnail untuk {{ $ad->product_name }}"
                                    class="w-full h-48 lg:h-78 object-cover"
                                />

                                <h2
                                    class="mt-2 text-xl font-bold text-white group-hover:text-sky-400 transition-colors"
                                >
                                    {{ $ad->product_name }}
                                </h2>

                                <p class="mt-3 text-slate-400 text-sm">
                                    {{ Str::limit(strip_tags($ad->description), 120) }}
                                </p>

                                <a
                                    href="{{ $ad->ad_link }}"
                                    target="_blank"
                                    class="inline-block px-6 py-3 my-4 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-full transition duration-300 animate-pulse hover:animate-none"
                                >
                                    Order Sebelum Kehabisan
                                </a>
                                <p class="text-sm text-slate-400">
                                    Stok Terbatas
                                </p>
                            </div>
                            {{-- JIKA KODE SHOPEE ANDA ADALAH JS/IFRAME, MASUKKAN DI SINI --}}
                        </div>
                    @endif

                    {{-- END: SLOT IKLAN AFFILIATE SHOPEE - POSISI 1 --}}

                    <h2
                        class="text-2xl text-center md:text-3xl font-bold text-slate-700 mb-6"
                    >
                        Terbaru
                    </h2>

                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                    >
                        @foreach ($posts as $post)
                            <div class="blog-card flex flex-col">
                                <a
                                    href="/blog/show/{{ $post->slug }}"
                                    wire:navigate
                                    class="block"
                                >
                                    <img
                                        src="{{ $post->featured_image_path ?? "https://placehold.co/600x400/1E293B/FFFFFF?text=PortoKu.id" }}"
                                        alt="Gambar thumbnail untuk {{ $post->title }}"
                                        class="w-full h-48 object-cover"
                                    />
                                </a>
                                <div class="p-6 flex flex-col flex-grow">
                                    @if ($post->category)
                                        {{-- FIX: Tautan kategori sekarang bisa diklik --}}
                                        <a
                                            href="/blog/category/{{ $post->category->slug }}"
                                            wire:navigate
                                            class="text-sm font-semibold text-sky-400 uppercase hover:underline"
                                        >
                                            {{ $post->category->name }}
                                        </a>
                                    @endif

                                    <a
                                        href="/blog/show/{{ $post->slug }}"
                                        wire:navigate
                                        class="group"
                                    >
                                        <h2
                                            class="mt-2 text-xl font-bold text-white group-hover:text-sky-400 transition-colors"
                                        >
                                            {{ $post->title }}
                                        </h2>
                                    </a>
                                    <p
                                        class="mt-3 text-slate-400 text-sm flex-grow"
                                    >
                                        {{ Str::limit(strip_tags($post->content), 120) }}
                                    </p>
                                    <p class="mt-4 text-xs text-slate-500">
                                        {{ $post->published_at->format("d M Y") }}
                                    </p>
                                </div>
                            </div>
                            {{-- START: SLOT IKLAN AFFILIATE SHOPEE - POSISI 2 (Setelah Postingan ke-3) --}}

                            @if ($loop->index + 1 == 10)
                                @if ($ad)
                                    <div
                                        class="blog-card flex flex-col md:col-span-2 lg:col-span-3"
                                    >
                                        {{-- Ini akan memakan lebar 3 kolom agar iklan banner lebih besar. --}}

                                        <div
                                            class="p-6 bg-yellow-900 rounded-lg shadow-xl text-center"
                                        >
                                            <p
                                                class="text-xl font-bold text-white mb-4"
                                            >
                                                Diskon Khusus Hanya untuk Anda!
                                            </p>

                                            {{-- GANTI DENGAN KODE IKLAN SHOPEE AFFILIATE ANDA --}}
                                            <a href="{{ $ad->ad_link }}">
                                                <h3
                                                    class="text-xl font-bold text-sky-400 hover:underline animate-pulse hover:animate-none mb-4 transition duration-300"
                                                >
                                                    {{ $ad->product_name }}
                                                </h3>
                                            </a>

                                            <a
                                                href="{{ $ad->ad_link }}"
                                                target="_blank"
                                                class="inline-block px-8 py-4 bg-orange-600 hover:bg-orange-700 text-white text-lg font-semibold rounded-lg transition duration-300"
                                            >
                                                Cek Promonya Sekarang!
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            {{-- END: SLOT IKLAN AFFILIATE SHOPEE - POSISI 2 --}}
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-12">
                        {{ $posts->links("livewire.tailwind-custom") }}
                    </div>
                @else
                    <div class="text-center text-slate-400">
                        <p>Belum ada postingan yang diterbitkan.</p>
                    </div>
                @endif
            </div>
        </section>
    </main>
</div>
