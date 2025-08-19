<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div>
  <main>
    <!-- ===== Page Header ===== -->
    <section class="py-20 text-center bg-slate-900">
      <div class="container mx-auto px-6">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white">Blog & Wawasan Keuangan</h1>
        <p class="mt-4 text-lg md:text-xl text-slate-400 max-w-3xl mx-auto">
          Temukan artikel terbaru seputar dunia kripto, strategi investasi, dan tips manajemen keuangan pribadi dari tim kami.
        </p>
      </div>
    </section>

    <!-- ===== Blog Grid Section ===== -->
    <section class="py-20">
      <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

          <!-- Blog Card 1 -->
          <a href="/blog/show" wire:navigate class="blog-card group">
            <img src="https://placehold.co/600x400/1E293B/FFFFFF?text=Analisis+Pasar" alt="Blog post image" class="w-full h-48 object-cover">
            <div class="p-6">
              <span class="text-sm font-semibold text-sky-400">BITCOIN</span>
              <h2 class="mt-2 text-xl font-bold text-white group-hover:text-sky-400 transition-colors">
                Analisis Pasar Bitcoin: Apa yang Diharapkan di Kuartal Berikutnya?
              </h2>
              <p class="mt-3 text-slate-400 text-sm">
                Tinjauan mendalam tentang tren harga, adopsi institusional, dan faktor makroekonomi yang akan mempengaruhi Bitcoin...
              </p>
              <p class="mt-4 text-xs text-slate-500">15 Agustus 2024</p>
            </div>
          </a>

          <!-- Blog Card 2 -->
          <a href="/blog/show" wire:navigate class="blog-card group">
            <img src="https://placehold.co/600x400/1E293B/FFFFFF?text=Tips+Keuangan" alt="Blog post image" class="w-full h-48 object-cover">
            <div class="p-6">
              <span class="text-sm font-semibold text-sky-400">KEUANGAN PRIBADI</span>
              <h2 class="mt-2 text-xl font-bold text-white group-hover:text-sky-400 transition-colors">
                5 Kesalahan Umum dalam Mengelola Keuangan yang Harus Dihindari
              </h2>
              <p class="mt-3 text-slate-400 text-sm">
                Dari penganggaran yang buruk hingga mengabaikan dana darurat, pelajari cara menghindari jebakan finansial yang umum...
              </p>
              <p class="mt-4 text-xs text-slate-500">10 Agustus 2024</p>
            </div>
          </a>

          <!-- Blog Card 3 -->
          <a href="/blog/show" wire:navigate class="blog-card group">
            <img src="https://placehold.co/600x400/1E293B/FFFFFF?text=Investasi+Crypto" alt="Blog post image" class="w-full h-48 object-cover">
            <div class="p-6">
              <span class="text-sm font-semibold text-sky-400">INVESTASI</span>
              <h2 class="mt-2 text-xl font-bold text-white group-hover:text-sky-400 transition-colors">
                Memahami Dollar Cost Averaging (DCA) untuk Investasi Kripto
              </h2>
              <p class="mt-3 text-slate-400 text-sm">
                Strategi sederhana ini dapat membantu mengurangi risiko volatilitas pasar dan membangun portofolio Anda secara konsisten...
              </p>
              <p class="mt-4 text-xs text-slate-500">5 Agustus 2024</p>
            </div>
          </a>

        </div>

        <!-- Pagination -->
        <div class="mt-12 flex justify-center">
          <nav class="flex items-center gap-2 text-sm">
            <a href="#" class="p-2 rounded hover:bg-slate-700 disabled:opacity-50 text-slate-400">
              <x-icon name="lucide.chevron-left" class="w-4 h-4" />
            </a>
            <a href="#" class="bg-slate-700 text-white font-semibold rounded px-3 py-1">1</a>
            <a href="#" class="text-slate-400 hover:bg-slate-700 rounded px-3 py-1">2</a>
            <a href="#" class="text-slate-400 hover:bg-slate-700 rounded px-3 py-1">3</a>
            <a href="#" class="p-2 rounded hover:bg-slate-700 text-slate-400">
              <x-icon name="lucide.chevron-right" class="w-4 h-4" />
            </a>
          </nav>
        </div>
      </div>
    </section>
  </main>
</div>
