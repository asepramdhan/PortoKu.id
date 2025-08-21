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
<body x-data="{ isMenuOpen: false }">

  <!-- ===== Header / Navigation Bar ===== -->
  <x-home-navbar />

  <!-- The main content with `full-width` -->
  <x-main>
    <!-- This is a sidebar that works also as a drawer on small screens -->

    <!-- The `$slot` goes here -->
    <x-slot:content>
      {{ $slot }}
    </x-slot:content>

  </x-main>

  <!-- ===== Footer ===== -->
  <x-home-footer />
</body>
</html>
