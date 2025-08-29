<!DOCTYPE html>
<html
    lang="{{ str_replace("_", "-", app()->getLocale()) }}"
    class="scroll-smooth"
>
    <head>
        <link
            rel="icon"
            type="image/png"
            href="{{ asset("favicon-96x96.png") }}"
            sizes="96x96"
        />
        <link
            rel="icon"
            type="image/svg+xml"
            href="{{ asset("favicon.svg") }}"
        />
        <link rel="shortcut icon" href="{{ asset("favicon.ico") }}" />
        <link
            rel="apple-touch-icon"
            sizes="180x180"
            href="{{ asset("apple-touch-icon.png") }}"
        />
        <meta name="apple-mobile-web-app-title" content="MyWebSite" />
        <link rel="manifest" href="{{ asset("site.webmanifest") }}" />
        <meta charset="utf-8" />
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover"
        />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>
            {{ isset($title) ? config("app.name") . " - " . $title : config("app.name") }}
            
        </title>

        @vite(["resources/css/app.css", "resources/js/app.js"])

        <!-- Google Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
            rel="stylesheet"
        />
    </head>
    <body
        class="bg-slate-950"
        x-data="{
            isSidebarOpen: false,
            isAddModalOpen: false,
            isEditModalOpen: false,
            isDeleteModalOpen: false,
            isDeleteAccountModalOpen: false,
        }"
        @close-add-modal.window="isAddModalOpen = false"
        @close-edit-modal.window="isEditModalOpen = false"
        @close-delete-modal.window="isDeleteModalOpen = false"
        @close-delete-account-modal.window="isDeleteAccountModalOpen = false"
    >
        <div class="relative min-h-screen lg:flex">
            <!-- ===== Sidebar (Sticky) ===== -->
            <x-sidebar />

            <!-- Overlay for mobile -->
            <div
                id="sidebar-overlay"
                @click="isSidebarOpen = false"
                class="fixed inset-0 bg-black z-40 hidden lg:hidden"
                style="background-color: rgba(0, 0, 0, 0.7)"
                :class="{'hidden': !isSidebarOpen}"
            ></div>

            <!-- ===== Main Content (Scrollable) ===== -->
            <div class="flex-1 flex flex-col h-screen overflow-y-auto">
                <!-- Top Header (Sticky) -->
                <x-top-header />

                <!-- Page Content -->
                <x-main class="flex-1 p-6 md:p-8">
                    <x-slot:content>
                        {{ $slot }}
                    </x-slot>
                </x-main>

                @auth
                    @if (Auth::check() && ! Auth::user()->is_admin)
                        <livewire:user-chat-widget />
                    @endif
                @endauth

                <!-- ===== Footer ===== -->
                <x-footer />
            </div>
        </div>
        <!--- Cookies -->
        <livewire:cookies />
    </body>
</html>
