<header class="bg-slate-900/80 backdrop-blur-sm sticky top-0 z-50 border-b border-slate-800">
  <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
    <a href="/" wire:navigate class="text-2xl font-bold text-white">
      Porto<span class="text-sky-400">Ku</span>.id
    </a>

    <!-- Desktop Menu -->
    <div class="hidden md:flex items-center space-x-6">
      <a href="/features" wire:navigate class="{{ request()->is('features') ? 'text-sky-400 font-semibold' : 'text-slate-300 hover:text-sky-400 transition-colors' }}">Fitur</a>
      <a href="/workflow" wire:navigate class="{{ request()->is('workflow') ? 'text-sky-400 font-semibold' : 'text-slate-300 hover:text-sky-400 transition-colors' }}">Cara Kerja</a>
      <a href="/blog" wire:navigate class="{{ request()->is('blog*') ? 'text-sky-400 font-semibold' : 'text-slate-300 hover:text-sky-400 transition-colors' }}">Blog</a>
      @guest
      <a href="/login" wire:navigate class="{{ request()->is('login') ? 'text-sky-400 font-semibold' : 'text-slate-300 hover:text-sky-400 transition-colors' }}">Login</a>
      @endguest
    </div>
    @guest
    <a href="/register" wire:navigate class="hidden md:block bg-sky-500 hover:bg-sky-600 text-white font-semibold px-5 py-2 rounded-lg transition-colors">
      Daftar Gratis
    </a>
    @else
    <a href="/dashboard" wire:navigate class="hidden md:block bg-sky-500 hover:bg-sky-600 text-white font-semibold px-5 py-2 rounded-lg transition-colors">
      Portofolio
    </a>
    @endguest

    <!-- Mobile Menu Button -->
    <button @click="isMenuOpen = !isMenuOpen" class="md:hidden text-white z-50 cursor-pointer">
      <x-icon name="lucide.menu" x-show="!isMenuOpen" class="w-6 h-6" />
      <x-icon name="lucide.x" x-show="isMenuOpen" x-cloak class="w-6 h-6" />
    </button>
  </nav>

  <!-- Mobile Menu -->
  <div x-show="isMenuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2" @click.away="isMenuOpen = false" class="md:hidden absolute top-full left-0 w-full bg-slate-900 border-b border-slate-800" x-cloak>
    <div class="px-4 pt-2 pb-4 space-y-2">
      <a href="/features" wire:navigate @click="isMenuOpen = false" class="block {{ request()->is('features') ? 'text-sky-400 rounded-md px-3 py-2 font-semibold' : 'text-slate-300 hover:text-sky-400 rounded-md px-3 py-2 font-medium' }}">Fitur</a>
      <a href="/workflow" wire:navigate @click="isMenuOpen = false" class="block {{ request()->is('workflow') ? 'text-sky-400 rounded-md px-3 py-2 font-semibold' : 'text-slate-300 hover:text-sky-400 rounded-md px-3 py-2 font-medium' }}">Cara Kerja</a>
      <a href="/blog" wire:navigate @click="isMenuOpen = false" class="block {{ request()->is('blog*') ? 'text-sky-400 rounded-md px-3 py-2 font-semibold' : 'text-slate-300 hover:text-sky-400 rounded-md px-3 py-2 font-medium' }}">Blog</a>
      @guest
      <a href="/login" wire:navigate @click="isMenuOpen = false" class="block {{ request()->is('login') ? 'text-sky-400 rounded-md px-3 py-2 font-semibold' : 'text-slate-300 hover:text-sky-400 rounded-md px-3 py-2 font-medium' }}">Login</a>
      <a href="/register" wire:navigate class="block bg-sky-500 hover:bg-sky-600 text-white font-semibold w-full text-center mt-4 px-5 py-2 rounded-lg transition-colors">
        Daftar Gratis
      </a>
      @else
      <a href="/dashboard" wire:navigate class="block bg-sky-500 hover:bg-sky-600 text-white font-semibold w-full text-center mt-4 px-5 py-2 rounded-lg transition-colors">
        Portofolio
      </a>
      @endguest
    </div>
  </div>
</header>
