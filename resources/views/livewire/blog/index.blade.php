<?php

use App\Models\Post;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;

new class extends Component {
    use WithPagination; // Membuat pencarian muncul di URL

    #[Url(as: "q")]
    public string $search = "";

    public function with(): array
    {
        $query = Post::whereNotNull("published_at")
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

        return [
            "posts" => $query->paginate(6),
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
                    <div class="grid grid-cols-12 mb-12">
                        @foreach ($posts->random(1) as $post)
                            @if ($post->views_count > 50)
                                <div
                                    class="blog-card flex flex-col col-span-10 col-start-2"
                                >
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
                                            <span class="animate-pulse">
                                                🔥
                                            </span>
                                            {{ $post->title }}
                                        </h2>
                                        <p class="mt-3 text-slate-400 text-sm">
                                            {{ Str::limit(strip_tags($post->content), 120) }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        @endforeach
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
