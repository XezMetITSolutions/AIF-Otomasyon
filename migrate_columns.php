<?php
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();
try {
    $db->query("ALTER TABLE toplantilar ADD COLUMN sekreter_id INT NULL DEFAULT NULL");
    echo "Column sekreter_id added successfully (or already exists).\n";
} catch (Exception $e) {
    echo "Error adding column: " . $e->getMessage() . "\n";
}
try {
    $db->query("ALTER TABLE toplantilar ADD COLUMN baskan_degerlendirmesi TEXT NULL");
    echo "Column baskan_degerlendirmesi added successfully.\n";
} catch (Exception $e) {
    echo "Error adding column: " . $e->getMessage() . "\n";
}
try {
    $db->query("ALTER TABLE toplantilar ADD COLUMN bitis_tarihi DATETIME NULL AFTER toplanti_tarihi");
    echo "Column bitis_tarihi added successfully.\n";
} catch (Exception $e) {
    echo "Error adding column: " . $e->getMessage() . "\n";
}
?>