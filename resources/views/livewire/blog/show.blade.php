<?php

use App\Models\Post;
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

    public function getProcessedContentProperty(): string
    {
        $content = $this->post->content;

        // Kode iklan AdSense Anda (bagian <ins> dan <script> kedua)
        $adCode = <<<'HTML'
                    <div class="my-8"> <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8728220555344178"
             crossorigin="anonymous"></script>
        <ins class="adsbygoogle"
             style="display:block; text-align:center;"
             data-ad-layout="in-article"
             data-ad-format="fluid"
             data-ad-client="ca-pub-8728220555344178"
             data-ad-slot="5273485627"></ins>
        <script>
             (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
        </div>
        HTML;

        try {
            // Jangan sisipkan iklan jika artikel terlalu pendek
            if (substr_count($content, "<p>") < 4) {
                return $content;
            }

            $crawler = new Crawler($content);

            // Cari semua paragraf. Kita akan sisipkan iklan setelah paragraf ke-3.
            $paragraphs = $crawler->filter("p");

            if ($paragraphs->count() >= 3) {
                $paragraphs
                    ->getNode(2)
                    ->insertAdjacentHTML("afterend", $adCode);
            }

            return $crawler->html();
        } catch (\Exception $e) {
            // Jika terjadi error saat parsing, kembalikan konten asli agar halaman tidak rusak
            return $content;
        }
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

                    <!-- Post Content -->
                    <div class="prose prose-lg prose-custom max-w-none">
                        {!! $this->processedContent !!}
                    </div>

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
                                            class="absolute top-2 right-2 text-xs text-slate-400 bg-slate-800 px-2 py-1 rounded-full opacity-70"
                                        >
                                            Masih dalam pengembangan
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
                                @if (! $app->is_demo)
                                    <a
                                        href="{{ $app->shopee_link }}"
                                        target="_blank"
                                        class="mt-6 inline-block bg-orange-500 hover:bg-orange-600 text-white font-bold px-6 py-3 rounded-lg transition-colors text-center"
                                    >
                                        <div
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <x-icon
                                                name="lucide.shopping-cart"
                                                class="w-5 h-5"
                                            />
                                            <span>Lihat di Shopee</span>
                                        </div>
                                    </a>
                                @endif

                                <a
                                    href="{{ $app->demo_link }}"
                                    target="_blank"
                                    class="mt-6 inline-block bg-sky-500 hover:bg-sky-600 text-white font-bold px-6 py-3 rounded-lg transition-colors text-center"
                                >
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <x-icon
                                            name="lucide.eye"
                                            class="w-5 h-5"
                                        />
                                        <span>Lihat Demo</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
</div>
