<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $component = Livewire\Livewire::mount('admin.inventory.quick-edit');
    echo "Mounted successfully.\n";
    echo substr($component->html(), 0, 100) . "...\n";
} catch (\Exception $e) {
    echo "Exception: " . get_class($e) . "\n" . $e->getMessage() . "\n";
}
