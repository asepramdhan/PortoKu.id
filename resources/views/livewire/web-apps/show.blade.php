<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\WebApp;

new class extends Component {
    public $webApp;
    public string $whatsappUrl = ""; // <-- Properti untuk menampung URL WhatsApp

    public function with(): array
    {
        // Ambil satu nomor Admin secara acak dari database
        $admin = User::where("is_admin", 1)
            ->inRandomOrder()
            ->first();
        // Default jika tidak ada nomor Admin di database
        $phoneNumber = "6285117688832"; // Ganti dengan nomor default Anda
        if ($admin->phone_number) {
            // Format nomor: ganti '0' di depan dengan '62'
            $phoneNumber = "62" . substr($admin->phone_number, 1);
        }
        // Buat pesan default yang akan diisi otomatis di WhatsApp
        $message = urlencode(
            "Assalamualaikum, Kak. Saya tertarik dengan aplikasi '{$this->webApp->title}'. Apakah masih tersedia?",
        );

        // Buat URL WhatsApp
        $this->whatsappUrl = "https://wa.me/{$phoneNumber}?text={$message}";

        // menampilkan semua data web app dan skip web app ini, dan tampilkan 3 web app terkait secara acak
        $relatedWebApps = WebApp::where("id", "!=", $this->webApp->id)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        return [
            "whatsappUrl" => $this->whatsappUrl,
            "relatedWebApps" => $relatedWebApps,
        ];
    }
}; ?>

<div class="py-12 md:py-20">
    <div class="container mx-auto px-2 lg:px-6">
        <div class="max-w-3xl mx-auto">
            <!-- Post Header -->
            <header class="text-center mb-12">
                <h1
                    class="mt-2 text-3xl md:text-5xl font-extrabold text-white leading-tight"
                >
                    {{ $this->webApp->title }}
                </h1>
                <div
                    class="mt-6 flex items-center justify-center space-x-4 text-slate-400"
                >
                    <span class="hidden md:inline">
                        @if (! $this->webApp->is_demo)
                            <span class="text-green-400">
                                <x-icon name="lucide.check" class="w-6 h-6" />
                                Production
                            </span>
                        @else
                            <span class="text-red-400">
                                <x-icon name="lucide.x" class="w-6 h-6" />
                                Development
                            </span>
                        @endif
                    </span>
                    <span class="hidden md:inline">&bull;</span>
                    <time
                        datetime="{{ $this->webApp->created_at->toIso8601String() }}"
                    >
                        <span class="hidden md:inline">
                            {{ $this->webApp->created_at->format("d M Y") }} |
                        </span>
                        Updated
                        {{ $this->webApp->updated_at->format("d M Y H:i") }}
                    </time>
                </div>
                <span class="lg:hidden">
                    @if (! $this->webApp->is_demo)
                        <span class="text-green-400">
                            <x-icon name="lucide.check" class="w-6 h-6" />
                            Production
                        </span>
                    @else
                        <span class="text-red-400">
                            <x-icon name="lucide.x" class="w-6 h-6" />
                            Development
                        </span>
                    @endif
                </span>
            </header>

            <!-- Feature Image -->
            @if ($this->webApp->image_path)
                <figure class="mb-12">
                    <img
                        src="{{ $this->webApp->image_path }}"
                        alt="Gambar utama untuk {{ $this->webApp->title }}"
                        class="w-full h-auto rounded-xl object-cover"
                    />
                </figure>
            @endif

            <!-- Post Content -->
            <div class="prose prose-lg prose-custom max-w-none">
                {!! $this->webApp->description !!}
            </div>

            <!-- Tombol Demo dan Shopee -->
            <div
                class="lg:flex lg:items-center lg:justify-center text-center gap-4 mt-8 lg:mt-12"
            >
                <a
                    href="{{ $this->webApp->demo_link }}"
                    target="_blank"
                    class="mt-6 inline-block bg-sky-500 hover:bg-sky-600 text-white font-bold px-6 py-3 rounded-lg transition-colors text-center"
                >
                    <div class="flex items-center justify-center gap-2">
                        <x-icon name="lucide.eye" class="w-5 h-5" />
                        <span>Lihat Demo</span>
                    </div>
                </a>
                @if (! $this->webApp->is_demo)
                    <a
                        href="{{ $whatsappUrl }}"
                        target="_blank"
                        class="mt-6 inline-block bg-green-500 hover:bg-green-600 text-white font-bold px-6 py-3 rounded-lg transition-colors text-center"
                    >
                        <div class="flex items-center justify-center gap-2">
                            <x-icon
                                name="lucide.message-circle"
                                class="w-5 h-5"
                            />
                            <span>Chat Admin</span>
                        </div>
                    </a>
                @endif
            </div>
        </div>

        {{-- pembatas --}}
        <div class="mt-12 md:mt-20">
            <hr class="border-slate-600 border-dashed" />
        </div>

        {{-- releted web apps --}}
        <div>
            <h2
                class="text-xl font-bold text-center text-slate-400 mt-12 md:mt-20 mb-8"
            >
                Aplikasi Web Lainnya
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($relatedWebApps as $app)
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
                            <p class="mt-3 text-slate-400 text-sm flex-grow">
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
    </div>
</div>
