<aside id="sidebar" :class="{'translate-x-0': isSidebarOpen, '-translate-x-full': !isSidebarOpen}" class="bg-slate-900 w-64 p-6 fixed inset-y-0 left-0 transform -translate-x-full lg:relative lg:translate-x-0 transition-transform duration-300 ease-in-out z-50 flex flex-col">
  <div class="flex justify-between items-center mb-10">
    <a href="/" class="text-2xl font-bold text-white">
      Porto<span class="text-sky-400">Ku</span>.id
    </a>
    <button id="close-sidebar-btn" @click="isSidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white cursor-pointer">
      <x-icon name="lucide.x" />
    </button>
  </div>
  <nav class="flex flex-col space-y-2">
    <a href="/dashboard" wire:navigate class="sidebar-link {{ request()->is('dashboard') ? 'active' : '' }}">
      <x-icon name="lucide.layout-dashboard" class="mr-3" />
      Dashboard
    </a>
    <a href="/portofolio" wire:navigate class="sidebar-link {{ request()->is('portofolio') ? 'active' : '' }}">
      <x-icon name="lucide.bitcoin" class="mr-3" />
      Portofolio
    </a>
    <a href="/transactions" wire:navigate class="sidebar-link {{ request()->is('transactions') ? 'active' : '' }}">
      <x-icon name="lucide.arrow-right-left" class="mr-3" />
      Transaksi
    </a>
    <a href="/reports" wire:navigate class="sidebar-link {{ request()->is('reports') ? 'active' : '' }}">
      <x-icon name="lucide.pie-chart" class="mr-3" />
      Laporan
    </a>
    <a href="/settings" wire:navigate class="sidebar-link {{ request()->is('settings') ? 'active' : '' }}">
      <x-icon name="lucide.settings" class="mr-3" />
      Pengaturan
    </a>
    @admin
    <a href="/admin/blog" wire:navigate class="sidebar-link {{ request()->is('admin/blog*') ? 'active' : '' }}">
      <x-icon name="lucide.edit" class="mr-3" />
      Kelola Blog
    </a>
    @endadmin
  </nav>
  <div class="mt-auto">
    <livewire:logout />
  </div>
</aside>
