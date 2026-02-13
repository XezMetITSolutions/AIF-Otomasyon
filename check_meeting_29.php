<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

$id = 29;
echo "Meeting $id details:\n";
$toplanti = $db->fetch("SELECT * FROM toplantilar WHERE toplanti_id = ?", [$id]);
print_r($toplanti);

echo "\nBYK details:\n";
$byk = $db->fetch("SELECT * FROM byk WHERE byk_id = ?", [$toplanti['byk_id']]);
print_r($byk);
