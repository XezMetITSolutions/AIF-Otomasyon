<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

try {
    echo "Creating kullanici_byklar table...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS `kullanici_byklar` (
            `kullanici_id` INT NOT NULL,
            `byk_id` INT NOT NULL,
            PRIMARY KEY (`kullanici_id`, `byk_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "Migrating existing byk_ids...\n";
    $db->query("
        INSERT IGNORE INTO `kullanici_byklar` (kullanici_id, byk_id)
        SELECT kullanici_id, byk_id FROM kullanicilar WHERE byk_id IS NOT NULL
    ");

    echo "Successfully completed migration!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
