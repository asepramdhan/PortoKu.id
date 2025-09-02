<?php

use App\Models\Post;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    use WithPagination;

    public $tag;

    public function mount(): void
    {
        $this->updateViewsCount();
    }

    private function updateViewsCount(): void
    {
        // --- TAMBAHKAN LOGIKA PENGHITUNG VIEW DI SINI ---
        $cacheKey = "viewed_tag_" . $this->tag->id . "_" . request()->ip();

        if (! Cache::has($cacheKey)) {
            // Jika IP ini belum melihat tag ini dalam 24 jam,
            // kita hitung sebagai view baru
            $this->tag->increment("views_count");

            // Lalu, simpan jejaknya ke cache selama 24 jam (1440 menit)
            Cache::put($cacheKey, true, now()->addDays(1));
        }
    }

    public function with(): array
    {
        $posts = Post::whereHas("tags", function ($query) {
            $query->where("id", $this->tag->id);
        })
            ->with("category", "user")
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
            <p class="font-semibold text-sky-400">Tag</p>
            <h1 class="mt-2 text-4xl md:text-5xl font-extrabold text-white">
                #{{ $tag->name }}
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
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-12">
                    {{ $posts->links("livewire.tailwind-custom") }}
                </div>
            @else
                <div class="text-center text-slate-400">
                    <p>Belum ada postingan dengan tag ini.</p>
                </div>
            @endif
        </div>
    </section>
</div>
