<?php
require 'includes/init.php';
$db = Database::getInstance();
try {
    $rows = $db->fetchAll("DESCRIBE kullanicilar");
    print_r($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}
