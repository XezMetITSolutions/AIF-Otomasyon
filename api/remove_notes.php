<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

try {
    $db->query("UPDATE istisare_oylama SET notlar = '' WHERE notlar = 'Kızlar beraber oy verdi'");
    echo "Notlar silindi!<br>";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
