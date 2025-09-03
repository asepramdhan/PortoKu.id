<?php

use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Post; // Ganti dengan model Anda, misal: Post, Product, dll.

Route::get('/sitemap.xml', function () {
  $sitemap = Sitemap::create()
    ->add(Url::create('/')->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

  // Ambil semua artikel/postingan dari database Anda
  // Ganti 'Article' dengan nama model yang Anda gunakan
  $articles = Post::all();
  foreach ($articles as $article) {
    $sitemap->add(Url::create("/articles/{$article->slug}") // Sesuaikan dengan struktur URL Anda
      ->setLastModificationDate($article->updated_at)
      ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
      ->setPriority(0.8));
  }

  // Karena Anda pakai Folio, kita bisa scan halaman statis
  // Ini cara cerdas untuk menambahkan halaman Folio Anda
  $folioPagesPath = resource_path('views/pages');
  $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folioPagesPath));
  foreach ($files as $file) {
    if ($file->isDir()) {
      continue;
    }

    // Mengubah path file menjadi URL
    $path = str_replace([$folioPagesPath, '.blade.php', 'index'], '', $file->getPathname());
    $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

    // Hanya tambahkan jika path tidak kosong (untuk halaman utama)
    if (!empty($path)) {
      $sitemap->add(Url::create($path)
        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
        ->setPriority(0.7));
    }
  }

  return $sitemap;
});
