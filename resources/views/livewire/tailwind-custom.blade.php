@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
  {{-- Previous Page Link --}}
  <div class="flex-1 flex justify-start">
    @if ($paginator->onFirstPage())
    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-500 bg-slate-800 rounded-md cursor-not-allowed">
      {!! __('pagination.previous') !!}
    </span>
    @else
    <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-300 bg-slate-700 hover:bg-slate-600 rounded-md transition-colors cursor-pointer">
      {!! __('pagination.previous') !!}
    </button>
    @endif
  </div>

  {{-- Pagination Information --}}
  <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
    <div>
      <p class="text-sm text-slate-400">
        Menampilkan
        <span class="font-medium text-white">{{ $paginator->firstItem() }}</span>
        sampai
        <span class="font-medium text-white">{{ $paginator->lastItem() }}</span>
        dari
        <span class="font-medium text-white">{{ $paginator->total() }}</span>
        hasil
      </p>
    </div>
  </div>

  {{-- Next Page Link --}}
  <div class="flex-1 flex justify-end">
    @if ($paginator->hasMorePages())
    <button wire:click="nextPage" wire:loading.attr="disabled" rel="next" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-300 bg-slate-700 hover:bg-slate-600 rounded-md transition-colors cursor-pointer">
      {!! __('pagination.next') !!}
    </button>
    @else
    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-500 bg-slate-800 rounded-md cursor-not-allowed">
      {!! __('pagination.next') !!}
    </span>
    @endif
  </div>
</nav>
@endif
