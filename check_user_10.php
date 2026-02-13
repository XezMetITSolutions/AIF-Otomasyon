<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "User 10 details:\n";
$user10 = $db->fetch("SELECT k.*, r.rol_adi, r.rol_yetki_seviyesi FROM kullanicilar k LEFT JOIN roller r ON k.rol_id = r.rol_id WHERE k.kullanici_id = 10");
print_r($user10);

echo "\nAll super admins:\n";
$superAdmins = $db->fetchAll("SELECT k.kullanici_id, k.ad, k.soyad, r.rol_adi FROM kullanicilar k JOIN roller r ON k.rol_id = r.rol_id WHERE r.rol_adi = 'super_admin' OR r.rol_yetki_seviyesi >= 90");
print_r($superAdmins);
