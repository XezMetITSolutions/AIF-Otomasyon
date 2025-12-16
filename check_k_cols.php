<?php
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();
try {
    $cols = $db->fetchAll("SHOW COLUMNS FROM toplanti_katilimcilar");
    foreach ($cols as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
