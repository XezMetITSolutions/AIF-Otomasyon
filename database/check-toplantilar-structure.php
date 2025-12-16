<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

try {
    // toplantilar tablosunun yapısını göster
    $columns = $db->fetchAll("SHOW COLUMNS FROM toplantilar");
    
    echo "=== TOPLANTILAR TABLOSU YAPISI ===\n\n";
    foreach ($columns as $col) {
        echo "Field: {$col['Field']}\n";
        echo "Type: {$col['Type']}\n";
        echo "Null: {$col['Null']}\n";
        echo "Key: {$col['Key']}\n";
        echo "Default: {$col['Default']}\n";
        echo "Extra: {$col['Extra']}\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
