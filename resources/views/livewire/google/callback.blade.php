<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

new class extends Component {
    public function mount(): void
    {
        try {
            $googleUser = Socialite::driver("google")->user();

            // Cari user di database berdasarkan google_id, atau buat user baru
            $user = User::updateOrCreate(
                [
                    "google_id" => $googleUser->id,
                ],
                [
                    "name" => $googleUser->name,
                    "email" => $googleUser->email,
                    "password" => Hash::make(Str::random(16)),
                ],
            );

            // Login-kan user tersebut
            Auth::login($user);

            // Arahkan ke halaman dashboard
            $this->redirect("/dashboard");
        } catch (\Exception $e) {
            // Jika ada error, kembalikan ke halaman login dengan pesan error
            $this->redirect("/login", navigate: true);
            session()->flash("error", "Terjadi kesalahan: " . $e->getMessage());
        }
    }
}; ?>

<div>//</div>
