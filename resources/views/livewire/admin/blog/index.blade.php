<?php

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

new class extends Component {
  use WithPagination, WithFileUploads;

  public ?Post $deleting = null;
  public $showDeleteModal = false;

  // === INLINE EDITING PROPERTIES ===
  public $editingId = null;
  public $editingField = null;
  public $editingValue = '';

  // === THUMBNAIL UPLOAD PROPERTIES ===
  public $imageUpload;
  public $postIdForImageUpload;

  public function editField($postId, $field)
  {
    $post = Post::find($postId);
    if (!$post) return;

    $this->editingId = $postId;
    $this->editingField = $field;

    if ($field === 'title') {
      $this->editingValue = $post->title;
    } elseif ($field === 'status') {
      $this->editingValue = $post->published_at && !$post->published_at->isFuture() ? 'published' : 'draft';
    } elseif ($field === 'published_at') {
      $this->editingValue = $post->published_at ? $post->published_at->format('Y-m-d') : '';
    }
  }

  public function saveField()
  {
    if ($this->editingId === null || $this->editingField === null) {
      return;
    }

    $post = Post::find($this->editingId);
    
    $rules = [];
    if ($this->editingField === 'title') {
      $rules['editingValue'] = 'required|string|max:255';
      $this->validate($rules);
      $post->update([
        'title' => $this->editingValue,
        'slug' => Str::slug($this->editingValue) . '-' . Str::random(5)
      ]);
    }
    
    if ($this->editingField === 'status') {
      $rules['editingValue'] = 'required|in:published,draft';
      $this->validate($rules);
      $post->update([
        'published_at' => $this->editingValue === 'published' ? now() : null,
      ]);
    }
    if ($this->editingField === 'published_at') {
      $rules['editingValue'] = 'nullable|date';
      $this->validate($rules);
      $post->update([
        'published_at' => $this->editingValue,
      ]);
    }

    $this->cancelEdit();
    session()->flash('message', 'Postingan berhasil diperbarui.');
  }

  public function cancelEdit()
  {
    $this->reset('editingId', 'editingField', 'editingValue');
  }

  // Metode ini akan berjalan otomatis saat gambar baru dipilih
  public function updatedImageUpload()
  {
    $this->validate([
      'imageUpload' => 'required|image|max:1024', // Maks 1MB
    ]);

    $post = Post::find($this->postIdForImageUpload);

    if ($post) {
      // Hapus gambar lama jika ada
      if ($post->featured_image_path) {
        $oldPath = Str::after($post->featured_image_path, '/storage/');
        Storage::disk('public')->delete($oldPath);
      }

      // Simpan gambar baru
      $path = $this->imageUpload->store('featured-images', 'public');
      $post->update(['featured_image_path' => Storage::disk('public')->url($path)]);

      $this->reset('imageUpload', 'postIdForImageUpload');
      session()->flash('message', 'Gambar berhasil diperbarui.');
    }
  }

  public function prepareToDelete(Post $post)
  {
    $this->deleting = $post;
    $this->showDeleteModal = true;
  }

  public function deletePost()
  {
    if ($this->deleting) {
      // 1. Hapus gambar dari konten Trix Editor
      preg_match_all('/<img src="([^"]+)"/', $this->deleting->content, $matches);
      foreach ($matches[1] as $imageUrl) {
        $path = Str::after($imageUrl, '/storage/');
        if (Storage::disk('public')->exists($path)) {
          Storage::disk('public')->delete($path);
        }
      }

      // FIX: Hapus gambar unggulan (thumbnail) jika ada
      if ($this->deleting->featured_image_path) {
        $path = Str::after($this->deleting->featured_image_path, '/storage/');
        if (Storage::disk('public')->exists($path)) {
          Storage::disk('public')->delete($path);
        }
      }

      // 2. Hapus data postingan dari database
      $this->deleting->delete();

      session()->flash('message', 'Postingan dan gambar terkait berhasil dihapus.');
    }
    $this->showDeleteModal = false;
  }

  public function with(): array
  {
    // Ambil semua post, diurutkan dari yang terbaru, dengan relasi ke user (penulis)
    $posts = Post::with('user')
      ->orderBy('created_at', 'desc')
      ->paginate(10);

    return [
      'posts' => $posts,
    ];
  }
}; ?>

<div>
  <!-- Page Content -->
  <div class="flex flex-col md:flex-row justify-between md:items-center mb-6">
    <h1 class="text-3xl font-bold text-white">Kelola Postingan Blog</h1>
    <a href="/admin/blog/create" wire:navigate class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors mt-4 md:mt-0">
      <x-icon name="lucide.plus-circle" class="h-5 w-5" />
      Buat Postingan Baru
    </a>
  </div>

  <x-notification />

  <!-- Posts Table -->
  <div class="card">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Gambar</th>
            <th>Judul</th>
            <th>Penulis</th>
            <th>Status</th>
            <th>Tanggal Publikasi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($posts as $post)
          <tr wire:key="{{ $post->id }}">
            <td>
              <label for="image-upload-{{ $post->id }}" class="cursor-pointer relative block w-20 h-12" wire:click="$set('postIdForImageUpload', {{ $post->id }})">

                @if($postIdForImageUpload === $post->id)
                <div wire:loading wire:target="imageUpload" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                  <x-loading class="loading-dots text-sky-400" />
                </div>
                @endif

                @if($post->featured_image_path)
                <div class="group hover:opacity-75 relative">
                  <img src="{{ $post->featured_image_path }}" alt="Thumbnail" class="w-20 h-12 object-cover rounded-md">
                  <div wire:loading.remove wire:target="imageUpload" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 hidden group-hover:block">
                    <x-icon name="lucide.upload-cloud" class="text-sky-400" />
                  </div>
                </div>
                @else
                <div class="w-20 h-12 bg-slate-700 rounded-md flex items-center justify-center text-slate-500 text-xs">No Img</div>
                @endif
              </label>
              <input type="file" id="image-upload-{{ $post->id }}" wire:model="imageUpload" class="hidden">
            </td>

            <td class="truncate">
              @if($editingId === $post->id && $editingField === 'title')
              <input type="text" wire:model="editingValue" wire:keydown.enter="saveField" wire:keydown.escape="cancelEdit" class="form-input text-sm p-1 **w-full**" x-init="$nextTick(() => $el.focus())" x-ref="editInput{{ $post->id }}_title" @click.away="$wire.cancelEdit()" x-trap.noscroll>
              @else
              <p wire:click="editField({{ $post->id }}, 'title')" class="font-semibold text-white cursor-pointer hover:bg-slate-700 p-1 rounded">{{ Str::limit($post->title, 25) }}</p>
              @endif
            </td>
            <td class="text-slate-300 truncate">{{ Str::title($post->user->name ?? 'N/A') }}</td>
            <td class="whitespace-nowrap">
              @if($editingId === $post->id && $editingField === 'status')
              <div class="flex items-center gap-2">
                <select wire:model="editingValue" wire:change="saveField" wire:keydown.escape="cancelEdit" class="bg-slate-800 text-white text-sm rounded-md border-slate-600 focus:border-sky-500 focus:ring-sky-500" x-init="$nextTick(() => $el.focus())" x-ref="editInput{{ $post->id }}_status" @click.away="$wire.cancelEdit()" x-trap.noscroll>
                  <option value="published">Diterbitkan</option>
                  <option value="draft">Draf</option>
                </select>
              </div>
              @else
              <div wire:click="editField({{ $post->id }}, 'status')" class="cursor-pointer hover:bg-slate-700 p-1 rounded">
                @if($post->published_at && !$post->published_at->isFuture()) <span class="status-badge bg-green-500/20 text-green-400">Diterbitkan</span>
                @else
                <span class="status-badge bg-yellow-500/20 text-yellow-400">Draf</span>
                @endif
              </div>
              @endif
            </td>
            <td class="text-slate-300 truncate">{{ $post->published_at ? $post->published_at->format('d M Y') : '-' }}</td>
            <td>
              <div class="flex space-x-4 lg:space-x-2">
                @if ($post->published_at && !$post->published_at->isFuture())
                <a href="/blog/show/{{ $post->slug }}" target="_blank" class="text-slate-400 hover:text-orange-400">
                  <x-icon name="lucide.eye" class="w-5 h-5" />
                </a>
                @endif
                <a href="/admin/blog/edit/{{ $post->slug }}" wire:navigate class="text-slate-400 hover:text-sky-400">
                  <x-icon name="lucide.edit-3" class="w-5 h-5" />
                </a>
                <button wire:click="prepareToDelete({{ $post->id }})" class="text-slate-400 hover:text-red-500 cursor-pointer">
                  <x-icon name="lucide.trash-2" class="w-5 h-5" />
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center py-8 text-slate-400">Belum ada postingan.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <!-- Pagination -->
    <div class="p-4 border-t border-slate-800">
      {{ $posts->links('livewire.tailwind-custom') }}
    </div>
  </div>

  <!-- Delete Modal -->
  <div x-data="{ show: @entangle('showDeleteModal') }" x-show="show" @keydown.escape.window="show = false" class="fixed inset-0 bg-black z-50 flex items-center justify-center p-4" style="background-color: rgba(0, 0, 0, 0.7);" x-cloak>
    <div @click.away="show = false" class="card w-full max-w-md">
      <div class="p-6 md:p-8 text-center">
        <h2 class="text-2xl font-bold text-white">Hapus Postingan?</h2>
        <p class="text-slate-400 mt-2">Anda yakin ingin menghapus postingan ini?</p>
        <div class="mt-6 flex justify-center gap-4">
          <button type="button" @click="show = false" class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-6 py-2 rounded-lg w-full cursor-pointer">Batal</button>
          <button type="button" wire:click="deletePost" wire:loading.attr="disabled" wire:target="deletePost" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-lg w-full cursor-pointer">
            <div class="flex items-center justify-center">
              <x-loading wire:loading wire:target="deletePost" class="loading-dots mr-2" />
              <x-icon name="lucide.trash-2" wire:loading.remove wire:target="deletePost" class="mr-2" />
              Ya, Hapus
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
