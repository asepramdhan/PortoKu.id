<?php

use Illuminate\Support\Facades\Password;
use Livewire\Volt\Component;

new class extends Component {
    public string $email = "";
    public ?string $emailSentMessage = null;

    public function sendResetLink(): void
    {
        $this->validate(["email" => "required|email"]);

        // Menampilkan notifikasi secara instan
        $this->emailSentMessage = __(
            "Kami telah mengirimkan tautan reset kata sandi ke email Anda!",
        );

        // Mengirim email ke antrian untuk diproses di latar belakang
        Password::broker()->sendResetLink(["email" => $this->email]);
    }
}; ?>

<div>
    <main
        class="flex items-center justify-center min-h-screen py-12 px-4 sm:px-6 lg:px-8"
    >
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                    Lupa Kata Sandi Anda?
                </h2>
                <p class="mt-2 text-center text-sm text-slate-400">
                    Masukkan alamat email Anda di bawah ini dan kami akan
                    mengirimkan tautan untuk mengatur ulang kata sandi Anda.
                </p>
            </div>

            @if ($emailSentMessage)
                <div
                    class="p-4 mb-4 text-sm text-green-300 bg-green-500/10 border border-green-500/30 rounded-lg"
                >
                    {{ $emailSentMessage }}
                </div>
            @else
                <form
                    class="mt-8 space-y-6"
                    wire:submit.prevent="sendResetLink"
                >
                    <div class="rounded-md shadow-sm">
                        <div>
                            <label for="email-address" class="sr-only">
                                Alamat Email
                            </label>
                            <input
                                id="email-address"
                                wire:model="email"
                                type="email"
                                autocomplete="email"
                                required
                                class="form-input @error("email") input-error @enderror"
                                placeholder="Alamat Email"
                                autofocus
                            />
                            @error("email")
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="sendResetLink"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-sky-500 cursor-pointer"
                        >
                            <div class="flex items-center">
                                <x-loading
                                    wire:loading
                                    wire:target="sendResetLink"
                                    class="loading-dots mr-2"
                                />
                                <x-icon
                                    name="lucide.send"
                                    wire:loading.remove
                                    wire:target="sendResetLink"
                                    class="mr-2"
                                />
                                Kirim Tautan Reset
                            </div>
                        </button>
                    </div>

                    <div class="text-center">
                        <a
                            href="/login"
                            wire:navigate
                            class="font-medium text-sky-400 hover:text-sky-500 text-sm flex items-center justify-center gap-2"
                        >
                            <x-icon name="lucide.arrow-left" class="w-4 h-4" />
                            Kembali ke Login
                        </a>
                    </div>
                </form>
            @endif
        </div>
    </main>
</div>
