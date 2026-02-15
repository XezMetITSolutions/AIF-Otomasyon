<?php
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();
$res = $db->fetchAll("SELECT b.byk_kodu, b.byk_id, b.byk_adi, COUNT(k.kullanici_id) as count 
                     FROM byk b 
                     LEFT JOIN kullanicilar k ON b.byk_id = k.byk_id 
                     WHERE b.byk_kodu IN ('AT', 'GT', 'KGT', 'KT')
                     GROUP BY b.byk_id");
print_r($res);
