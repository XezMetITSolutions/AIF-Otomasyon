<?php
require_once 'includes/init.php';
$db = Database::getInstance();
header('Content-Type: application/json');

$results = $db->fetchAll("
    SELECT b.byk_kodu, b.byk_id, b.byk_adi, COUNT(k.kullanici_id) as user_count 
    FROM byk b 
    LEFT JOIN kullanicilar k ON b.byk_id = k.byk_id 
    WHERE b.byk_kodu IN ('AT', 'GT', 'KGT', 'KT')
    GROUP BY b.byk_id
");

echo json_encode($results);
