<?php
putenv('DB_HOST=127.0.0.1');
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();

echo "--- USERS ---\n";
$users = $db->fetchAll("SELECT kullanici_id, ad, soyad FROM kullanicilar");
foreach ($users as $u) {
    echo $u['kullanici_id'] . ": " . $u['ad'] . " " . $u['soyad'] . "\n";
}

echo "\n--- BYKS ---\n";
$byks = $db->fetchAll("SELECT byk_id, byk_adi FROM byk");
foreach ($byks as $b) {
    echo $b['byk_id'] . ": " . $b['byk_adi'] . "\n";
}
