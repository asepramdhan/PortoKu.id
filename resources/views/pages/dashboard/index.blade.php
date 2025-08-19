<?php
 
use function Laravel\Folio\{middleware};
 
middleware(['auth', 'verified']);
 
?>
<x-layouts.app :title="__('Dashboard')">
  <livewire:dashboard.index />
</x-layouts.app>
