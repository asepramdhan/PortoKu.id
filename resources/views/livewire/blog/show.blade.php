<?php

use App\Models\Post;
use App\Models\ShopeeAd;
use App\Models\WebApp;
use Livewire\Volt\Component;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\DomCrawler\Crawler;

new class extends Component {
    public $post, $webApps;
    public Collection $relatedPosts;

    public function mount(): void
    {
        $this->webApps = WebApp::all();
        $this->updateViewsCount();
        $this->relatedPosts = Post::where("id", "!=", $this->post->id)
            ->inRandomOrder()
            ->take(3)
            ->get();
    }

    private function updateViewsCount(): void
    {
        // 1. Logika untuk menghitung unique view berdasarkan IP dalam 24 jam
        $cacheKey = "viewed_post_" . $this->post->id . "_" . request()->ip();

        if (! Cache::has($cacheKey)) {
            // Buat catatan baru di tabel post_views
            $this->post->views()->create([
                "ip_address" => request()->ip(),
            ]);
            // Jika IP ini belum melihat post ini dalam 24 jam,
            // kita hitung sebagai view baru
            $this->post->increment("views_count");

            // Lalu, simpan jejaknya ke cache selama 24 jam (1440 menit)
            Cache::put($cacheKey, true, now()->addMinutes(1440));
        }
    }

    public function recordClickAndRedirect(ShopeeAd $ad): void
    {
        // Naikkan jumlah klik
        $ad->increment("clicks_count");

        $this->redirect($ad->ad_link);
    }

    public function with(): array
    {
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
            "ad" => $ad,
        ];
    }
}; ?>

<div>
    <main>
        <article class="py-12 md:py-20">
            <div class="container mx-auto px-2 lg:px-6">
                <div class="max-w-3xl mx-auto">
                    <!-- Post Header -->
                    <header class="text-center mb-12">
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

                        <h1
                            class="mt-2 text-3xl md:text-5xl font-extrabold text-white leading-tight"
                        >
                            {{ $this->post->title }}
                        </h1>

                        <div
                            class="mt-6 flex items-center justify-center space-x-4 text-slate-400"
                        >
                            <div class="flex items-center space-x-2">
                                <img
                                    src="{{ $this->post->user->profile_photo_path ? asset("storage/" . $this->post->user->profile_photo_path) : "https://placehold.co/40x40/0EA5E9/FFFFFF?text=" . substr($this->post->user->name, 0, 1) }}"
                                    alt="Author"
                                    class="w-8 h-8 rounded-full"
                                />
                                <span>
                                    {{ Str::title($this->post->user->name) }}
                                </span>
                            </div>
                            <span>&bull;</span>
                            <time
                                datetime="{{ $this->post->published_at->toIso8601String() }}"
                            >
                                {{ $this->post->published_at->format("d M Y") }}
                            </time>
                        </div>
                    </header>

                    <!-- Feature Image -->
                    @if ($post->featured_image_path)
                        <figure class="mb-12">
                            <img
                                src="{{ $this->post->featured_image_path }}"
                                alt="Gambar utama untuk {{ $this->post->title }}"
                                class="w-full h-auto rounded-xl object-cover"
                            />
                        </figure>
                    @endif

                    {{-- START: SLOT IKLAN AFFILIATE SHOPEE - POSISI 1 --}}
                    @if ($ad)
                        <div class="my-10 text-center">
                            <p class="text-lg font-bold text-sky-400 mb-4">
                                Produk Tranding di Shopee
                            </p>

                            {{-- GANTI DENGAN KODE IKLAN SHOPEE AFFILIATE ANDA --}}

                            <div
                                class="p-4 rounded-lg blog-card cursor-pointer"
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

                    <!-- Post Content -->
                    <div class="prose prose-lg prose-custom max-w-none">
                        {!! $this->post->content !!}
                    </div>

                    {{-- START: SLOT IKLAN AFFILIATE SHOPEE - POSISI 2 (Setelah Postingan ke-3) --}}
                    @if ($ad)
                        <div
                            class="blog-card flex flex-col md:col-span-2 lg:col-span-3 my-10"
                        >
                            {{-- Ini akan memakan lebar 3 kolom agar iklan banner lebih besar. --}}

                            <div
                                class="p-6 bg-yellow-900 rounded-lg shadow-xl text-center"
                            >
                                <p class="text-xl font-bold text-white mb-4">
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

                    {{-- END: SLOT IKLAN AFFILIATE SHOPEE - POSISI 2 --}}

                    <!-- Tags -->
                    @if ($post->tags->isNotEmpty())
                        <div class="mt-12 flex flex-wrap gap-2">
                            @foreach ($this->post->tags as $tag)
                                <a
                                    href="/blog/tag/{{ $tag->slug }}"
                                    wire:navigate
                                    class="bg-slate-800 text-sky-400 text-xs font-semibold px-3 py-1 rounded-full hover:bg-slate-700 transition-colors"
                                >
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <!-- Author Bio -->
                    <div
                        class="mt-16 pt-8 border-t border-slate-800 flex items-start space-x-6"
                    >
                        <img
                            src="{{ $this->post->user->profile_photo_path ? asset("storage/" . $this->post->user->profile_photo_path) : "https://placehold.co/80x80/0EA5E9/FFFFFF?text=" . substr($this->post->user->name, 0, 1) }}"
                            alt="Author"
                            class="w-16 h-16 rounded-full flex-shrink-0"
                        />
                        <div>
                            <h4 class="text-lg font-bold text-white">
                                Tentang
                                {{ Str::title($this->post->user->name) }}
                            </h4>
                            <p class="mt-1 text-slate-400">
                                Penulis di PortoKu.id yang berfokus pada
                                {{ $this->post->user->about ?? "N/A" }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <!-- KOMPONEN KOMENTAR DI SINI -->
        <section class="pb-12 md:pb-20">
            <div class="container mx-auto px-2 lg:px-6">
                <div class="max-w-3xl mx-auto">
                    <livewire:blog.comments :post="$post" lazy />
                </div>
            </div>
        </section>

        <!-- Related Posts Section -->
        @if ($this->relatedPosts->isNotEmpty())
            <section class="py-20">
                <div class="container mx-auto px-2 lg:px-6">
                    <h2 class="text-3xl font-bold text-center text-white mb-12">
                        Baca Juga
                    </h2>
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                    >
                        @foreach ($this->relatedPosts as $relatedPost)
                            <div class="blog-card flex flex-col">
                                <a
                                    href="/blog/show/{{ $relatedPost->slug }}"
                                    wire:navigate
                                    class="block"
                                >
                                    <img
                                        src="{{ $relatedPost->featured_image_path ?? "https://placehold.co/600x400/1E293B/FFFFFF?text=PortoKu.id" }}"
                                        alt="Gambar thumbnail untuk {{ $relatedPost->title }}"
                                        class="w-full h-48 object-cover"
                                    />
                                </a>
                                <div class="p-6 flex flex-col flex-grow">
                                    @if ($relatedPost->category)
                                        <a
                                            href="/blog/category/{{ $relatedPost->category->slug }}"
                                            wire:navigate
                                            class="text-sm font-semibold text-sky-400 uppercase hover:underline"
                                        >
                                            {{ $relatedPost->category->name }}
                                        </a>
                                    @endif

                                    <a
                                        href="/blog/show/{{ $relatedPost->slug }}"
                                        wire:navigate
                                        class="group"
                                    >
                                        <h2
                                            class="mt-2 text-xl font-bold text-white group-hover:text-sky-400 transition-colors"
                                        >
                                            @if ($relatedPost->views_count > 50)
                                                <span class="animate-pulse">
                                                    🔥
                                                </span>
                                            @endif

                                            {{ $relatedPost->title }}
                                        </h2>
                                    </a>
                                    <p
                                        class="mt-3 text-slate-400 text-sm flex-grow"
                                    >
                                        {{ Str::limit(strip_tags($relatedPost->content), 120) }}
                                    </p>
                                    <p class="mt-4 text-xs text-slate-500">
                                        {{ $relatedPost->published_at->format("d M Y") }}
                                    </p>
                                </div>
                            </div>

                            {{-- START: SLOT IKLAN AFFILIATE SHOPEE - POSISI 2 (Setelah Postingan ke-3) --}}
                            @if ($loop->index + 1 == 3)
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
                </div>
            </section>
        @endif

        <!-- Web Apps is prodution -->
        <section class="py-20">
            <div class="container mx-auto px-2 lg:px-6">
                <h2 class="text-3xl font-bold text-center text-white mb-12">
                    <x-icon
                        name="lucide.rocket"
                        class="w-6 h-6 text-orange-400 inline-block mr-1"
                    />
                    Aplikasi Web
                </h2>
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                >
                    @foreach ($this->webApps as $app)
                        <div class="card flex flex-col group overflow-hidden">
                            <a
                                href="/web-apps/show/{{ $app->slug }}"
                                wire:navigate
                                class="block"
                            >
                                <div class="relative">
                                    <img
                                        src="{{ $app->image_path ?? "https://placehold.co/600x400/1E293B/FFFFFF?text=" . urlencode($app->title) }}"
                                        alt="Gambar thumbnail untuk {{ $app->title }}"
                                        class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                    @if ($app->is_demo)
                                        <span
                                            class="absolute top-2 right-2 text-xs font-semibold text-red-400 bg-slate-700 px-2 py-1 rounded-full"
                                        >
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <x-icon
                                                    name="lucide.file-terminal"
                                                    class="w-4 h-4"
                                                />
                                                <span>Development</span>
                                            </div>
                                        </span>
                                    @endif
                                </div>
                            </a>
                            <div class="p-6 flex flex-col flex-grow">
                                @if ($app->tags)
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        @foreach ($app->tags as $tag)
                                            <span
                                                class="text-xs font-semibold text-sky-400 bg-sky-500/10 px-2 py-1 rounded-full"
                                            >
                                                {{ Str::title($tag) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <a
                                    href="/web-apps/show/{{ $app->slug }}"
                                    wire:navigate
                                    class="block"
                                >
                                    <h2
                                        class="mt-2 text-xl font-bold text-white group-hover:text-sky-400 transition-colors"
                                    >
                                        {{ $app->title }}
                                    </h2>
                                </a>
                                <p
                                    class="mt-3 text-slate-400 text-sm flex-grow"
                                >
                                    {{ Str::limit(strip_tags($app->description), 120) }}
                                </p>
                                <p class="mt-4 text-xs text-slate-500">
                                    {{ $app->created_at->format("d M Y") }} |
                                    Updated
                                    {{ $app->updated_at->format("d M Y H:i") }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
</div>
