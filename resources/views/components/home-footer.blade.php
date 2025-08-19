<footer class="bg-slate-900 border-t border-slate-800 pb-12">
  <div class="container mx-auto px-6 py-8 text-center text-slate-400">
    <p>&copy; {{ now()->year }} PortoKu.id. Semua Hak Cipta Dilindungi.</p>
    <div class="flex justify-center space-x-6 mt-4">
      <a href="/terms" wire:navigate class="{{ request()->is('terms') ? 'text-sky-400 font-semibold' : 'hover:text-sky-400' }}">Ketentuan Layanan</a>
      <a href="/privacy-policy" wire:navigate class="{{ request()->is('privacy-policy') ? 'text-sky-400 font-semibold' : 'hover:text-sky-400' }}">Kebijakan Privasi</a>
    </div>
  </div>
</footer>
