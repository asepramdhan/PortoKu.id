<?php

use App\Models\Post;
use App\Models\ShopeeAd;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
// use Illuminate\Pagination\LengthAwarePaginator; // Import ini untuk paginasi

new class extends Component {
    use WithPagination; // Membuat pencarian muncul di URL

    #[Url(as: "q")]
    public string $search = "";

    // Properti untuk mengontrol status pop-up
    public bool $showAdPopup = false;

    // Gunakan fungsi mount() untuk menampilkan pop-up saat pertama kali dimuat
    public function mount()
    {
        // Secara default, pop-up akan ditampilkan saat komponen dimuat
        // Di sini Anda bisa menambahkan logika pengecekan cookie
        // agar pop-up tidak muncul berulang kali (lihat poin 3)
        $this->showAdPopup = true;
    }

    public function recordClickAndRedirect(ShopeeAd $ad): void
    {
        // Setelah diklik, tutup pop-up (walaupun redirect akan memuat ulang halaman)
        $this->showAdPopup = false;

        // Naikkan jumlah klik
        $ad->increment("clicks_count");

        $this->redirect($ad->ad_link);
    }

    public function with(): array
    {
        // FIX: Tambahkan logika untuk memfilter berdasarkan pencarian
        // if ($this->search) {
        //     $query->where(function ($q) {
        //         $q->where("title", "like", "%" . $this->search . "%")->orWhere(
        //             "content",
        //             "like",
        //             "%" . $this->search . "%",
        //         );
        //     });
        // }

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

        // buat pencarian berdasarkan judul
        if ($this->search) {
            $query->where("title", "like", "%" . $this->search . "%");
        }

        return [
            "posts" => $query->paginate(9),
            "trends" => $trends,
            "ad" => $ad,
        ];
    }
}; ?>

<div
    x-data="{
        init() {
            // Cek apakah cookie 'ad_shown' sudah ada
            if (localStorage.getItem('ad_shown') !== 'true') {
                this.$wire.showAdPopup = true
                // Set Local Storage setelah 5 detik agar tidak muncul lagi
                setTimeout(() => {
                    localStorage.setItem('ad_shown', 'true')
                }, 5000) // Tampilkan pop-up selama 5 detik, lalu set cookie
            }
        },
    }"
    x-init="init()"
>
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

                            <div
                                class="p-4 lg:mx-65 rounded-lg blog-card cursor-pointer"
                            >
                                <img
                                    wire:click="recordClickAndRedirect({{ $ad->id }})"
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

                                <button
                                    wire:click="recordClickAndRedirect({{ $ad->id }})"
                                    class="inline-block px-6 py-3 my-4 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-full transition duration-300 animate-pulse hover:animate-none cursor-pointer"
                                >
                                    Order Sebelum Kehabisan
                                </button>
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
                            @if ($loop->index + 1 == 9)
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
                                            <h3
                                                class="text-xl font-bold text-sky-400 animate-pulse hover:animate-none mb-4 transition duration-300"
                                            >
                                                {{ $ad->product_name }}
                                            </h3>

                                            <button
                                                wire:click="recordClickAndRedirect({{ $ad->id }})"
                                                class="inline-block px-8 py-4 bg-orange-600 hover:bg-orange-700 text-white text-lg font-semibold rounded-lg transition duration-300 cursor-pointer"
                                            >
                                                Cek Promonya Sekarang!
                                            </button>
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

    {{-- Cek apakah ada iklan yang dipublikasikan dan tersedia --}}
    @if ($ad && $ad->is_published)
        <div
            x-data="{
                show: @entangle("showAdPopup"),
                delay: 4000, // Tentukan penundaan (misalnya 4000ms = 4 detik)
            }"
            x-show="show"
            {{-- INIT: Logika untuk menunda penampilan pop-up --}}
            x-init="
                // Set show ke false saat inisialisasi agar tersembunyi dulu
                show = false

                // Atur penundaan
                setTimeout(() => {
                    // Setelah penundaan (misalnya 4 detik), set show menjadi true
                    show = true
                }, delay)

                // Logika untuk menonaktifkan scrolling tubuh saat pop-up aktif
                $watch('show', (value) => {
                    if (value) {
                        document.body.style.overflow = 'hidden'
                    } else {
                        document.body.style.overflow = 'auto'
                    }
                })
            "
            class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4 transition-opacity duration-500"
            {{-- Durasi Transisi Latar Belakang --}}
            x-cloak
        >
            <div
                @click.away="show = false"
                class="bg-slate-800 rounded-lg shadow-2xl max-w-md w-full relative transform transition-all duration-500"
                {{-- Durasi Transisi Pop-up --}}
                {{-- ANIMASI TRANSISI (SCALE dan OPACITY) --}}
                x-transition:enter="ease-out duration-500"
                x-transition:enter-start="opacity-0 translate-y-full"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="ease-in duration-300"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-full"
            >
                {{-- Tombol Tutup dan Isi Iklan (tidak berubah) --}}
                <button
                    @click="show = false"
                    class="absolute top-2 right-2 text-white bg-red-600 hover:bg-red-700 p-1 rounded-full z-10"
                    aria-label="Tutup Iklan"
                >
                    <x-icon name="lucide.x" class="w-5 h-5" />
                </button>

                <div class="text-center p-6">
                    <p
                        class="text-xs font-semibold text-sky-400 mb-2 uppercase"
                    >
                        Iklan Khusus
                    </p>

                    {{-- GAMBAR IKLAN --}}
                    <img
                        wire:click="recordClickAndRedirect({{ $ad->id }})"
                        @click="show = false"
                        src="{{ $ad->image_path ?? "https://placehold.co/600x400/1E293B/FFFFFF?text=Iklan+Shopee" }}"
                        alt="{{ $ad->product_name }}"
                        class="w-full h-48 object-cover rounded-md cursor-pointer hover:opacity-90 transition-opacity mx-auto"
                    />

                    <h3 class="mt-4 text-xl font-bold text-white">
                        {{ $ad->product_name }}
                    </h3>

                    <p class="mt-2 text-slate-400 text-sm mb-4">
                        {{ Str::limit(strip_tags($ad->description), 80) }}
                    </p>

                    {{-- TOMBOL CTA --}}
                    <button
                        wire:click="recordClickAndRedirect({{ $ad->id }})"
                        @click="show = false"
                        class="inline-block px-8 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-full transition duration-300 animate-pulse hover:animate-none cursor-pointer w-full"
                    >
                        Lihat Promo Sekarang!
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
