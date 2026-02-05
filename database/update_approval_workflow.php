<?php
/**
 * Veritabanı Güncelleme: Onay Akışı için Yasin Çakmak ve Muhammed Enes Sivrikaya
 * 
 * İzin Talepleri: Yasin Çakmak onayı (tek seviye)
 * Harcama Talepleri: Yasin Çakmak → Muhammed Enes Sivrikaya (iki seviye)
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

echo "=== Onay Akışı Güncelleniyor ===\n\n";

try {
    // 1. İzin talepleri tablosunu kontrol et
    echo "1. İzin talepleri tablosunu kontrol ediliyor...\n";

    $izinColumns = $db->fetchAll("SHOW COLUMNS FROM izin_talepleri");
    $hasFirstApprover = false;

    foreach ($izinColumns as $col) {
        if ($col['Field'] === 'onaylayan_id') {
            $hasFirstApprover = true;
        }
    }

    if ($hasFirstApprover) {
        echo "   ✓ izin_talepleri tablosu zaten onaylayan_id içeriyor\n";
    }

    // 2. Harcama talepleri tablosunu kontrol et ve ikinci onay için kolonları ekle
    echo "\n2. Harcama talepleri tablosunu kontrol ediliyor...\n";

    $harcamaColumns = $db->fetchAll("SHOW COLUMNS FROM harcama_talepleri");
    $hasFirstApprover = false;
    $hasSecondApprover = false;
    $hasApprovalLevel = false;

    foreach ($harcamaColumns as $col) {
        if ($col['Field'] === 'ilk_onaylayan_id')
            $hasFirstApprover = true;
        if ($col['Field'] === 'ikinci_onaylayan_id')
            $hasSecondApprover = true;
        if ($col['Field'] === 'onay_seviyesi')
            $hasApprovalLevel = true;
    }

    // Harcama talepleri için yeni kolonlar ekle
    if (!$hasApprovalLevel) {
        echo "   + onay_seviyesi kolonu ekleniyor...\n";
        $db->query("
            ALTER TABLE harcama_talepleri 
            ADD COLUMN onay_seviyesi TINYINT DEFAULT 0 
            COMMENT '0=beklemede, 1=ilk onay, 2=ikinci onay (tamamlandı)'
        ");
    } else {
        echo "   ✓ onay_seviyesi zaten mevcut\n";
    }

    if (!$hasFirstApprover) {
        echo "   + ilk_onaylayan_id kolonu ekleniyor...\n";
        $db->query("
            ALTER TABLE harcama_talepleri 
            ADD COLUMN ilk_onaylayan_id INT DEFAULT NULL,
            ADD COLUMN ilk_onay_tarihi DATETIME DEFAULT NULL,
            ADD COLUMN ilk_onay_aciklama TEXT DEFAULT NULL
        ");
    } else {
        echo "   ✓ ilk_onaylayan_id zaten mevcut\n";
    }

    if (!$hasSecondApprover) {
        echo "   + ikinci_onaylayan_id kolonu ekleniyor...\n";
        $db->query("
            ALTER TABLE harcama_talepleri 
            ADD COLUMN ikinci_onaylayan_id INT DEFAULT NULL,
            ADD COLUMN ikinci_onay_tarihi DATETIME DEFAULT NULL,
            ADD COLUMN ikinci_onay_aciklama TEXT DEFAULT NULL
        ");
    } else {
        echo "   ✓ ikinci_onaylayan_id zaten mevcut\n";
    }

    // 3. Mevcut onaylayan_id verilerini ilk_onaylayan_id'ye kopyala
    if ($hasFirstApprover) {
        echo "\n3. Mevcut onay verilerini yeni yapıya taşıyoruz...\n";
        $db->query("
            UPDATE harcama_talepleri 
            SET ilk_onaylayan_id = onaylayan_id,
                ilk_onay_tarihi = onay_tarihi,
                ilk_onay_aciklama = onay_aciklama,
                onay_seviyesi = CASE 
                    WHEN durum = 'onaylandi' THEN 1
                    WHEN durum = 'beklemede' THEN 0
                    ELSE 0
                END
            WHERE onaylayan_id IS NOT NULL
        ");
        echo "   ✓ Mevcut onay verileri taşındı\n";
    }

    //4. Kullanıcıları bul
    echo "\n4. Onaylayıcı kullanıcılar kontrol ediliyor...\n";

    $firstApproverId = null;
    $firstApproverName = '';
    $secondApproverId = null;
    $secondApproverName = '';

    // Yasin Çakmak
    $yasinUser = $db->fetch("
        SELECT kullanici_id, ad, soyad, email 
        FROM kullanicilar 
        WHERE (ad LIKE '%Yasin%' OR ad LIKE '%yasin%') 
          AND (soyad LIKE '%Çakmak%' OR soyad LIKE '%Cakmak%')
        LIMIT 1
    ");

    if ($yasinUser) {
        echo "   ✓ Yasin Çakmak bulundu: {$yasinUser['ad']} {$yasinUser['soyad']} (ID: {$yasinUser['kullanici_id']})\n";
        $firstApproverId = $yasinUser['kullanici_id'];
        $firstApproverName = $yasinUser['ad'] . ' ' . $yasinUser['soyad'];
    } else {
        echo "   ⚠ Yasin Çakmak kullanıcısı bulunamadı. Manuel olarak config/approval_workflow.php'yi düzenleyin.\n";
    }

    // Muhammed Enes Sivrikaya
    $muhammedUser = $db->fetch("
        SELECT kullanici_id, ad, soyad, email 
        FROM kullanicilar 
        WHERE (ad LIKE '%Muhammed%' OR ad LIKE '%Enes%') 
          AND soyad LIKE '%Sivrikaya%'
        LIMIT 1
    ");

    if ($muhammedUser) {
        echo "   ✓ Muhammed Enes Sivrikaya bulundu: {$muhammedUser['ad']} {$muhammedUser['soyad']} (ID: {$muhammedUser['kullanici_id']})\n";
        $secondApproverId = $muhammedUser['kullanici_id'];
        $secondApproverName = $muhammedUser['ad'] . ' ' . $muhammedUser['soyad'];
    } else {
        echo "   ⚠ Muhammed Enes Sivrikaya kullanıcısı bulunamadı. Manuel olarak config/approval_workflow.php'yi düzenleyin.\n";
    }

    // 5. Özet bilgi
    echo "\n=== ÖZET ===\n";
    echo "İzin Talepleri Onay Akışı:\n";
    echo "  → Yasin Çakmak (ID: " . ($firstApproverId ?? 'BULUNAMADI') . ") → Onay/Red\n\n";

    echo "Harcama Talepleri Onay Akışı:\n";
    echo "  → 1. Seviye: Yasin Çakmak (ID: " . ($firstApproverId ?? 'BULUNAMADI') . ")\n";
    echo "  → 2. Seviye: Muhammed Enes Sivrikaya (ID: " . ($secondApproverId ?? 'BULUNAMADI') . ")\n";

    // 6. Onaylayıcı ID'lerini config dosyasına kaydet
    if ($firstApproverId || $secondApproverId) {
        $configData = [
            'approval_workflow' => [
                'izin_talepleri' => [
                    'approver_user_id' => $firstApproverId,
                    'approver_name' => $firstApproverName,
                ],
                'harcama_talepleri' => [
                    'first_approver_user_id' => $firstApproverId,
                    'first_approver_name' => $firstApproverName,
                    'second_approver_user_id' => $secondApproverId,
                    'second_approver_name' => $secondApproverName,
                ]
            ]
        ];

        $configFile = __DIR__ . '/../config/approval_workflow.php';
        $configContent = "<?php\n\nreturn " . var_export($configData, true) . ";\n";
        file_put_contents($configFile, $configContent);
        echo "\n✓ Onay konfigürasyonu kaydedildi: config/approval_workflow.php\n";
    }

    echo "\n✅ Veritabanı güncelleme tamamlandı!\n";

} catch (Exception $e) {
    echo "\n❌ HATA: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>