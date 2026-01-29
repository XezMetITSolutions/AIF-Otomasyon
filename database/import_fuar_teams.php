<?php
/**
 * Fuar Projesi (ID=2) İçin Ekip ve Üye Import Scripti
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

// CLI kontrolü (opsiyonel, tarayıcıdan da çalıştırılabilir ama çıktı verir)
if (php_sapi_name() !== 'cli') {
    echo "<pre>";
}

$db = Database::getInstance();
$projectId = 2;

// Veri Seti
$teamsData = [
    'Fuar ana sorumluluğu' => [
        'Fikret Özcan', 
        'Sinan Yiğit', 
        'Hüseyin Ergül'
    ],
    'Stand sorumluları' => [
        'Sinan Yiğit', 
        'Ekrem Gürel', 
        'Enes Sivrikaya'
    ],
    'Sponsor ve Vilayet standları' => [
        'Volkan Meral', 
        'Ali Gümüş', 
        'Umut Burçak'
    ],
    'Otel Ev Pansiyon takibatı' => [
        'İbrahim Çetin', 
        'Mete Burçak', 
        'Umut Burçak'
    ],
    'Transfer Takibatı' => [
        'Hüseyin Akyıldız', 
        'Adem İmamoglu'
    ],
    'Tanıtım Reklam Takibatı' => [
        'Hüseyin Ayhan', 
        'Mahmut Yıldız', 
        'Ömer Canbaz', 
        'Muhammed Ayhan'
    ],
    'Sahne programları' => [
        'Hatice Armağan', 
        'Selda Avcı'
    ]
];

echo "Baslatiliyor... Proje ID: $projectId\n\n";

// Önce proje var mı kontrol et
$proje = $db->fetch("SELECT proje_id FROM projeler WHERE proje_id = ?", [$projectId]);
if (!$proje && $projectId != 0) { 
    // proje_id ve id karışıklığı olabilir, şemayı kontrol etmeyelim, direkt insert/select deneyelim
}

foreach ($teamsData as $teamTitle => $members) {
    echo "Ekip Olusturuluyor: $teamTitle\n";
    
    // 1. Ekibi Oluştur (Varsa ID'sini al)
    $existingTeam = $db->fetch("SELECT id FROM proje_ekipleri WHERE proje_id = ? AND baslik = ?", [$projectId, $teamTitle]);
    
    if ($existingTeam) {
        $teamId = $existingTeam['id'];
        echo "  - Zaten mevcut (ID: $teamId)\n";
    } else {
        $db->query("INSERT INTO proje_ekipleri (proje_id, baslik, aciklama) VALUES (?, ?, ?)", 
            [$projectId, $teamTitle, 'Otomatik oluşturuldu']);
        $teamId = $db->lastInsertId();
        echo "  - Yeni oluşturuldu (ID: $teamId)\n";
    }
    
    // 2. Üyeleri Ekle
    foreach ($members as $memberName) {
        // İsim Soyisim ayır veya direkt ara
        // Veritabanında ad ve soyad ayrı. 
        // Gelen string: "Ad Soyad" veya "Ad İkinciAd Soyad"
        
        // En basit yaklaşım: CONCAT ile arama
        $user = $db->fetch("SELECT kullanici_id, ad, soyad FROM kullanicilar WHERE CONCAT(ad, ' ', soyad) LIKE ?", ["%" . trim($memberName) . "%"]);
        
        if ($user) {
            $userId = $user['kullanici_id'];
            
            // Zaten ekli mi?
            $isMember = $db->fetch("SELECT id FROM proje_ekip_uyeleri WHERE ekip_id = ? AND kullanici_id = ?", [$teamId, $userId]);
            
            if (!$isMember) {
                $db->query("INSERT INTO proje_ekip_uyeleri (ekip_id, kullanici_id) VALUES (?, ?)", [$teamId, $userId]);
                echo "    + Eklendi: $memberName ({$user['ad']} {$user['soyad']})\n";
            } else {
                echo "    . Zaten uye: $memberName\n";
            }
        } else {
            echo "    ! BULUNAMADI: $memberName\n";
            // Alternatif arama: Son kelime soyad, kalanı ad
            $parts = explode(' ', $memberName);
            if (count($parts) >= 2) {
                $lastName = array_pop($parts);
                $firstName = implode(' ', $parts);
                $user2 = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE ad LIKE ? AND soyad LIKE ?", ["%$firstName%", "%$lastName%"]);
                if ($user2) {
                     $userId = $user2['kullanici_id'];
                     // Tekrar ekleme denemesi
                     $isMember = $db->fetch("SELECT id FROM proje_ekip_uyeleri WHERE ekip_id = ? AND kullanici_id = ?", [$teamId, $userId]);
                     if (!$isMember) {
                        $db->query("INSERT INTO proje_ekip_uyeleri (ekip_id, kullanici_id) VALUES (?, ?)", [$teamId, $userId]);
                        echo "    + Eklendi (Alternatif Arama): $memberName\n";
                     } else {
                         echo "    . Zaten uye (Alternatif): $memberName\n";
                     }
                }
            }
        }
    }
    echo "\n";
}

echo "Islem Tamamlandi.";
?>
