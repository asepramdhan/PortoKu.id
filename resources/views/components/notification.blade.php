@php
    // Cek apakah ini pesan error atau sukses
    $isError = session()->has("error");
    $isInfo = session()->has("info");
    // Ambil pesan yang sesuai
    $message = session("message") ?? (session("error") ?? session("info"));

    // Tentukan kelas CSS secara dinamis berdasarkan tipe pesan
    $wrapperClasses = $isError ? "bg-red-500/10 border-red-500/30 text-red-300" : ($isInfo ? "bg-sky-500/10 border-sky-500/30 text-sky-300" : "bg-green-500/10 border-green-500/30 text-green-300");

    $iconClasses = $isError ? "text-red-400" : ($isInfo ? "text-sky-400" : "text-green-400");
    $buttonClasses = $isError ? "text-red-300/70 hover:text-white" : ($isInfo ? "text-sky-300/70 hover:text-white" : "text-green-300/70 hover:text-white");
@endphp

{{-- Hanya tampilkan komponen jika ada pesan (baik sukses maupun error) --}}
@if ($message)
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => (show = false), 4000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        class="mb-6 p-4 rounded-lg flex justify-between items-center {{ $wrapperClasses }}"
        role="alert"
    >
        <div class="flex items-center">
            {{-- Tampilkan ikon yang benar --}}
            @if ($isError)
                <x-icon
                    name="lucide.triangle-alert"
                    class="w-6 h-6 mr-3 {{ $iconClasses }}"
                />
            @elseif ($isInfo)
                <x-icon
                    name="lucide.circle-alert"
                    class="w-6 h-6 mr-3 {{ $iconClasses }}"
                />
            @else
                <x-icon
                    name="lucide.circle-check"
                    class="w-6 h-6 mr-3 {{ $iconClasses }}"
                />
            @endif

            {{-- Tampilkan pesan yang benar --}}
            <span class="font-semibold">{{ $message }}</span>
        </div>

        {{-- Tombol Tutup --}}
        <button
            @click="show = false"
            class="{{ $buttonClasses }} cursor-pointer"
        >
            <x-icon name="lucide.x" class="w-5 h-5" />
        </button>
    </div>
@endif
