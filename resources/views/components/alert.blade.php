@if (session()->has('message'))
<div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2" class="mb-6 p-4 rounded-lg bg-green-500/10 border border-green-500/30 text-green-300 flex justify-between items-center" role="alert">

  <div class="flex items-center">
    {{-- Ikon Sukses --}}
    <x-icon name="lucide.check-circle" class="w-6 h-6 mr-3 text-green-400" />
    {{-- Pesan dari session --}}
    <span class="font-semibold">{{ session('message') }}</span>
  </div>

  {{-- Tombol Tutup --}}
  <button @click="show = false" class="text-green-300/70 hover:text-white cursor-pointer">
    <x-icon name="lucide.x" class="w-5 h-5" />
  </button>
</div>
@endif
