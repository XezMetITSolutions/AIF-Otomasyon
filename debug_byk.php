<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "--- Kullanicilar BYK_ID Samples ---\n";
$users = $db->fetchAll("SELECT ad, soyad, byk_id FROM kullanicilar LIMIT 5");
print_r($users);

echo "\n--- BYK Samples ---\n";
$byks = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk LIMIT 5");
print_r($byks);

echo "\n--- BYK Categories Samples ---\n";
try {
    $categories = $db->fetchAll("SELECT id, name, code FROM byk_categories LIMIT 5");
    print_r($categories);
} catch (Exception $e) {
    echo "byk_categories table does not exist.\n";
}
