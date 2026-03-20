<?php

$dir = __DIR__ . '/resources/views';

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$replacements = [
    'rounded-3xl' => 'rounded-lg',
    'rounded-2xl' => 'rounded-lg',
    'rounded-xl' => 'rounded-md',
    'shadow-xl' => 'shadow-sm',
    'shadow-lg' => 'shadow-sm',
    'shadow-md' => 'shadow-sm',
    'border-outline-variant' => 'border-slate-200',
    'bg-surface' => 'bg-white',
    'bg-surface-container-lowest' => 'bg-white',
    // also replacing left-over custom brand tokens
    'bg-primary/10' => 'bg-slate-100',
    'text-primary' => 'text-slate-900',
    'border-primary/20' => 'border-slate-200',
    'border-l-primary' => 'border-l-slate-800'
];

$count = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $newContent = strtr($content, $replacements);
        
        if ($content !== $newContent) {
            file_put_contents($file->getPathname(), $newContent);
            $count++;
            echo "Updated: " . $file->getFilename() . "\n";
        }
    }
}

echo "Total files updated: $count\n";
