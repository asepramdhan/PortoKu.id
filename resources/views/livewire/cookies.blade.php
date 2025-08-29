<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use App\Models\UserCookie;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $cookieName = "user_token";

    public function mount(): void
    {
        $cookieName = $this->cookieName;

        if (! Cookie::has($cookieName)) {
            Cookie::queue($cookieName, Str::uuid7()->toString(), 60 * 24 * 30);
            return;
        }

        $this->saveCookie();
    }

    private function saveCookie(): void
    {
        $cookieValue = Cookie::get($this->cookieName);

        // cek dulu apakah user belum login / register
        if (! Auth::check()) {
            return;
        }

        // cek dulu apakah user sudah punya data di tabel user_cookies
        $userCookie = UserCookie::where("cookie_name", $this->cookieName)
            ->where("user_id", auth()->id())
            ->first();

        if ($userCookie) {
            $userCookie->cookie_value = $cookieValue ?? "";
            $userCookie->save();
            return;
        }

        // jika user belum punya data di tabel user_cookies
        UserCookie::updateOrCreate([
            "cookie_name" => $this->cookieName,
            "cookie_value" => $cookieValue ?? "", // kasih default biar gak null
            "user_id" => auth()->id(),
        ]);
    }
}; ?>

<div></div>
