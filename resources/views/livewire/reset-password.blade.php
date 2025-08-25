<?php

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component {
    public string $token;
    public string $email;
    public string $password;
    public string $password_confirmation;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query("email", "");
    }

    public function resetPassword(): void
    {
        $this->validate([
            "token" => ["required"],
            "email" => ["required", "email"],
            "password" => ["required", "min:8", "confirmed"],
        ]);

        $status = Password::broker()->reset(
            $this->only([
                "email",
                "password",
                "password_confirmation",
                "token",
            ]),
            function ($user, $password) {
                $user
                    ->forceFill([
                        "password" => Illuminate\Support\Facades\Hash::make(
                            $password,
                        ),
                        "remember_token" => Str::random(60),
                    ])
                    ->save();
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash("message", __($status));
            $this->redirect("/login", navigate: true);
            return;
        }

        session()->flash("error", __($status));
    }
}; ?>

<div>
    <main
        class="flex items-center justify-center min-h-screen py-12 px-4 sm:px-6 lg:px-8"
    >
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                    Atur ulang kata sandi Anda.
                </h2>
            </div>

            <x-notification />

            <form class="mt-8 space-y-6" wire:submit.prevent="resetPassword">
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

                <div class="rounded-md shadow-sm">
                    <div>
                        <label for="password" class="sr-only">
                            Kata Sandi Baru
                        </label>
                        <input
                            id="password"
                            wire:model="password"
                            type="password"
                            required
                            class="form-input @error("password") input-error @enderror"
                            placeholder="Kata Sandi Baru"
                        />
                        @error("password")
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-md shadow-sm">
                    <div>
                        <label for="password_confirmation" class="sr-only">
                            Konfirmasi Kata Sandi
                        </label>
                        <input
                            id="password_confirmation"
                            wire:model="password_confirmation"
                            type="password"
                            required
                            class="form-input"
                            placeholder="Konfirmasi Kata Sandi"
                        />
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="resetPassword"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-sky-500 cursor-pointer"
                    >
                        <div class="flex items-center">
                            <x-loading
                                wire:loading
                                wire:target="resetPassword"
                                class="loading-dots mr-2"
                            />
                            <x-icon
                                name="lucide.lock-keyhole-open"
                                wire:loading.remove
                                wire:target="resetPassword"
                                class="mr-2"
                            />
                            Reset Kata Sandi
                        </div>
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
