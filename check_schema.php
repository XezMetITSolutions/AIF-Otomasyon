<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "EXPLAIN kullanicilar:\n";
print_r($db->fetchAll("EXPLAIN kullanicilar"));

echo "\nSHOW TABLES LIKE 'kullanici_byklar':\n";
print_r($db->fetchAll("SHOW TABLES LIKE 'kullanici_byklar'"));
