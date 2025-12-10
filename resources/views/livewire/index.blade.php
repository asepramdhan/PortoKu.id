<?php

use App\Models\ShopeeAd;
use Livewire\Volt\Component;

new class extends Component {
    // Properti untuk mengontrol status pop-up
    public bool $showAdPopup = false;

    // Gunakan fungsi mount() untuk menampilkan pop-up saat pertama kali dimuat
    public function mount()
    {
        // Secara default, pop-up akan ditampilkan saat komponen dimuat
        // Di sini Anda bisa menambahkan logika pengecekan cookie
        // agar pop-up tidak muncul berulang kali (lihat poin 3)
        $this->showAdPopup = true;
    }

    public function recordClickAndHide(ShopeeAd $ad): void
    {
        // Naikkan hitungan klik
        $ad->increment("clicks_count");

        // 2. Gunakan $this->js() untuk menjalankan JavaScript di browser
        //    window.open(URL, '_blank') akan membuka link di tab baru, akan tetapi tabnya tidak berpindah tetap di halam blog sama, sehingga user tidak pindah ke tab iklan
        $this->js('window.open("' . $ad->ad_link . '", "_blank");');

        // Sembunyikan pop-up
        $this->showAdPopup = false;
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

<div
    x-data="{
        init() {
            // Cek apakah cookie 'ad_shown' sudah ada
            if (localStorage.getItem('ad_shown') !== 'true') {
                this.$wire.showAdPopup = true
                // Set Local Storage setelah 5 detik agar tidak muncul lagi
                setTimeout(() => {
                    localStorage.setItem('ad_shown', 'true')
                }, 5000) // Tampilkan pop-up selama 5 detik, lalu set cookie
            }
        },
    }"
    x-init="init()"
>
    <!-- ===== Hero Section ===== -->
    <section
        class="relative text-center py-20 md:py-32 overflow-hidden hero-gradient"
    >
        <div class="container mx-auto px-2 lg:px-6 z-10 relative">
            <div class="max-w-3xl mx-auto">
                <span
                    class="bg-sky-500/10 text-sky-400 border border-sky-500/30 rounded-full px-4 py-1 text-sm font-semibold"
                >
                    Solusi Cerdas Keuangan Anda
                </span>
                <h1
                    class="text-3xl md:text-6xl font-extrabold text-white mt-6 mb-4 leading-tight"
                >
                    Visualisasikan
                    <span class="text-sky-400">Portofolio Bitcoin</span>
                    & Keuangan Pribadi
                </h1>
                <p
                    class="text-lg md:text-xl text-slate-300 max-w-2xl mx-auto mb-8"
                >
                    Alat bantu modern untuk mencatat, menganalisis, dan
                    mengoptimalkan aset digital serta keuangan Anda di satu
                    tempat yang aman.
                </p>
                <div
                    class="flex justify-center items-center gap-4 {{ auth()->check() && auth()->user()->is_admin ? "hidden" : "" }}"
                >
                    @guest
                        <a
                            href="/register"
                            wire:navigate
                            class="bg-sky-500 hover:bg-sky-600 text-white font-bold py-3 px-8 rounded-lg text-lg transition-transform hover:scale-105"
                        >
                            Mulai Sekarang!
                        </a>
                        <a
                            href="/features"
                            wire:navigate
                            class="hidden md:block bg-slate-700 hover:bg-slate-600 text-white font-bold py-3 px-8 rounded-lg text-lg transition-transform hover:scale-105"
                        >
                            Lihat Fitur
                        </a>
                    @else
                        <a
                            href="/dashboard"
                            wire:navigate
                            class="bg-sky-500 hover:bg-sky-600 text-white font-bold py-3 px-8 rounded-lg text-lg transition-transform hover:scale-105"
                        >
                            Periksa Portofolio
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    <!-- ===== Fitur Utama Section ===== -->
    <section id="fitur" class="py-20">
        <div class="container mx-auto px-2 lg:px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-white">
                    Fitur Unggulan PortoKu.id
                </h2>
                <p class="text-slate-400 mt-2 max-w-2xl mx-auto">
                    Semua yang Anda butuhkan untuk manajemen aset yang lebih
                    baik.
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature Card 1 -->
                <div class="feature-card rounded-xl p-8 text-center">
                    <div
                        class="bg-sky-500/10 inline-flex p-4 rounded-full mb-4"
                    >
                        <x-icon
                            name="lucide.bitcoin"
                            class="w-8 h-8 text-sky-400"
                        />
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">
                        Pelacakan Portofolio Bitcoin
                    </h3>
                    <p class="text-slate-400">
                        Pantau nilai investasi Bitcoin Anda secara real-time
                        dengan data pasar terkini.
                    </p>
                </div>
                <!-- Feature Card 2 -->
                <div class="feature-card rounded-xl p-8 text-center">
                    <div
                        class="bg-sky-500/10 inline-flex p-4 rounded-full mb-4"
                    >
                        <x-icon
                            name="lucide.wallet"
                            class="w-8 h-8 text-sky-400"
                        />
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">
                        Manajemen Keuangan Pribadi
                    </h3>
                    <p class="text-slate-400">
                        Catat pemasukan dan pengeluaran harian Anda untuk
                        mendapatkan gambaran finansial yang utuh.
                    </p>
                </div>
                <!-- Feature Card 3 -->
                <div class="feature-card rounded-xl p-8 text-center">
                    <div
                        class="bg-sky-500/10 inline-flex p-4 rounded-full mb-4"
                    >
                        <x-icon
                            name="lucide.pie-chart"
                            class="w-8 h-8 text-sky-400"
                        />
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">
                        Visualisasi Data Intuitif
                    </h3>
                    <p class="text-slate-400">
                        Grafik dan laporan yang mudah dibaca untuk membantu Anda
                        memahami alokasi aset dan arus kas.
                    </p>
                </div>
                <!-- Feature Card 4 -->
                <div class="feature-card rounded-xl p-8 text-center">
                    <div
                        class="bg-sky-500/10 inline-flex p-4 rounded-full mb-4"
                    >
                        <x-icon
                            name="lucide.shield-check"
                            class="w-8 h-8 text-sky-400"
                        />
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">
                        Keamanan Terjamin
                    </h3>
                    <p class="text-slate-400">
                        Data Anda dienkripsi dan disimpan dengan standar
                        keamanan tertinggi. Privasi Anda adalah prioritas kami.
                    </p>
                </div>
                <!-- Feature Card 5 -->
                <div class="feature-card rounded-xl p-8 text-center">
                    <div
                        class="bg-sky-500/10 inline-flex p-4 rounded-full mb-4"
                    >
                        <x-icon
                            name="lucide.smartphone"
                            class="w-8 h-8 text-sky-400"
                        />
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">
                        Akses Multi-Platform
                    </h3>
                    <p class="text-slate-400">
                        Gunakan di perangkat apa pun, baik desktop maupun
                        mobile, dengan tampilan yang responsif.
                    </p>
                </div>
                <!-- Feature Card 6 -->
                <div class="feature-card rounded-xl p-8 text-center">
                    <div
                        class="bg-sky-500/10 inline-flex p-4 rounded-full mb-4"
                    >
                        <x-icon
                            name="lucide.bell-ring"
                            class="w-8 h-8 text-sky-400"
                        />
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">
                        Notifikasi Cerdas
                    </h3>
                    <p class="text-slate-400">
                        Dapatkan pemberitahuan penting tentang perubahan
                        signifikan pada portofolio Anda.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== Cara Kerja Section ===== -->
    <section id="cara-kerja" class="py-20">
        <div class="container mx-auto px-2 lg:px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-white">
                    Hanya 3 Langkah Mudah
                </h2>
                <p class="text-slate-400 mt-2 max-w-2xl mx-auto">
                    Mulai kelola aset Anda dalam hitungan menit.
                </p>
            </div>
            <div
                class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12 text-center"
            >
                <!-- Step 1 -->
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <div
                            class="flex items-center justify-center w-20 h-20 rounded-full bg-slate-800 border-2 border-sky-500 text-sky-400 text-3xl font-bold"
                        >
                            1
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-white mt-6 mb-2">
                        Buat Akun Gratis
                    </h3>
                    <p class="text-slate-400">
                        Daftarkan diri Anda dengan cepat tanpa biaya apapun.
                    </p>
                </div>
                <!-- Step 2 -->
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <div
                            class="flex items-center justify-center w-20 h-20 rounded-full bg-slate-800 border-2 border-sky-500 text-sky-400 text-3xl font-bold"
                        >
                            2
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-white mt-6 mb-2">
                        Hubungkan & Catat Aset
                    </h3>
                    <p class="text-slate-400">
                        Masukkan data transaksi Bitcoin dan catatan keuangan
                        Anda.
                    </p>
                </div>
                <!-- Step 3 -->
                <div class="flex flex-col items-center">
                    <div
                        class="flex items-center justify-center w-20 h-20 rounded-full bg-slate-800 border-2 border-sky-500 text-sky-400 text-3xl font-bold"
                    >
                        3
                    </div>
                    <h3 class="text-xl font-bold text-white mt-6 mb-2">
                        Pantau & Analisis
                    </h3>
                    <p class="text-slate-400">
                        Nikmati visualisasi data dan mulailah membuat keputusan
                        cerdas.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== Call to Action (CTA) Section ===== -->
    <section
        class="py-20 {{ auth()->check() && auth()->user()->is_admin ? "hidden" : "" }}"
    >
        <div class="container mx-auto px-2 lg:px-6">
            <div
                class="bg-slate-800 rounded-2xl p-10 md:p-16 text-center shadow-2xl"
            >
                <h2 class="text-xl lg:text-4xl font-bold text-white">
                    Siap Mengambil Kendali Penuh Atas Keuangan Anda?
                </h2>
                <p class="text-slate-300 mt-4 mb-8 max-w-2xl mx-auto">
                    Jangan tunda lagi. Bergabunglah dengan ribuan pengguna lain
                    yang telah merasakan manfaatnya.
                </p>
                <a
                    href="{{ auth()->check() ? "/dashboard" : "/register" }}"
                    wire:navigate
                    class="bg-sky-500 hover:bg-sky-600 text-white font-bold py-4 px-10 rounded-lg text-lg lg:text-xl transition-transform hover:scale-105 inline-block"
                >
                    {!! auth()->check() ? "Periksa Portofolio" : "Daftar Sekarang <span class='hidden lg:inline-block'>- Gratis!</span>" !!}
                </a>
            </div>
        </div>
    </section>
    {{-- Cek apakah ada iklan yang dipublikasikan dan tersedia --}}
    @if ($ad && $ad->is_published)
        <div
            wire:click="recordClickAndHide({{ $ad->id }})"
            x-data="{
                show: @entangle("showAdPopup"),
                delay: 4000, // Tentukan penundaan (misalnya 4000ms = 4 detik)
            }"
            x-show="show"
            {{-- INIT: Logika untuk menunda penampilan pop-up --}}
            x-init="
                // Set show ke false saat inisialisasi agar tersembunyi dulu
                show = false

                // Atur penundaan
                setTimeout(() => {
                    // Setelah penundaan (misalnya 4 detik), set show menjadi true
                    show = true
                }, delay)

                // Logika untuk menonaktifkan scrolling tubuh saat pop-up aktif
                $watch('show', (value) => {
                    if (value) {
                        document.body.style.overflow = 'hidden'
                    } else {
                        document.body.style.overflow = 'auto'
                    }
                })
            "
            class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4 transition-opacity duration-500"
            {{-- Durasi Transisi Latar Belakang --}}
            x-cloak
        >
            <div
                wire:click="recordClickAndHide({{ $ad->id }})"
                @click.away="show = false"
                class="bg-slate-800 rounded-lg shadow-2xl max-w-md w-full relative transform transition-all duration-500"
                {{-- Durasi Transisi Pop-up --}}
                {{-- ANIMASI TRANSISI (SCALE dan OPACITY) --}}
                x-transition:enter="ease-out duration-500"
                x-transition:enter-start="opacity-0 translate-y-full"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="ease-in duration-300"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-full"
            >
                {{-- Tombol Tutup dan Isi Iklan (tidak berubah) --}}
                <button
                    wire:click="recordClickAndHide({{ $ad->id }})"
                    @click="show = false"
                    class="absolute top-2 right-2 text-slate-400 hover:text-white z-10 cursor-pointer"
                    aria-label="Tutup Iklan"
                >
                    <x-icon name="lucide.x" class="w-5 h-5" />
                </button>

                <div class="text-center p-6">
                    <p
                        class="text-xs font-semibold text-sky-400 mb-2 uppercase"
                    >
                        Iklan Khusus
                    </p>

                    {{-- GAMBAR IKLAN --}}
                    <img
                        wire:click="recordClickAndRedirect({{ $ad->id }})"
                        @click="show = false"
                        src="{{ $ad->image_path ?? "https://placehold.co/600x400/1E293B/FFFFFF?text=Iklan+Shopee" }}"
                        alt="{{ $ad->product_name }}"
                        class="w-full h-auto object-cover rounded-md cursor-pointer hover:opacity-90 transition-opacity mx-auto"
                    />

                    <h3 class="mt-4 text-xl font-bold text-white">
                        {{ $ad->product_name }}
                    </h3>

                    <p class="mt-2 text-slate-400 text-sm mb-4">
                        {{ Str::limit(strip_tags($ad->description), 80) }}
                    </p>

                    {{-- TOMBOL CTA --}}
                    <button
                        wire:click="recordClickAndRedirect({{ $ad->id }})"
                        @click="show = false"
                        class="inline-block px-8 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-full transition duration-300 animate-pulse hover:animate-none cursor-pointer w-full"
                    >
                        Lihat Promo Sekarang!
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
