<?php

use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    use WithPagination;

    public $category;

    public function mount(): void
    {
        $this->updateViewsCount();
    }

    private function updateViewsCount(): void
    {
        // --- TAMBAHKAN LOGIKA PENGHITUNG VIEW DI SINI ---
        $cacheKey =
            "viewed_category_" . $this->category->id . "_" . request()->ip();

        if (! Cache::has($cacheKey)) {
            // Jika IP ini belum melihat kategori ini dalam 24 jam,
            // kita hitung sebagai view baru
            $this->category->increment("views_count");

            // Lalu, simpan jejaknya ke cache selama 24 jam (1440 menit)
            Cache::put($cacheKey, true, now()->addDays(1));
        }
    }

    public function with(): array
    {
        $posts = $this->category
            ->posts()
            ->whereNotNull("published_at")
            ->where("published_at", "<=", now())
            ->orderBy("published_at", "desc")
            ->paginate(6);

        return [
            "posts" => $posts,
        ];
    }
}; ?>

<div>
    <!-- Page Header -->
    <section class="py-20 text-center">
        <div class="container mx-auto px-6">
            <p class="font-semibold text-sky-400">Kategori</p>
            <h1 class="mt-2 text-4xl md:text-5xl font-extrabold text-white">
                {{ $category->name }}
            </h1>
        </div>
    </section>

    <!-- Blog Grid Section -->
    <section class="py-20">
        <div class="container mx-auto px-6">
            @if ($posts->count() > 0)
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                >
                    @foreach ($posts as $post)
                        <a
                            href="/blog/show/{{ $post->slug }}"
                            wire:navigate
                            class="blog-card group"
                        >
                            <img
                                src="{{ $post->featured_image_path ?? "https://placehold.co/600x400/1E293B/FFFFFF?text=PortoKu.id" }}"
                                alt="Gambar thumbnail untuk {{ $post->title }}"
                                class="w-full h-48 object-cover"
                            />
                            <div class="p-6">
                                <span
                                    class="text-sm font-semibold text-sky-400 uppercase"
                                >
                                    {{ $post->category->name ?? "Tanpa Kategori" }}
                                </span>
                                <h2
                                    class="mt-2 text-xl font-bold text-white group-hover:text-sky-400 transition-colors"
                                >
                                    {{ $post->title }}
                                </h2>
                                <p class="mt-3 text-slate-400 text-sm">
                                    {{ Str::limit(strip_tags($post->content), 120) }}
                                </p>
                                <p class="mt-4 text-xs text-slate-500">
                                    {{ $post->published_at->format("d M Y") }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-12">
                    {{ $posts->links("livewire.tailwind-custom") }}
                </div>
            @else
                <div class="text-center text-slate-400">
                    <p>Belum ada postingan dalam kategori ini.</p>
                </div>
            @endif
        </div>
    </section>
</div>
