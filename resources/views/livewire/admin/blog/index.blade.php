<?php

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    public ?Post $deleting = null;
    public $showDeleteModal = false;

    public function prepareToDelete(Post $post)
    {
        $this->deleting = $post;
        $this->showDeleteModal = true;
    }

    public function deletePost()
    {
        if ($this->deleting) {
            $this->deleting->delete();
            session()->flash('message', 'Postingan berhasil dihapus.');
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
    <a href="#" class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors mt-4 md:mt-0">
      <i data-lucide="plus-circle" class="w-5 h-5"></i>
      Buat Postingan Baru
    </a>
  </div>

  <!-- Posts Table -->
  <div class="card">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Judul</th>
            <th>Penulis</th>
            <th>Status</th>
            <th>Tanggal Publikasi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($posts as $post)
          <tr>
            <td>
              <p class="font-semibold text-white">{{ Str::limit($post->title, 50) }}</p>
            </td>
            <td class="text-slate-300">{{ $post->user->name ?? 'N/A' }}</td>
            <td>
              @if($post->published_at && $post->published_at <= now()) <span class="status-badge bg-green-500/20 text-green-400">Diterbitkan</span>
                @else
                <span class="status-badge bg-yellow-500/20 text-yellow-400">Draf</span>
                @endif
            </td>
            <td class="text-slate-300">{{ $post->published_at ? $post->published_at->format('d M Y') : '-' }}</td>
            <td>
              <div class="flex space-x-2">
                <a href="#" class="text-slate-400 hover:text-sky-400"><i data-lucide="edit-3" class="w-5 h-5"></i></a>
                <button wire:click="prepareToDelete({{ $post->id }})" class="text-slate-400 hover:text-red-500"><i data-lucide="trash-2" class="w-5 h-5"></i></button>
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
      {{ $posts->links('vendor.livewire.tailwind-custom') }}
    </div>
  </div>

  <!-- Delete Modal -->
  <div x-data="{ show: @entangle('showDeleteModal') }" x-show="show" @keydown.escape.window="show = false" class="fixed inset-0 bg-black z-50 flex items-center justify-center p-4" style="background-color: rgba(0, 0, 0, 0.7);" x-cloak>
    <div @click.away="show = false" class="card w-full max-w-md">
      <div class="p-6 md:p-8 text-center">
        <h2 class="text-2xl font-bold text-white">Hapus Postingan?</h2>
        <p class="text-slate-400 mt-2">Anda yakin ingin menghapus postingan ini?</p>
        <div class="mt-6 flex justify-center gap-4">
          <button type="button" @click="show = false" class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-6 py-2 rounded-lg w-full">Batal</button>
          <button type="button" wire:click="deletePost" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-lg w-full">Ya, Hapus</button>
        </div>
      </div>
    </div>
  </div>
</div>
