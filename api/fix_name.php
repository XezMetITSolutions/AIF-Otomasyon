<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

try {
    for ($i = 1; $i <= 5; $i++) {
        $db->query("UPDATE istisare_oylama SET secilen_$i = 'Seyit Evkaya' WHERE secilen_$i = 'Seyid Evkaya'");
    }
    echo "Isimler duzeltildi!<br>";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
