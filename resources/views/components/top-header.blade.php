<header
    class="bg-slate-900/80 backdrop-blur-sm border-b border-slate-800 p-4 flex justify-between items-center lg:justify-end sticky top-0 z-30"
>
    <button
        id="open-sidebar-btn"
        @click="isSidebarOpen = true"
        class="lg:hidden text-white mr-4 cursor-pointer"
    >
        <x-icon name="lucide.menu" class="w-6 h-6" />
    </button>
    <div class="flex items-center space-x-4">
        <x-dark-mode-toggle />
        <livewire:notification-bell />
        <div class="flex items-center space-x-2">
            <img
                src="{{ Auth::user()->profile_photo_path ? asset("storage/" . Auth::user()->profile_photo_path) : "https://placehold.co/80x80/0EA5E9/FFFFFF?text=" . substr(Auth::user()->name, 0, 1) }}"
                alt="User Avatar"
                class="w-8 h-8 rounded-full"
            />
            <span class="text-white font-semibold hidden sm:block">
                {{ Str::title(Str::limit(auth()->user()->name, 10)) }}
            </span>
        </div>
    </div>
</header>
