<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;

new class extends Component {
    public $name = "",
        $email = "",
        $subject = "",
        $message = "";

    public function sendMessage()
    {
        $validated = $this->validate([
            "name" => "required",
            "email" => "required|email",
            "subject" => "required",
            "message" => "required",
        ]);

        // Ganti dengan email admin Anda
        $adminEmail = config("mail.from.address", "admin@portoku.id");
        try {
            Mail::to($adminEmail)->send(
                new ContactFormMail(
                    $validated["name"],
                    $validated["email"],
                    $validated["subject"],
                    $validated["message"],
                ),
            );

            session()->flash("message", "Pesan Anda berhasil dikirim.");
            $this->reset("name", "email", "subject", "message");
        } catch (\Exception $e) {
            session()->flash(
                "error",
                "Terjadi kesalahan saat mengirim pesan. Silakan coba lagi nanti.",
            );
        }
    }
}; ?>

<div class="py-12 md:py-20">
    <div class="container mx-auto px-6">
        <div class="max-w-3xl mx-auto">
            <header class="text-center mb-12">
                <h1
                    class="text-4xl md:text-5xl font-extrabold text-white leading-tight"
                >
                    Hubungi Kami
                </h1>
                <p class="mt-4 text-lg text-slate-400">
                    Punya pertanyaan, masukan, atau butuh bantuan? Kami siap
                    mendengarkan.
                </p>
            </header>

            <div class="card p-6 md:p-8">
                <x-notification />
                <form wire:submit.prevent="sendMessage" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label
                                for="name"
                                class="block text-sm font-medium text-slate-300 mb-2"
                            >
                                Nama Lengkap
                            </label>
                            <input
                                type="text"
                                id="name"
                                wire:model="name"
                                class="form-input"
                                placeholder="Nama Anda"
                            />
                            @error("name")
                                <span class="text-red-500 text-sm mt-1">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                        <div>
                            <label
                                for="email"
                                class="block text-sm font-medium text-slate-300 mb-2"
                            >
                                Alamat Email
                            </label>
                            <input
                                type="email"
                                id="email"
                                wire:model="email"
                                class="form-input"
                                placeholder="email@anda.com"
                            />
                            @error("email")
                                <span class="text-red-500 text-sm mt-1">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label
                            for="subject"
                            class="block text-sm font-medium text-slate-300 mb-2"
                        >
                            Subjek
                        </label>
                        <input
                            type="text"
                            id="subject"
                            wire:model="subject"
                            class="form-input"
                            placeholder="Subjek pesan Anda"
                        />
                        @error("subject")
                            <span class="text-red-500 text-sm mt-1">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
                    <div>
                        <label
                            for="message"
                            class="block text-sm font-medium text-slate-300 mb-2"
                        >
                            Pesan
                        </label>
                        <textarea
                            id="message"
                            wire:model="message"
                            rows="5"
                            class="form-input"
                            placeholder="Tulis pesan Anda di sini..."
                        ></textarea>
                        @error("message")
                            <span class="text-red-500 text-sm mt-1">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
                    <div>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-3 rounded-lg transition-colors cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="sendMessage">
                                Kirim Pesan
                            </span>
                            <span wire:loading wire:target="sendMessage">
                                Mengirim...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
