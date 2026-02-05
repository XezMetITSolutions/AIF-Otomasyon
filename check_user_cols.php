<?php
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();
try {
    $users = $db->fetchAll("SELECT kullanici_id, ad, soyad FROM kullanicilar");
    echo "Users in database:\n";
    foreach ($users as $user) {
        echo "  - ID: {$user['kullanici_id']}, Name: {$user['ad']} {$user['soyad']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
