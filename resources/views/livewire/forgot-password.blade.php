<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div>
  <main class="flex items-center justify-center min-h-screen py-12 px-4 sm:px-6 lg:px-8" style="background-color: #0F172A;">
    <div class="w-full max-w-md space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
          Lupa Kata Sandi Anda?
        </h2>
        <p class="mt-2 text-center text-sm text-slate-400">
          Masukkan alamat email Anda di bawah ini dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.
        </p>
      </div>
      <form class="mt-8 space-y-6" wire:submit.prevent="forgotPassword">
        <div class="rounded-md shadow-sm">
          <div>
            <label for="email-address" class="sr-only">Alamat Email</label>
            <input id="email-address" wire:model="email" type="email" autocomplete="email" required class="form-input" placeholder="Alamat Email">
          </div>
        </div>

        <div>
          <button type="submit" wire:loading.attr="disabled" wire:target="forgotPassword" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-sky-500 cursor-pointer">
            <x-loading wire:loading wire:target="forgotPassword" class="loading-dots" />
            <span wire:loading.remove wire:target="forgotPassword">
              Kirim Tautan Reset
            </span>
          </button>
        </div>

        <div class="text-center">
          <a href="/login" wire:navigate class="font-medium text-sky-400 hover:text-sky-500 text-sm flex items-center justify-center gap-2">
            <x-icon name="lucide.arrow-left" class="w-4 h-4" />
            Kembali ke Login
          </a>
        </div>
      </form>
    </div>
  </main>
</div>
