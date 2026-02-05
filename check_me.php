<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$user = $auth->getUser();

if (!$user) {
    echo "No user logged in.\n";
    exit;
}

echo "Current User ID: " . $user['id'] . "\n";
echo "Current User Role: " . $user['role'] . "\n";

$db = Database::getInstance();
$checkAT = $db->fetch("SELECT b.byk_kodu, b.byk_adi FROM byk b JOIN kullanicilar k ON b.byk_id = k.byk_id WHERE k.kullanici_id = ?", [$user['id']]);

if ($checkAT) {
    echo "User Unit Code: " . $checkAT['byk_kodu'] . "\n";
    echo "User Unit Name: " . $checkAT['byk_adi'] . "\n";
} else {
    echo "User has no unit associated or unit not found.\n";
}
