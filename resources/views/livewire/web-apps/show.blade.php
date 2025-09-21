<?php

use Livewire\Volt\Component;

new class extends Component {
    public $webApp;

    public function mount(): void
    {
        // dd($this->webApp);
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
                    @if (! $this->webApp->is_demo)
                        <span>Production</span>
                    @else
                        <span class="text-red-400">Development</span>
                    @endif
                    <span>&bull;</span>
                    <time
                        datetime="{{ $this->webApp->created_at->toIso8601String() }}"
                    >
                        {{ $this->webApp->created_at->format("d M Y") }}
                    </time>
                </div>
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
                        href="{{ $this->webApp->shopee_link }}"
                        target="_blank"
                        class="mt-6 inline-block bg-orange-500 hover:bg-orange-600 text-white font-bold px-6 py-3 rounded-lg transition-colors text-center"
                    >
                        <div class="flex items-center justify-center gap-2">
                            <x-icon
                                name="lucide.shopping-cart"
                                class="w-5 h-5"
                            />
                            <span>Lihat di Shopee</span>
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
