<footer class="bg-slate-900 border-t border-slate-800">
    <div class="container mx-auto px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Kolom 1: Tentang PortoKu.id --}}
            <div>
                <a href="/" wire:navigate class="text-2xl font-bold text-white">
                    <div class="flex items-end">
                        <img
                            src="{{ asset("images/logo.svg") }}"
                            alt="PortoKu.id Logo"
                            class="h-10 w-auto"
                            style="margin-bottom: 5px"
                        />
                        <span class="ms-2">
                            {!! 'Porto<span class="text-sky-400">Ku</span>.id' !!}
                        </span>
                    </div>
                </a>
                <p class="mt-4 text-slate-400 max-w-xs">
                    Alat bantu modern untuk mencatat, menganalisis, dan
                    mengoptimalkan aset digital serta keuangan Anda di satu
                    tempat yang aman.
                </p>
                <p class="mt-6 flex gap-6">
                    <!-- Social Media Icons -->
                    <a
                        href="https://tiktok.com/@portoku.id"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-slate-400 hover:text-sky-400 transition-colors"
                    >
                        <x-icon name="fab.tiktok" class="w-6 h-6" />
                    </a>
                    <a
                        href="https://instagram.com/portoku.id"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-slate-400 hover:text-sky-400 transition-colors"
                    >
                        <x-icon name="fab.instagram" class="w-6 h-6" />
                    </a>
                    <a
                        href="https://facebook.com/portoku.id"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-slate-400 hover:text-sky-400 transition-colors"
                    >
                        <x-icon name="fab.facebook" class="w-6 h-6" />
                    </a>
                    <a
                        href="https://wa.me/6285117688832"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-slate-400 hover:text-sky-400 transition-colors"
                    >
                        <x-icon name="fab.whatsapp" class="w-6 h-6" />
                    </a>
                </p>
            </div>

            {{-- Kolom 2: Link Navigasi --}}
            <div>
                <h3 class="text-lg font-semibold text-white">Navigasi</h3>
                <nav class="mt-4 space-y-2">
                    <a
                        href="/features"
                        wire:navigate
                        class="block text-slate-400 transition-colors {{ request()->is("features") ? "font-semibold" : "hover:text-sky-400" }}"
                    >
                        Fitur
                    </a>
                    <a
                        href="/workflow"
                        wire:navigate
                        class="block text-slate-400 transition-colors {{ request()->is("workflow") ? "font-semibold" : "hover:text-sky-400" }}"
                    >
                        Cara Kerja
                    </a>
                    <a
                        href="/web-apps"
                        wire:navigate
                        class="block text-slate-400 transition-colors {{ request()->is("web-apps*") ? "font-semibold" : "hover:text-sky-400" }}"
                    >
                        Aplikasi Web
                        <span
                            class="text-xs font-bold text-amber-400 bg-amber-900 px-1.5 py-0.5 rounded-full"
                        >
                            Pro
                        </span>
                    </a>
                    <a
                        href="/blog"
                        wire:navigate
                        class="block text-slate-400 transition-colors {{ request()->is("blog*") ? "font-semibold" : "hover:text-sky-400" }}"
                    >
                        Blog
                    </a>
                </nav>
            </div>

            {{-- Kolom 3: Informasi & Legal --}}
            <div>
                <h3 class="text-lg font-semibold text-white">Informasi</h3>
                <nav class="mt-4 space-y-2">
                    {{-- INI LINK PENTING UNTUK ADSENSE --}}
                    <a
                        href="/about"
                        wire:navigate
                        class="block text-slate-400 transition-colors {{ request()->is("about") ? "font-semibold" : "hover:text-sky-400" }}"
                    >
                        Tentang Kami
                    </a>
                    <a
                        href="/contact"
                        wire:navigate
                        class="block text-slate-400 transition-colors {{ request()->is("contact") ? "font-semibold" : "hover:text-sky-400" }}"
                    >
                        Kontak
                    </a>
                    <a
                        href="/privacy-policy"
                        wire:navigate
                        class="block text-slate-400 transition-colors {{ request()->is("privacy-policy") ? "font-semibold" : "hover:text-sky-400" }}"
                    >
                        Kebijakan Privasi
                    </a>
                    <a
                        href="/terms"
                        wire:navigate
                        class="block text-slate-400 transition-colors {{ request()->is("terms") ? "font-semibold" : "hover:text-sky-400" }}"
                    >
                        Ketentuan Layanan
                    </a>
                </nav>
            </div>
        </div>

        <div
            class="mt-12 border-t border-slate-800 pt-8 text-center text-slate-500"
        >
            <p>
                &copy; {{ date("Y") }} PortoKu.id. Semua Hak Cipta Dilindungi.
            </p>
        </div>
    </div>
</footer>
