<?php

use function Laravel\Folio\{middleware, name};

middleware(["guest"]);

name("register");

?>

<x-layouts.app-home :title="__('Daftar')">
    <livewire:register />
</x-layouts.app-home>
