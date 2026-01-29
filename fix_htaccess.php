<?php
// fix_htaccess.php
// Bu script uploads klasörüne erişim izni veren bir .htaccess dosyası oluşturur.

$htaccessContent = <<<EOT
<IfModule mod_authz_core.c>
    Require all granted
</IfModule>
<IfModule !mod_authz_core.c>
    Order Allow,Deny
    Allow from all
</IfModule>

# Guvenlik icin PHP calistirmayi engelle, ama dosya erisimine izin ver
<FilesMatch "\.(php|php5|phtml|html)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
EOT;

$baseDir = __DIR__ . '/uploads';

if (!file_exists($baseDir)) {
    mkdir($baseDir, 0755, true);
}

// Uploads ana klasörü
file_put_contents($baseDir . '/.htaccess', $htaccessContent);
echo "Created .htaccess in " . $baseDir . "\n";

// Alt klasörleri gez ve varsa oradaki htaccess'leri sil veya güncelle
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    if ($item->isDir()) {
        $path = $item->getPathname();
        // Alt klasöre de aynısını koyalım garanti olsun
        file_put_contents($path . '/.htaccess', $htaccessContent);
        echo "Created .htaccess in " . $path . "\n";
    }
}

echo "\nDone. Try accessing the file now.";
?>
