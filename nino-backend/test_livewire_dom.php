<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$html = view('livewire.admin.inventory.quick-edit', ['stockItem' => null])->render();

$dom = new \DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
libxml_clear_errors();

$nodes = collect($dom->childNodes)->filter(function ($node) {
    return $node->nodeType === XML_ELEMENT_NODE;
});

echo "Total HTML elements at root: " . $nodes->count() . "\n";
foreach ($nodes as $node) {
    echo "Node name: " . $node->nodeName . "\n";
    echo "Node content: " . substr(trim((string)$node->textContent), 0, 50) . "...\n";
}
