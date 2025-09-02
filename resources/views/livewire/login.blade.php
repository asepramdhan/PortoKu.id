<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $email = "";
    public $password = "";
    public $remember = false;

    protected $rules = [
        "email" => "required|email",
        "password" => "required",
    ];

    public function login(): void
    {
        $credentials = $this->validate();
        if (Auth::attempt($credentials, $this->remember)) {
            session()->regenerate();

            // cek dulu apakah user adalah admin
            if (Auth::user()->is_admin) {
                $this->redirect("/admin/dashboard");
            } else {
                $this->redirect("/dashboard");
            }
        }
        // tampung error ke session
        session()->flash("error", "Email atau kata sandi salah.");
    }
}; ?>

<div>
    <main
        class="flex items-center justify-center min-h-screen py-12 px-4 sm:px-6 lg:px-8"
    >
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                    Login ke Akun Anda
                </h2>
                <p class="mt-2 text-center text-sm text-slate-400">
                    Atau
                    <a
                        href="/register"
                        wire:navigate
                        class="font-medium text-sky-400 hover:text-sky-500"
                    >
                        buat akun baru
                    </a>
                    jika Anda belum terdaftar.
                </p>
            </div>

            <x-notification />

            <form class="mt-8 space-y-6" wire:submit.prevent="login">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div class="mb-6">
                        <label for="email-address" class="sr-only">
                            Alamat Email
                        </label>
                        <input
                            id="email-address"
                            wire:model="email"
                            type="email"
                            autocomplete="email"
                            required
                            class="form-input rounded-t-md"
                            placeholder="Alamat Email"
                        />
                    </div>
                    <div>
                        <label for="password" class="sr-only">Kata Sandi</label>
                        <input
                            id="password"
                            wire:model="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="form-input rounded-b-md"
                            placeholder="Kata Sandi"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember-me"
                            wire:model="remember"
                            type="checkbox"
                            class="h-4 w-4 text-sky-600 focus:ring-sky-500 border-slate-600 bg-slate-700 rounded"
                        />
                        <label
                            for="remember-me"
                            class="ml-2 block text-sm text-slate-300"
                        >
                            Ingat saya
                        </label>
                    </div>

                    <div class="text-sm">
                        <a
                            href="/forgot-password"
                            wire:navigate
                            class="font-medium text-sky-400 hover:text-sky-500"
                        >
                            Lupa kata sandi?
                        </a>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="login"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-sky-500 cursor-pointer"
                    >
                        <div class="flex items-center">
                            <x-loading
                                wire:loading
                                wire:target="login"
                                class="loading-dots mr-2"
                            />
                            <x-icon
                                name="lucide.log-in"
                                wire:loading.remove
                                wire:target="login"
                                class="mr-2"
                            />
                            Login
                        </div>
                    </button>
                </div>
            </form>

            <!--=== === Tahap Pengembangan === ===-->
            {{--
                <div class="relative flex py-5 items-center">
                <div class="flex-grow border-t border-slate-700"></div>
                <span class="flex-shrink mx-4 text-slate-400 text-sm">
                Atau lanjutkan dengan
                </span>
                <div class="flex-grow border-t border-slate-700"></div>
                </div>
                
                <div>
                <a
                href="/google/redirect"
                class="w-full inline-flex justify-center py-3 px-4 border border-slate-600 rounded-md shadow-sm bg-slate-800 text-sm font-medium text-white hover:bg-slate-700 transition-colors"
                >
                <img
                class="w-5 h-5 mr-2"
                src="https://developers.google.com/identity/images/g-logo.png"
                alt="Google logo"
                />
                Login dengan Google
                </a>
                </div>
            --}}
        </div>
    </main>
</div>
