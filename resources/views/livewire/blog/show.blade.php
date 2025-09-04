<?php

use App\Models\Post;
use Livewire\Volt\Component;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    public $post;
    public Collection $relatedPosts;

    public function mount(): void
    {
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
}; ?>

<div>
    <main>
        <article class="py-12 md:py-20">
            <div class="container mx-auto px-6">
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
                        {!! $this->post->content !!}
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
            <div class="container mx-auto px-6">
                <div class="max-w-3xl mx-auto">
                    <livewire:blog.comments :post="$post" lazy />
                </div>
            </div>
        </section>

        <!-- Related Posts Section -->
        @if ($this->relatedPosts->isNotEmpty())
            <section class="py-20">
                <div class="container mx-auto px-6">
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
    </main>
</div>
