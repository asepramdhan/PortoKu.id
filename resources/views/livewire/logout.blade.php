<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect('/login');
    }
}; ?>

<div>
  <a href="#" wire:click.prevent="logout" class="sidebar-link">
    <x-icon name="lucide.log-out" class="mr-3" />
    Keluar
  </a>
</div>
