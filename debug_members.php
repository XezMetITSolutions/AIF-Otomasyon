<?php
require_once 'includes/init.php';
$db = Database::getInstance();
echo "BYK Table:\n";
print_r($db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk WHERE byk_kodu IN ('AT', 'GT', 'KGT', 'KT')"));

echo "\nUser Counts for these BYKs:\n";
print_r($db->fetchAll("
    SELECT b.byk_kodu, COUNT(k.kullanici_id) as count 
    FROM byk b 
    LEFT JOIN kullanicilar k ON b.byk_id = k.byk_id 
    WHERE b.byk_kodu IN ('AT', 'GT', 'KGT', 'KT') 
    GROUP BY b.byk_id
"));

echo "\nChecking if users have byk_id from byk_categories instead:\n";
try {
    print_r($db->fetchAll("
        SELECT bc.code, COUNT(k.kullanici_id) as count 
        FROM byk_categories bc
        LEFT JOIN kullanicilar k ON k.byk_category_id = bc.id
        WHERE bc.code IN ('AT', 'GT', 'KGT', 'KT')
        GROUP BY bc.id
    "));
} catch (Exception $e) {
    echo "byk_category_id column might not exist in kullanicilar.\n";
}
