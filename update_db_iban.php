<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

try {
    // Check if column exists
    $check = $db->fetch("SHOW COLUMNS FROM kullanicilar LIKE 'iban'");
    if (!$check) {
        $db->query("ALTER TABLE kullanicilar ADD COLUMN iban VARCHAR(34) DEFAULT NULL");
        echo "IBAN column added successfully.";
    } else {
        echo "IBAN column already exists.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
