<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "--- Detailed Group Debug ---\n";
$gruplar = $db->fetchAll("SELECT * FROM ziyaret_gruplari");
foreach ($gruplar as $g) {
    echo "ID: " . $g['grup_id'] . "\n";
    echo "Name Value: '" . $g['grup_adi'] . "'\n";
    echo "Hex Name: " . bin2hex($g['grup_adi']) . "\n";
    echo "--------------------------\n";
}
