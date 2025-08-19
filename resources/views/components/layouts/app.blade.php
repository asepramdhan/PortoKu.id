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

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #020617;
      /* slate-950 */
      color: #F1F5F9;
      /* slate-100 */
    }

    .sidebar-link {
      display: flex;
      align-items: center;
      padding: 0.75rem 1.25rem;
      border-radius: 0.5rem;
      color: #94A3B8;
      /* slate-400 */
      transition: background-color 0.2s, color 0.2s;
    }

    .sidebar-link:hover {
      background-color: #1E293B;
      /* slate-800 */
      color: #F1F5F9;
      /* slate-100 */
    }

    .sidebar-link.active {
      background-color: #0EA5E9;
      /* sky-500 */
      color: white;
      font-weight: 600;
    }

    .card {
      background-color: #0F172A;
      /* slate-900 */
      border: 1px solid #1E293B;
      /* slate-800 */
      border-radius: 0.75rem;
    }

    /* Style untuk tabel */
    .table-wrapper {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      padding: 1rem 1.5rem;
      text-align: left;
    }

    thead {
      background-color: #1E293B;
      /* slate-800 */
    }

    th {
      color: #94A3B8;
      /* slate-400 */
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
    }

    tbody tr {
      border-bottom: 1px solid #1E293B;
      /* slate-800 */
    }

    tbody tr:last-child {
      border-bottom: none;
    }

    tbody tr:hover {
      background-color: #151f32;
      /* Sedikit lebih terang dari card */
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-weight: 500;
      font-size: 0.8rem;
    }

    .tab-link {
      padding: 0.5rem 1rem;
      border-bottom: 2px solid transparent;
      color: #94A3B8;
      /* slate-400 */
      font-weight: 500;
    }

    .tab-link:hover {
      color: #F1F5F9;
      /* slate-100 */
    }

    .tab-link.active {
      color: #38BDF8;
      /* sky-400 */
      border-bottom-color: #38BDF8;
    }

    .form-input {
      background-color: #1E293B;
      /* slate-800 */
      border: 1px solid #334155;
      /* slate-700 */
      border-radius: 0.5rem;
      padding: 0.75rem 1rem;
      width: 100%;
      color: white;
    }

    .form-input:focus {
      outline: none;
      border-color: #0EA5E9;
      /* sky-500 */
      box-shadow: 0 0 0 2px #0EA5E930;
    }

    .form-input.input-error {
      border-color: #ef4444;
      /* red-500 */
    }

    .form-input.input-error:focus {
      border-color: #ef4444;
      /* red-500 */
      box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
      /* Optional: Ganti ring jadi merah */
    }

    [x-cloak] {
      display: none !important;
    }

  </style>

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
