<?php

use App\Models\Post;
use App\Models\Category; 
use App\Models\Tag; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new class extends Component {
  use WithFileUploads;

  public ?Post $post = null;

  public string $title = '';
  public string $content = '';
  public ?string $published_at = null;
  public ?int $category_id = null;
  public string $tags = ''; // Untuk input tag

  public $photo;

  // Properti baru untuk Gambar Unggulan
  public $featured_image;
  public ?string $existing_featured_image = null;
  public ?TemporaryUploadedFile $previous_featured_image = null;

  public function mount(Post $post = null): void
  {
    // Jika ada post yang di-passing, kita sedang dalam mode edit
    if ($post->exists) {
      $this->post = $post;
      $this->title = $post->title;
      $this->content = $post->content;
      $this->published_at = $post->published_at?->format('Y-m-d');
      $this->category_id = $post->category_id;
      $this->tags = $post->tags->pluck('name')->implode(', ');
      $this->existing_featured_image = $post->featured_image_path;
    }
  }

  // FIX: Metode ini akan membersihkan unggahan sementara sebelumnya
  public function updatedFeaturedImage(TemporaryUploadedFile $value): void
  {
    // Hapus file sementara sebelumnya jika ada
    $this->previous_featured_image?->delete();
    // Simpan referensi file yang baru untuk pembersihan berikutnya
    $this->previous_featured_image = $value;
  }

  public function save(): void
  {
    $validated = $this->validate([
      'title' => 'required|string|max:255',
      'content' => 'required|string|min:10',
      'published_at' => 'nullable|date',
      'category_id' => 'required|exists:categories,id',
      'featured_image' => 'nullable|image|max:1024',
    ]);

    $data = $validated + [
      'user_id' => Auth::id(),
      'slug' => Str::slug($validated['title']) . '-' . Str::random(5),
    ];

    // Handle unggahan gambar unggulan
    if ($this->featured_image) {
      // Hapus gambar lama jika ada
      if ($this->post && $this->post->featured_image_path) {
        // FIX: Gunakan Str::after() untuk logika path yang lebih andal
        $oldPath = Str::after($this->post->featured_image_path, '/storage/');
        Storage::disk('public')->delete($oldPath);
      }
      // Simpan gambar baru dan dapatkan path-nya
      $path = $this->featured_image->store('featured-images', 'public');
      $data['featured_image_path'] = Storage::disk('public')->url($path);
    }

    $tagIds = [];
    if (!empty($this->tags)) {
      $tagNames = array_map('trim', explode(',', $this->tags));
      foreach ($tagNames as $tagName) {
        $tag = Tag::firstOrCreate(
          ['slug' => Str::slug($tagName) . '-' . Str::random(5)],
          ['name' => $tagName]
        );
        $tagIds[] = $tag->id;
      }
    }

    if ($this->post) {
      // Mode Update
      $this->post->update($data);
      $this->post->tags()->sync($tagIds);
      session()->flash('message', 'Postingan berhasil diperbarui.');
    } else {
      // Mode Create
      $newPost = Post::create($data);
      $newPost->tags()->sync($tagIds);
      session()->flash('message', 'Postingan berhasil dibuat.');
    }

    $this->redirect('/admin/blog', navigate: true);
  }

  public function getPhotoUrl()
  {
    $this->validate(['photo' => 'required|image|max:2048']);
    $path = $this->photo->store('attachments', 'public');
    return Storage::disk('public')->url($path);
  }

  // FIX: Metode baru untuk menghapus gambar saat dihapus dari Trix
  public function removeAttachment($url)
  {
    $path = Str::after($url, '/storage/');
    if (Storage::disk('public')->exists($path)) {
      Storage::disk('public')->delete($path);
    }
  }

  public function with(): array
  {
    return [
      'categories' => Category::all()
    ];
  }

}; ?>

<div>
  <!-- Page Content -->
  <main class="flex-1 p-6 md:p-8">
    <div class="flex items-center justify-between mb-6">
      <a href="/admin/blog" wire:navigate class="text-slate-400 hover:text-white flex items-center gap-2">
        <x-icon name="lucide.chevron-left" class="w-5 h-5" />
        Kembali ke Daftar Postingan
      </a>
    </div>

    <form x-data @submit.prevent>
      <div class="card p-6 md:p-8">
        <h1 class="text-2xl font-bold text-white mb-6">
          {{ $post ? 'Edit Postingan' : 'Buat Postingan Baru' }}
        </h1>

        <div class="space-y-6">
          <div>
            <label for="title" class="block text-sm font-medium text-slate-300 mb-2">Judul</label>
            <input type="text" id="title" wire:model="title" class="form-input @error('title') input-error @enderror" placeholder="Judul artikel yang menarik...">
            @error('title') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
          </div>

          <div>
            <label for="category" class="block text-sm font-medium text-slate-300 mb-2">Kategori</label>
            <select id="category" wire:model="category_id" class="form-input @error('category_id') input-error @enderror">
              <option value="">Pilih kategori...</option>
              @foreach($categories as $category)
              <option value="{{ $category->id }}">{{ $category->name }}</option>
              @endforeach
            </select>
            @error('category_id') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
          </div>

          <div>
            <label for="featured_image" class="block text-sm font-medium text-slate-300 mb-2">Gambar Unggulan (Thumbnail)</label>
            <div x-data="{ dragging: false }" @click="$refs.fileInput.click()" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; @this.upload('featured_image', $event.dataTransfer.files[0])" class="border-2 border-dashed border-slate-600 rounded-lg p-6 text-center transition-colors cursor-pointer" :class="{ 'border-sky-500 bg-slate-800': dragging }">

              <div wire:loading.remove wire:target="featured_image">
                @if ($featured_image)
                <img src="{{ $featured_image->temporaryUrl() }}" alt="Pratinjau Gambar" class="mx-auto rounded-lg h-32">
                @elseif ($existing_featured_image)
                <img src="{{ $existing_featured_image }}" alt="Gambar Saat Ini" class="mx-auto rounded-lg h-32">
                @else
                <div class="flex flex-col items-center justify-center">
                  <x-icon name="lucide.upload-cloud" class="w-12 h-12 text-slate-500" />
                  <p class="mt-2 text-sm text-slate-400">Seret & lepas gambar ke sini</p>
                  <label for="featured_image" class="mt-2 text-xs text-sky-400 hover:underline cursor-pointer">atau klik untuk memilih</label>
                </div>
                @endif
              </div>

              <div wire:loading.flex wire:target="featured_image" class="items-center justify-center">
                <p class="text-sm text-slate-400">Mengunggah...</p>
              </div>

              <input type="file" id="featured_image" x-ref="fileInput" wire:model="featured_image" class="hidden">
            </div>
            @error('featured_image') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
          </div>

          <div>
            <label for="content" class="block text-sm font-medium text-slate-300 mb-2">Konten</label>
            <x-trix-editor id="content" wire:model="content" class="@error('content') input-error @enderror"></x-trix-editor>
            @error('content') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
          </div>

          <div>
            <label for="tags" class="block text-sm font-medium text-slate-300 mb-2">Tags (Opsional)</label>
            <input type="text" id="tags" wire:model="tags" class="form-input" placeholder="bitcoin, investasi, keuangan">
          </div>

          <div>
            <label for="published_at" class="block text-sm font-medium text-slate-300 mb-2">Tanggal Publikasi (Opsional)</label>
            <input type="date" id="published_at" wire:model.lazy="published_at" class="form-input text-slate-400 @error('published_at') input-error @enderror">
            <p class="mt-2 text-xs text-slate-500">Kosongkan jika ingin disimpan sebagai draf.</p>
            @error('published_at') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
          </div>
        </div>

        <div class="pt-6 mt-6 border-t border-slate-800 flex justify-end">
          <button type="button" @click="$dispatch('trix-submit')" wire:loading.attr="disabled" wire:target="save" class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors cursor-pointer">
            <div class="flex items-center">
              <x-loading wire:loading wire:target="save" class="loading-dots mr-2" />
              <x-icon name="lucide.{{ $post ? 'edit' : 'plus' }}" wire:loading.remove wire:target="save" class="mr-2" />
              {{ $post ? 'Simpan Perubahan' : 'Publikasikan Postingan' }}
            </div>
          </button>
        </div>
      </div>
    </form>
  </main>
</div>
