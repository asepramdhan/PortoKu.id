<aside
    id="sidebar"
    :class="{'translate-x-0': isSidebarOpen, '-translate-x-full': !isSidebarOpen}"
    class="bg-slate-900 w-64 p-6 fixed inset-y-0 left-0 transform -translate-x-full lg:relative lg:translate-x-0 transition-transform duration-300 ease-in-out z-50 flex flex-col"
>
    <div class="flex justify-between items-center mb-10">
        @admin
            <a href="/admin/dashboard" class="text-2xl font-bold text-white">
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
        @else
            <a href="/dashboard" class="text-2xl font-bold text-white">
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
        @endadmin
        <button
            id="close-sidebar-btn"
            @click="isSidebarOpen = false"
            class="lg:hidden text-slate-400 hover:text-white cursor-pointer"
        >
            <x-icon name="lucide.x" />
        </button>
    </div>
    @if (auth()->check() && auth()->user()->is_admin)
        @admin
            <nav class="flex flex-col space-y-2">
                <a
                    href="/admin/dashboard"
                    wire:navigate
                    wire:current="active"
                    class="sidebar-link"
                >
                    <x-icon name="lucide.layout-dashboard" class="mr-3" />
                    Dashboard
                </a>
                <a
                    href="/admin/assets"
                    wire:navigate
                    wire:current="active"
                    class="sidebar-link"
                >
                    <x-icon name="lucide.wallet" class="mr-3" />
                    Kelola Aset
                </a>
                <a
                    href="/admin/blog"
                    wire:navigate
                    wire:current.exact="active"
                    class="sidebar-link"
                >
                    <x-icon name="lucide.book-open" class="mr-3" />
                    Kelola Blog
                </a>
                <a
                    href="/admin/blog/categories"
                    wire:navigate
                    wire:current="active"
                    class="sidebar-link"
                >
                    <x-icon name="lucide.tag" class="mr-3" />
                    Kelola Kategori
                </a>
                <a
                    href="/admin/blog/tags"
                    wire:navigate
                    wire:current="active"
                    class="sidebar-link"
                >
                    <x-icon name="lucide.tags" class="mr-3" />
                    Kelola Tag
                </a>
                <a
                    href="/admin/comments"
                    wire:navigate
                    wire:current="active"
                    class="sidebar-link"
                >
                    <x-icon name="lucide.messages-square" class="mr-3" />
                    Kelola Komentar
                </a>
                <a
                    href="/admin/users"
                    wire:navigate
                    wire:current="active"
                    class="sidebar-link"
                >
                    <x-icon name="lucide.users" class="mr-3" />
                    Kelola Pengguna
                </a>
                <a href="/admin/chat" target="_blank" class="sidebar-link">
                    <x-icon name="lucide.message-circle" class="mr-3" />
                    <livewire:admin.chat.unread-count />
                </a>
            </nav>
        @endadmin
    @else
        <nav class="flex flex-col space-y-2">
            <a
                href="/dashboard"
                wire:navigate
                wire:current="active"
                class="sidebar-link"
            >
                <x-icon name="lucide.layout-dashboard" class="mr-3" />
                Dashboard
            </a>
            <a
                href="/portofolio"
                wire:navigate
                wire:current="active"
                class="sidebar-link"
            >
                <x-icon name="lucide.bitcoin" class="mr-3" />
                Portofolio
            </a>
            <a
                href="/transactions"
                wire:navigate
                wire:current="active"
                class="sidebar-link"
            >
                <x-icon name="lucide.arrow-right-left" class="mr-3" />
                Transaksi
            </a>
            <a
                href="/reports"
                wire:navigate
                wire:current="active"
                class="sidebar-link"
            >
                <x-icon name="lucide.pie-chart" class="mr-3" />
                Laporan
            </a>
            <a
                href="/settings"
                wire:navigate
                wire:current="active"
                class="sidebar-link"
            >
                <x-icon name="lucide.settings" class="mr-3" />
                Pengaturan
            </a>
        </nav>
    @endif
    <div class="mt-auto">
        <livewire:logout />
    </div>
</aside>
