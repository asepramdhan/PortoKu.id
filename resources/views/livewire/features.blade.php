<div>
    <!-- ===== Page Header ===== -->
    <section class="py-20 text-center">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white">
                Semua yang Anda Butuhkan
            </h1>
            <p class="mt-4 text-lg md:text-xl text-slate-400 max-w-3xl mx-auto">
                Jelajahi bagaimana PortoKu.id dapat menyederhanakan manajemen
                aset digital dan keuangan pribadi Anda dengan fitur-fitur
                canggih kami.
            </p>
        </div>
    </section>

    <!-- ===== Detailed Features Section ===== -->
    <section class="py-16 md:py-20">
        <div class="container mx-auto px-6 space-y-16 md:space-y-24">
            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div
                    class="bg-slate-800 p-4 rounded-xl shadow-lg md:order-last"
                >
                    <img
                        src="{{ asset("images/fitur-visualisasi.svg") }}"
                        alt="Visualisasi Portofolio"
                        class="rounded-lg w-full"
                    />
                </div>

                <div>
                    <div class="inline-flex items-center gap-3 mb-4">
                        <div class="feature-icon p-3 rounded-lg">
                            <x-icon
                                name="lucide.bitcoin"
                                class="w-7 h-7 text-sky-400"
                            />
                        </div>
                        <h2 class="text-2xl md:text-3xl font-bold text-white">
                            Pelacakan Portofolio Real-Time
                        </h2>
                    </div>
                    <p class="text-slate-300 text-lg mb-6">
                        Jangan pernah kehilangan momen di pasar yang fluktuatif.
                        Pantau nilai investasi Bitcoin Anda secara akurat dengan
                        data harga yang diperbarui secara real-time. Lihat
                        performa aset Anda dalam hitungan detik, kapan saja dan
                        di mana saja.
                    </p>
                    <ul class="space-y-3 text-slate-300">
                        <li class="flex items-start">
                            <x-icon
                                name="lucide.check-circle-2"
                                class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0"
                            />
                            <span>
                                Data harga terkini dari berbagai bursa
                                terkemuka.
                            </span>
                        </li>
                        <li class="flex items-start">
                            <x-icon
                                name="lucide.check-circle-2"
                                class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0"
                            />
                            <span>
                                Perhitungan laba/rugi yang otomatis dan akurat.
                            </span>
                        </li>
                        <li class="flex items-start">
                            <x-icon
                                name="lucide.check-circle-2"
                                class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0"
                            />
                            <span>
                                Tampilan ringkas untuk semua aset digital Anda.
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div class="bg-slate-800 p-4 rounded-xl shadow-lg">
                    <img
                        src="{{ asset("images/fitur-arus-kas.svg") }}"
                        alt="Manajemen Arus Kas"
                        class="rounded-lg w-full"
                    />
                </div>

                <div>
                    <div class="inline-flex items-center gap-3 mb-4">
                        <div class="feature-icon p-3 rounded-lg">
                            <x-icon
                                name="lucide.wallet"
                                class="w-7 h-7 text-sky-400"
                            />
                        </div>
                        <h2 class="text-2xl md:text-3xl font-bold text-white">
                            Manajemen Keuangan Terpusat
                        </h2>
                    </div>
                    <p class="text-slate-300 text-lg mb-6">
                        Lebih dari sekadar pelacak kripto. Catat semua pemasukan
                        dan pengeluaran harian Anda untuk mendapatkan gambaran
                        lengkap tentang kesehatan finansial Anda. Pahami ke mana
                        uang Anda pergi dan buat anggaran yang lebih baik.
                    </p>
                    <ul class="space-y-3 text-slate-300">
                        <li class="flex items-start">
                            <x-icon
                                name="lucide.check-circle-2"
                                class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0"
                            />
                            <span>
                                Pencatatan transaksi yang cepat dan mudah.
                            </span>
                        </li>
                        <li class="flex items-start">
                            <x-icon
                                name="lucide.check-circle-2"
                                class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0"
                            />
                            <span>
                                Kategorisasi pengeluaran untuk analisis
                                mendalam.
                            </span>
                        </li>
                        <li class="flex items-start">
                            <x-icon
                                name="lucide.check-circle-2"
                                class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0"
                            />
                            <span>
                                Lihat laporan arus kas bulanan dan tahunan.
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div
                    class="bg-slate-800 p-4 rounded-xl shadow-lg md:order-last"
                >
                    <img
                        src="{{ asset("images/fitur-laporan.svg") }}"
                        alt="Grafik dan Laporan"
                        class="rounded-lg w-full"
                    />
                </div>

                <div>
                    <div class="inline-flex items-center gap-3 mb-4">
                        <div class="feature-icon p-3 rounded-lg">
                            <x-icon
                                name="lucide.bar-chart-2"
                                class="w-7 h-7 text-sky-400"
                            />
                        </div>
                        <h2 class="text-2xl md:text-3xl font-bold text-white">
                            Laporan & Visualisasi Intuitif
                        </h2>
                    </div>
                    <p class="text-slate-300 text-lg mb-6">
                        Ubah data mentah menjadi wawasan berharga. Dengan grafik
                        dan diagram yang mudah dipahami, Anda dapat dengan cepat
                        menganalisis alokasi aset, tren pengeluaran, dan
                        performa investasi Anda dari waktu ke waktu.
                    </p>
                    <ul class="space-y-3 text-slate-300">
                        <li class="flex items-start">
                            <x-icon
                                name="lucide.check-circle-2"
                                class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0"
                            />
                            <span>
                                Grafik pertumbuhan portofolio yang dinamis.
                            </span>
                        </li>
                        <li class="flex items-start">
                            <x-icon
                                name="lucide.check-circle-2"
                                class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0"
                            />
                            <span>
                                Diagram lingkaran untuk alokasi aset dan
                                pengeluaran.
                            </span>
                        </li>
                        <li class="flex items-start">
                            <x-icon
                                name="lucide.check-circle-2"
                                class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0"
                            />
                            <span>
                                Unduh laporan dalam format PDF untuk catatan
                                pribadi.
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== Call to Action (CTA) Section ===== -->
    <section
        class="py-20 {{ auth()->check() && auth()->user()->is_admin ? "hidden" : "" }}"
    >
        <div class="container mx-auto px-6">
            <div
                class="bg-slate-800 rounded-2xl p-10 md:p-16 text-center shadow-2xl"
            >
                <h2 class="text-3xl md:text-4xl font-bold text-white">
                    Mulai Kelola Aset Anda Hari Ini
                </h2>
                <p class="text-slate-300 mt-4 mb-8 max-w-2xl mx-auto">
                    Daftar sekarang dan ambil langkah pertama menuju kebebasan
                    finansial dengan alat yang tepat di tangan Anda.
                </p>
                <a
                    href="{{ auth()->check() ? "/dashboard" : "/register" }}"
                    wire:navigate
                    class="bg-sky-500 hover:bg-sky-600 text-white font-bold py-4 px-10 rounded-lg text-xl transition-transform hover:scale-105 inline-block"
                >
                    {{ auth()->check() ? "Periksa Portofolio" : "Daftar Sekarang - Gratis!" }}
                </a>
            </div>
        </div>
    </section>
</div>
