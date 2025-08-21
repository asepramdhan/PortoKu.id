<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ isset($title) ? config('app.name').' - '.$title : config('app.name') }}</title>

  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

</head>
<body class="bg-slate-950" x-data="{ isSidebarOpen: false, isAddModalOpen: false, isEditModalOpen: false, isDeleteModalOpen: false, isDeleteAccountModalOpen: false }" @close-add-modal.window="isAddModalOpen = false" @close-edit-modal.window="isEditModalOpen = false" @close-delete-modal.window="isDeleteModalOpen = false" @close-delete-account-modal.window="isDeleteAccountModalOpen = false">

  <div class="relative min-h-screen lg:flex">
    <!-- ===== Sidebar (Sticky) ===== -->
    <x-sidebar />

    <!-- Overlay for mobile -->
    <div id="sidebar-overlay" @click="isSidebarOpen = false" class="fixed inset-0 bg-black z-40 hidden lg:hidden" style="background-color: rgba(0, 0, 0, 0.7);" :class="{'hidden': !isSidebarOpen}"></div>

    <!-- ===== Main Content (Scrollable) ===== -->
    <div class="flex-1 flex flex-col h-screen overflow-y-auto">
      <!-- Top Header (Sticky) -->
      <x-top-header />

      <!-- Page Content -->
      <x-main class="flex-1 p-6 md:p-8">
        <x-slot:content>
          {{ $slot }}
        </x-slot:content>
      </x-main>
      <!-- ===== Footer ===== -->
      <x-footer />
    </div>
  </div>

</body>
</html>
