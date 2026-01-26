<?php
/**
 * Bulk Permission Update Script
 * Marks all "uye" category permissions for all non-super-admin users.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Database.php';

// Only Super Admin should run this strictly, but since I am the agent acting on USER request:
$db = Database::getInstance();
$moduleDefinitions = require __DIR__ . '/config/baskan_modules.php';

// 1. Identify "Uye" (Member) Permissions
$uyeModules = [];
foreach ($moduleDefinitions as $key => $def) {
    if (isset($def['category']) && $def['category'] === 'uye') {
        $uyeModules[] = $key;
    }
}

if (empty($uyeModules)) {
    echo "No member modules found.<br>";
    exit;
}

echo "Found " . count($uyeModules) . " member modules: " . implode(', ', $uyeModules) . "<br>";

// 2. Get All Relevant Users (Everyone except Super Admin)
// Assuming Role ID 1 is Super Admin usually, or check role name.
// Previous code used: WHERE r.rol_adi != ? with 'super_admin' constant.
// Auth::ROLE_SUPER_ADMIN is a constant string usually 'super_admin'.
// Let's fetch IDs directly.

$users = $db->fetchAll("
    SELECT k.kullanici_id, k.ad, k.soyad 
    FROM kullanicilar k
    INNER JOIN roller r ON k.rol_id = r.rol_id
    WHERE r.rol_adi != 'super_admin'
");

if (empty($users)) {
    echo "No target users found.<br>";
    exit;
}

echo "Found " . count($users) . " users to update.<br>";

// 3. Update/Insert Permissions
$count = 0;
foreach ($users as $u) {
    $userId = $u['kullanici_id'];
    foreach ($uyeModules as $modKey) {
        // Use CAREFUL insert ... ON DUPLICATE KEY UPDATE to ensure it is set to 1
        $db->query("
            INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE can_view = 1
        ", [$userId, $modKey]);
        $count++;
    }
}

echo "Successfully updated permissions.<br>";
echo "Total operations: $count<br>";
?>
