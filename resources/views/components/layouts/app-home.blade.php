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
    /* Menggunakan font Inter sebagai default */
    body {
      font-family: 'Inter', sans-serif;
      background-color: #0F172A;
      /* slate-900 */
      color: #F1F5F9;
      /* slate-100 */
    }

    /* Efek gradien pada hero section */
    .hero-gradient {
      background: radial-gradient(ellipse 80% 80% at 50% -20%, rgba(56, 189, 248, 0.3), rgba(255, 255, 255, 0));
    }

    .feature-card {
      background-color: #1E293B;
      /* slate-800 */
      border: 1px solid #334155;
      /* slate-700 */
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .feature-icon {
      background-color: #1E293B;
      /* slate-800 */
      border: 1px solid #334155;
      /* slate-700 */
    }

    .step-card {
      background-color: #1E293B;
      /* slate-800 */
      border: 1px solid #334155;
      /* slate-700 */
      border-radius: 1rem;
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .step-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .step-number {
      font-size: 5rem;
      font-weight: 800;
      color: #334155;
      /* slate-700 */
      line-height: 1;
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

    .blog-card {
      background-color: #1E293B;
      /* slate-800 */
      border-radius: 0.75rem;
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .blog-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    /* Styling for article content */
    .prose-custom {
      color: #cbd5e1;
      /* slate-300 */
    }

    .prose-custom h2,
    .prose-custom h3 {
      color: #f1f5f9;
      /* slate-100 */
      margin-top: 2em;
      margin-bottom: 1em;
    }

    .prose-custom p {
      line-height: 1.75;
      margin-bottom: 1.5em;
    }

    .prose-custom a {
      color: #38bdf8;
      /* sky-400 */
      text-decoration: none;
    }

    .prose-custom a:hover {
      text-decoration: underline;
    }

    .prose-custom blockquote {
      border-left-color: #38bdf8;
      /* sky-400 */
      color: #94a3b8;
      /* slate-400 */
      margin-bottom: 1.5em;
    }

    .prose-custom strong {
      color: #7dd3fc;
      /* sky-300 */
    }

    .prose-custom code {
      background-color: #334155;
      /* slate-700 */
      color: #e2e8f0;
      /* slate-200 */
      padding: 0.2em 0.4em;
      border-radius: 0.25rem;
      font-weight: 600;
    }

    .prose-custom ul {
      margin-bottom: 1.5em;
      list-style-position: outside;
      padding-left: 1.25rem;
    }

    .prose-custom li {
      padding-left: 0.5rem;
      margin-bottom: 0.75em;
    }

    /* Mengatasi display: none dari AlpineJS agar transisi bekerja */
    [x-cloak] {
      display: none !important;
    }

  </style>

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
