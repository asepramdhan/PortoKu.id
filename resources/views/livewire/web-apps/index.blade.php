<?php

use App\Models\WebApp;
use Livewire\Volt\Component;

new class extends Component {
    public $webApps;

    public function mount(): void
    {
        $this->webApps = WebApp::all();
    }

    public function with(): array
    {
        return [
            "webApps" => $this->webApps,
        ];
    }
}; ?>

<div>
    <!-- Page Header -->
    <section class="py-20 text-center">
        <div class="container mx-auto px-2 lg:px-6">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white">
                Galeri Aplikasi Web
            </h1>
            <p class="mt-4 text-lg md:text-xl text-slate-400 max-w-3xl mx-auto">
                Temukan koleksi aplikasi web siap pakai yang dibangun dengan
                teknologi modern. Solusi instan untuk kebutuhan digital Anda
                atau bisnis Anda. Aplikasi web yang tersedia eksklusif di
                Shopee.
            </p>
        </div>
    </section>

    <!-- Apps Grid Section -->
    <section class="py-20">
        <div class="container mx-auto px-2 lg:px-6">
            @if ($webApps->isNotEmpty())
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                >
                    @foreach ($webApps as $app)
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
            @else
                <div class="text-center text-slate-400">
                    <p>
                        Belum ada aplikasi web yang tersedia saat ini. Silakan
                        cek kembali nanti!
                    </p>
                </div>
            @endif
        </div>
    </section>
</div>
