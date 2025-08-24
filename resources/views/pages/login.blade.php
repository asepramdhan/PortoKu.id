<?php

use function Laravel\Folio\{middleware, name};

middleware(["guest"]);

name("login");

?>

<x-layouts.app-home :title="__('Masuk')">
    <livewire:login />
</x-layouts.app-home>
