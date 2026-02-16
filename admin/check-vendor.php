<?php
header('Content-Type: text/plain');
$vendorDir = __DIR__ . '/../vendor';
if (is_dir($vendorDir)) {
    echo "Vendor directory exists.\n";
    $files = scandir($vendorDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file\n";
            if (is_dir("$vendorDir/$file")) {
                $subfiles = scandir("$vendorDir/$file");
                foreach ($subfiles as $sub) {
                    if ($sub != '.' && $sub != '..') {
                        echo "  - $sub\n";
                    }
                }
            }
        }
    }
} else {
    echo "Vendor directory NOT found at $vendorDir\n";
}
?>