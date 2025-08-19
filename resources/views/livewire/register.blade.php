<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
  public $name = '';
  public $email = '';
  public $password = '';
  public $password_confirmation = '';

  protected $rules = [
    'name' => 'required|string|max:255',
    'email' => 'required|string|email|max:255|unique:users',
    'password' => 'required|string|min:8|confirmed',
  ],
  $messages = [
    'name.required' => 'Nama lengkap harus diisi.',
    'name.string' => 'Nama lengkap harus berupa teks.',
    'name.max' => 'Nama lengkap tidak boleh lebih dari 255 karakter.',
    'email.required' => 'Alamat email harus diisi.',
    'email.string' => 'Alamat email harus berupa teks.',
    'email.email' => 'Format alamat email tidak valid.',
    'email.max' => 'Alamat email tidak boleh lebih dari 255 karakter.',
    'email.unique' => 'Alamat email sudah terdaftar.',
    'password.required' => 'Kata sandi harus diisi.',
    'password.string' => 'Kata sandi harus berupa teks.',
    'password.min' => 'Kata sandi harus memiliki minimal 8 karakter.',
    'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
  ];

  public function register(): void
  {
    $validated = $this->validate();
    $user = User::create($validated);
    Auth::login($user);
    $this->redirect('/dashboard');
  }
}; ?>

<div>
  <main class="flex items-center justify-center min-h-screen py-12 px-4 sm:px-6 lg:px-8" style="background-color: #0F172A;">
    <div class="w-full max-w-md space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
          Buat Akun Baru Anda
        </h2>
        <p class="mt-2 text-center text-sm text-slate-400">
          Atau <a href="/login" wire:navigate class="font-medium text-sky-400 hover:text-sky-500">login</a> jika Anda sudah punya akun.
        </p>
      </div>
      <form class="mt-8 space-y-6" wire:submit.prevent="register">
        <div class="rounded-md shadow-sm -space-y-px">
          <div class="mb-6">
            <label for="full-name" class="sr-only">Nama Lengkap</label>
            <input id="full-name" wire:model="name" type="text" autocomplete="name" required class="form-input @error('name') input-error @enderror" placeholder="Nama Lengkap"> @error('name') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
          </div>
          <div class="mb-6">
            <label for="email-address" class="sr-only">Alamat Email</label>
            <input id="email-address" wire:model="email" type="email" autocomplete="email" required class="form-input @error('email') input-error @enderror" placeholder="Alamat Email"> @error('email') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
          </div>
          <div class="mb-6">
            <label for="password" class="sr-only">Kata Sandi</label>
            <input id="password" wire:model="password" type="password" autocomplete="new-password" required class="form-input @error('password') input-error @enderror" placeholder="Kata Sandi"> @error('password') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
          </div>
          <div>
            <label for="password-confirmation" class="sr-only">Konfirmasi Kata Sandi</label>
            <input id="password-confirmation" wire:model="password_confirmation" type="password" autocomplete="new-password" required class="form-input @error('password_confirmation') input-error @enderror" placeholder="Konfirmasi Kata Sandi"> @error('password_confirmation') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
          </div>
        </div>

        <div>
          <button type="submit" wire:loading.attr="disabled" wire:target="register" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-sky-500 cursor-pointer">
            <div class="flex items-center">
              <x-loading wire:loading wire:target="register" class="loading-dots mr-2" />
              <x-icon name="lucide.user-plus" wire:loading.remove wire:target="register" class="mr-2" />
              Daftar Akun
            </div>
          </button>
        </div>
      </form>
    </div>
  </main>
</div>
