<?php
require 'includes/init.php';
$db = Database::getInstance();
$email = 'umut.burcak68@gmail.com';
$u = $db->fetch('SELECT kullanici_id FROM kullanicilar WHERE email = ?', [$email]);
if ($u) {
    echo "User ID: " . $u['kullanici_id'] . "\n";
    $z = $db->fetchAll('
        SELECT z.ziyaret_id, z.ziyaret_tarihi, z.durum, g.grup_adi, b.byk_adi 
        FROM sube_ziyaretleri z 
        INNER JOIN ziyaret_gruplari g ON z.grup_id = g.grup_id
        INNER JOIN byk b ON z.byk_id = b.byk_id
        INNER JOIN ziyaret_grup_uyeleri gu ON z.grup_id = gu.grup_id 
        WHERE gu.kullanici_id = ?
    ', [$u['kullanici_id']]);
    echo json_encode($z, JSON_PRETTY_PRINT);
} else {
    echo "User not found: " . $email;
}
