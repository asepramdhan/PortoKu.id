<?php

use function Laravel\Folio\{name};

name("password.reset");

?>

<x-layouts.app-home :title="__('Reset Kata Sandi')">
    <livewire:reset-password :token="$token" />
</x-layouts.app-home>
