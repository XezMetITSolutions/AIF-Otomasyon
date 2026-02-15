<?php
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();
$res = $db->fetchAll("SELECT byk_adi, byk_kodu, COUNT(*) as count FROM byk GROUP BY byk_kodu, byk_adi LIMIT 50");
$output = "BYK TABLE DATA:\n" . print_r($res, true) . "\n\n";

$res2 = $db->fetchAll("SELECT k.ad, k.soyad, b.byk_kodu, b.byk_adi FROM kullanicilar k JOIN byk b ON k.byk_id = b.byk_id LIMIT 20");
$output .= "USER BYK MAPPINGS:\n" . print_r($res2, true);

file_put_contents(__DIR__ . '/debug_output.txt', $output);
echo "Done";
