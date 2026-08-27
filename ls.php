<?php
echo "<pre>";
echo "DIR: " . __DIR__ . "\n\n";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
foreach ($it as $file) {
    if (!$file->isDir()) {
        echo $file->getPathname() . "\n";
    }
}
echo "</pre>";
