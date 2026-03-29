<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

try {
    $db->query("UPDATE istisare_oylama SET sube_ismi = 'AIF Innsbruck' WHERE sube_ismi IS NULL OR sube_ismi = ''");
    echo "Eski oylarin subesi 'AIF Innsbruck' olarak güncellendi!<br>";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
