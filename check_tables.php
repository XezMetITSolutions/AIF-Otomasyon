<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "=== Checking izin_talepleri table ===\n";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM izin_talepleri");
    echo "izin_talepleri columns:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Table izin_talepleri does not exist: " . $e->getMessage() . "\n";
}

echo "\n=== Checking harcama_talepleri table ===\n";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM harcama_talepleri");
    echo "harcama_talepleri columns:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Table harcama_talepleri does not exist: " . $e->getMessage() . "\n";
}

echo "\n=== Checking kullanicilar table (for approvers) ===\n";
try {
    $users = $db->fetchAll("SELECT kullanici_id, isim, soyisim, email FROM kullanicilar WHERE isim LIKE '%Yasin%' OR isim LIKE '%Muhammed%' OR soyisim LIKE '%Çakmak%' OR soyisim LIKE '%Sivrikaya%'");
    echo "Potential approvers:\n";
    foreach ($users as $user) {
        echo "  - ID: {$user['kullanici_id']}, Name: {$user['isim']} {$user['soyisim']}, Email: {$user['email']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>