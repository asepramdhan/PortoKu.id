<?php

use Laravel\Socialite\Facades\Socialite;
use Livewire\Volt\Component;

new class extends Component {
    public function mount(): void
    {
        $this->redirect(Socialite::driver("google")->redirect());
    }
}; ?>

<div></div>
