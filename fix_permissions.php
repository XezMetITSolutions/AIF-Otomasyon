<?php
/**
 * Fix permissions for upload directories and files.
 * Run this once via browser: /fix_permissions.php
 */
echo "<pre>Starting permission fix...\n";

$baseDir = __DIR__ . '/uploads';

if (!file_exists($baseDir)) {
    mkdir($baseDir, 0755, true);
    echo "Created base 'uploads' directory.\n";
} else {
    chmod($baseDir, 0755);
    echo "Set 'uploads' to 0755.\n";
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    if ($item->isDir()) {
        chmod($item->getPathname(), 0755);
        echo "DIR  : " . $item->getPathname() . " -> 0755\n";
    } else {
        chmod($item->getPathname(), 0644);
        echo "FILE : " . $item->getPathname() . " -> 0644\n";
    }
}

echo "\nDone. Delete this file after use.</pre>";
?>
