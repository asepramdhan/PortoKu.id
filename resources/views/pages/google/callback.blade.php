<?php

use function Laravel\Folio\{middleware};

middleware(["guest"]);

?>

<div>
    <livewire:google.callback />
</div>
