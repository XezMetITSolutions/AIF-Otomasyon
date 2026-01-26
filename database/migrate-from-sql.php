<?php
/**
 * SQL Dosyasından Verileri Mevcut Tablolara Migration Scripti
 * d0451622.sql dosyasındaki verileri mevcut tablo yapısına uyarlar
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

// HTML output için header
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migration</title><style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;}</style></head><body>";
    echo "<h1>Migration Başlıyor...</h1><pre>";
}

$db = Database::getInstance();

echo "Migration başlıyor...\n\n";

try {
    // 1. Rolleri migrate et (varsa)
    echo "1. Roller kontrol ediliyor...\n";
    $existingRoles = $db->fetchAll("SELECT rol_adi FROM roller");
    $existingRoleNames = array_column($existingRoles, 'rol_adi');
    
    if (!in_array('super_admin', $existingRoleNames)) {
        $db->query("INSERT INTO roller (rol_adi, rol_aciklama, rol_yetki_seviyesi) VALUES ('super_admin', 'Ana Yönetici', 3)");
        echo "   ✓ super_admin rolü eklendi\n";
    }
    if (!in_array('uye', $existingRoleNames)) {
        $db->query("INSERT INTO roller (rol_adi, rol_aciklama, rol_yetki_seviyesi) VALUES ('uye', 'Başkan', 2)");
        echo "   ✓ baskan rolü eklendi\n";
    }
    if (!in_array('uye', $existingRoleNames)) {
        $db->query("INSERT INTO roller (rol_adi, rol_aciklama, rol_yetki_seviyesi) VALUES ('uye', 'Üye', 1)");
        echo "   ✓ uye rolü eklendi\n";
    }
    
    // 2. BYK Categories -> BYK tablosuna migrate
    echo "\n2. BYK kategorileri kontrol ediliyor...\n";
    $bykCategories = $db->fetchAll("SELECT * FROM byk_categories WHERE code IN ('AT', 'KT', 'KGT', 'GT')");
    
    foreach ($bykCategories as $category) {
        $existing = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$category['code']]);
        if (!$existing) {
            $rol_id = 1; // super_admin için
            if ($category['code'] === 'KT' || $category['code'] === 'KGT' || $category['code'] === 'GT') {
                $rol_id = 2; // baskan için
            }
            
            $db->query("
                INSERT INTO byk (byk_adi, byk_kodu, renk_kodu, aciklama, aktif) 
                VALUES (?, ?, ?, ?, 1)
            ", [$category['name'], $category['code'], $category['color'], $category['description']]);
            echo "   ✓ BYK eklendi: {$category['name']} ({$category['code']})\n";
        }
    }
    
    // 3. Users -> Kullanicilar tablosuna migrate
    echo "\n3. Kullanıcılar migrate ediliyor...\n";
    
    // users tablosu var mı kontrol et
    try {
        $users = $db->fetchAll("
            SELECT u.*, 
                   CASE u.role
                       WHEN 'superadmin' THEN 1
                       WHEN 'manager' THEN 2
                       WHEN 'member' THEN 3
                       ELSE 3
                   END as mapped_rol_id,
                   bc.id as byk_category_id
            FROM users u
            LEFT JOIN byk_categories bc ON u.byk_category_id = bc.id
        ");
    } catch (Exception $e) {
        echo "   ⚠ Users tablosu bulunamadı, atlanıyor: " . $e->getMessage() . "\n";
        $users = [];
    }
    
    $migrated = 0;
    foreach ($users as $user) {
        // E-posta kontrolü
        $existing = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = ?", [$user['email']]);
        if ($existing) {
            continue; // Zaten var
        }
        
        // BYK ID'yi bul
        $byk_id = null;
        if ($user['byk_category_id']) {
            $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = (SELECT code FROM byk_categories WHERE id = ?)", [$user['byk_category_id']]);
            if ($byk) {
                $byk_id = $byk['byk_id'];
            }
        }
        
        // Alt birim ID'yi bul
        $alt_birim_id = null;
        if ($user['sub_unit_id']) {
            $alt_birim = $db->fetch("SELECT alt_birim_id FROM alt_birimler WHERE alt_birim_id = ?", [$user['sub_unit_id']]);
            if ($alt_birim) {
                $alt_birim_id = $alt_birim['alt_birim_id'];
            }
        }
        
        // Kullanıcıyı ekle
        $db->query("
            INSERT INTO kullanicilar (
                rol_id, byk_id, alt_birim_id, email, sifre, ad, soyad, telefon, 
                aktif, ilk_giris_zorunlu, son_giris, olusturma_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $user['mapped_rol_id'],
            $byk_id,
            $alt_birim_id,
            $user['email'],
            $user['password_hash'],
            $user['first_name'],
            $user['last_name'],
            null,
            $user['status'] === 'active' ? 1 : 0,
            $user['must_change_password'] ?? 0,
            $user['last_login'],
            $user['created_at']
        ]);
        
        $migrated++;
        echo "   ✓ Kullanıcı eklendi: {$user['email']}\n";
    }
    echo "   Toplam {$migrated} kullanıcı migrate edildi\n";
    
    // 4. Events -> Etkinlikler tablosuna migrate
    echo "\n4. Etkinlikler migrate ediliyor...\n";
    
    try {
        $events = $db->fetchAll("
            SELECT e.*, bc.code as byk_code, u.id as user_id
            FROM events e
            LEFT JOIN byk_categories bc ON e.byk_category_id = bc.id
            LEFT JOIN users u ON e.created_by = u.id
        ");
    } catch (Exception $e) {
        echo "   ⚠ Events tablosu bulunamadı, atlanıyor: " . $e->getMessage() . "\n";
        $events = [];
    }
    
    $migrated = 0;
    foreach ($events as $event) {
        // BYK ID'yi bul
        $byk_id = null;
        if ($event['byk_code']) {
            $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$event['byk_code']]);
            if ($byk) {
                $byk_id = $byk['byk_id'];
            }
        }
        
        // Oluşturan kullanıcı ID'yi bul
        $olusturan_id = 1; // Varsayılan admin
        if ($event['user_id']) {
            $kullanici = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = (SELECT email FROM users WHERE id = ?)", [$event['user_id']]);
            if ($kullanici) {
                $olusturan_id = $kullanici['kullanici_id'];
            }
        }
        
        if (!$byk_id) {
            continue; // BYK bulunamadıysa atla
        }
        
        $db->query("
            INSERT INTO etkinlikler (
                byk_id, baslik, aciklama, baslangic_tarihi, bitis_tarihi, 
                konum, olusturan_id, durum, olusturma_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'planlandi', ?)
        ", [
            $byk_id,
            $event['title'],
            $event['description'],
            $event['start_date'],
            $event['end_date'] ?? $event['start_date'],
            $event['location'],
            $olusturan_id,
            $event['created_at']
        ]);
        
        $migrated++;
        echo "   ✓ Etkinlik eklendi: {$event['title']}\n";
    }
    echo "   Toplam {$migrated} etkinlik migrate edildi\n";
    
    // 5. Announcements -> Duyurular tablosuna migrate
    echo "\n5. Duyurular migrate ediliyor...\n";
    
    try {
        $announcements = $db->fetchAll("
            SELECT a.*, bc.code as byk_code, u.id as user_id
            FROM announcements a
            LEFT JOIN byk_categories bc ON a.target_audience LIKE CONCAT('%', bc.code, '%')
            LEFT JOIN users u ON a.created_by = u.id
        ");
    } catch (Exception $e) {
        echo "   ⚠ Announcements tablosu bulunamadı, atlanıyor: " . $e->getMessage() . "\n";
        $announcements = [];
    }
    
    $migrated = 0;
    foreach ($announcements as $announcement) {
        // BYK ID'yi bul (target_audience'a göre)
        $byk_id = 1; // Varsayılan
        if ($announcement['byk_code']) {
            $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$announcement['byk_code']]);
            if ($byk) {
                $byk_id = $byk['byk_id'];
            }
        }
        
        // Oluşturan kullanıcı ID'yi bul
        $olusturan_id = 1;
        if ($announcement['user_id']) {
            $kullanici = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = (SELECT email FROM users WHERE id = ?)", [$announcement['user_id']]);
            if ($kullanici) {
                $olusturan_id = $kullanici['kullanici_id'];
            }
        }
        
        $db->query("
            INSERT INTO duyurular (
                byk_id, baslik, icerik, olusturan_id, aktif, olusturma_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $byk_id,
            $announcement['title'],
            $announcement['content'],
            $olusturan_id,
            $announcement['status'] === 'active' ? 1 : 0,
            $announcement['created_at']
        ]);
        
        $migrated++;
        echo "   ✓ Duyuru eklendi: {$announcement['title']}\n";
    }
    echo "   Toplam {$migrated} duyuru migrate edildi\n";
    
    // 6. Meetings -> Toplantilar tablosuna migrate
    echo "\n6. Toplantılar migrate ediliyor...\n";
    
    try {
        $meetings = $db->fetchAll("SELECT * FROM meetings");
    } catch (Exception $e) {
        echo "   ⚠ Meetings tablosu bulunamadı, atlanıyor: " . $e->getMessage() . "\n";
        $meetings = [];
    }
    
    $migrated = 0;
    foreach ($meetings as $meeting) {
        // BYK ID'yi bul
        $byk_id = 1; // Varsayılan
        if ($meeting['byk_code']) {
            $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$meeting['byk_code']]);
            if ($byk) {
                $byk_id = $byk['byk_id'];
            }
        }
        
        // Oluşturan kullanıcı ID'yi bul
        $olusturan_id = 1;
        if ($meeting['chairman_id']) {
            $kullanici = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = (SELECT email FROM users WHERE id = ?)", [$meeting['chairman_id']]);
            if ($kullanici) {
                $olusturan_id = $kullanici['kullanici_id'];
            }
        }
        
        $db->query("
            INSERT INTO toplantilar (
                byk_id, baslik, aciklama, toplanti_tarihi, konum, gundem, 
                toplanti_turu, olusturan_id, durum, olusturma_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $byk_id,
            $meeting['title'],
            $meeting['notes'],
            $meeting['meeting_date'],
            $meeting['location'],
            $meeting['agenda'],
            $meeting['meeting_type'] ?? 'normal',
            $olusturan_id,
            $meeting['status'] ?? 'planlandi',
            $meeting['created_at']
        ]);
        
        $migrated++;
        echo "   ✓ Toplantı eklendi: {$meeting['title']}\n";
    }
    echo "   Toplam {$migrated} toplantı migrate edildi\n";
    
    // 7. Expenses -> Harcama_talepleri tablosuna migrate
    echo "\n7. Harcama talepleri migrate ediliyor...\n";
    
    try {
        $expenses = $db->fetchAll("
            SELECT e.*, u.id as user_id, bc.code as byk_code
            FROM expenses e
            LEFT JOIN users u ON e.user_id = u.id
            LEFT JOIN users u2 ON e.user_id = u2.id
            LEFT JOIN byk_categories bc ON u2.byk_category_id = bc.id
        ");
    } catch (Exception $e) {
        echo "   ⚠ Expenses tablosu bulunamadı, atlanıyor: " . $e->getMessage() . "\n";
        $expenses = [];
    }
    
    $migrated = 0;
    foreach ($expenses as $expense) {
        // Kullanıcı ID'yi bul
        $kullanici_id = 1;
        if ($expense['user_id']) {
            $kullanici = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = (SELECT email FROM users WHERE id = ?)", [$expense['user_id']]);
            if ($kullanici) {
                $kullanici_id = $kullanici['kullanici_id'];
            }
        }
        
        // BYK ID'yi bul
        $byk_id = 1;
        if ($expense['byk_code']) {
            $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$expense['byk_code']]);
            if ($byk) {
                $byk_id = $byk['byk_id'];
            }
        }
        
        $durum_map = [
            'pending' => 'beklemede',
            'paid' => 'odenmistir',
            'rejected' => 'reddedildi'
        ];
        $durum = $durum_map[$expense['status']] ?? 'beklemede';
        
        $db->query("
            INSERT INTO harcama_talepleri (
                kullanici_id, byk_id, baslik, aciklama, tutar, durum, olusturma_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [
            $kullanici_id,
            $byk_id,
            "Harcama Talebi #{$expense['id']}",
            "İsim: {$expense['isim']} {$expense['soyisim']}, IBAN: {$expense['iban']}",
            $expense['total'],
            $durum,
            $expense['created_at']
        ]);
        
        $migrated++;
        echo "   ✓ Harcama talebi eklendi: #{$expense['id']}\n";
    }
    echo "   Toplam {$migrated} harcama talebi migrate edildi\n";
    
    // 8. Inventory -> Demirbaslar tablosuna migrate
    echo "\n8. Demirbaşlar migrate ediliyor...\n";
    
    try {
        $inventory = $db->fetchAll("SELECT * FROM inventory");
    } catch (Exception $e) {
        echo "   ⚠ Inventory tablosu bulunamadı, atlanıyor: " . $e->getMessage() . "\n";
        $inventory = [];
    }
    
    $migrated = 0;
    foreach ($inventory as $item) {
        // Kullanıcı ID'yi bul (responsible_user_id için)
        $kullanici_id = null;
        if ($item['responsible_user_id']) {
            $kullanici = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = (SELECT email FROM users WHERE id = ?)", [$item['responsible_user_id']]);
            if ($kullanici) {
                $kullanici_id = $kullanici['kullanici_id'];
            }
        }
        
        $durum_map = [
            'active' => 'kullanimda',
            'maintenance' => 'arizali',
            'disposed' => 'depoda'
        ];
        $durum = $durum_map[$item['status']] ?? 'kullanimda';
        
            $db->query("
            INSERT INTO demirbaslar (
                demirbas_adi, kategori, seri_no, durum, aciklama, olusturma_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $item['name'],
            $item['category'],
            $item['serial_number'] ?? null,
            $durum,
            $item['description'] ?? null,
            $item['created_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        $migrated++;
        echo "   ✓ Demirbaş eklendi: {$item['name']}\n";
    }
    echo "   Toplam {$migrated} demirbaş migrate edildi\n";
    
    // 9. Projects -> Projeler tablosuna migrate
    echo "\n9. Projeler migrate ediliyor...\n";
    
    try {
        $projects = $db->fetchAll("
            SELECT p.*, bc.code as byk_code, u.id as user_id
            FROM projects p
            LEFT JOIN byk_categories bc ON p.byk_category_id = bc.id
            LEFT JOIN users u ON p.created_by = u.id
        ");
    } catch (Exception $e) {
        echo "   ⚠ Projects tablosu bulunamadı, atlanıyor: " . $e->getMessage() . "\n";
        $projects = [];
    }
    
    $migrated = 0;
    foreach ($projects as $project) {
        // BYK ID'yi bul
        $byk_id = null;
        if ($project['byk_code']) {
            $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$project['byk_code']]);
            if ($byk) {
                $byk_id = $byk['byk_id'];
            }
        }
        
        // Oluşturan kullanıcı ID'yi bul
        $olusturan_id = 1;
        if ($project['user_id']) {
            $kullanici = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = (SELECT email FROM users WHERE id = ?)", [$project['user_id']]);
            if ($kullanici) {
                $olusturan_id = $kullanici['kullanici_id'];
            }
        }
        
        $durum_map = [
            'planning' => 'planlama',
            'active' => 'aktif',
            'on_hold' => 'beklemede',
            'completed' => 'tamamlandi',
            'cancelled' => 'iptal'
        ];
        $durum = $durum_map[$project['status']] ?? 'planlama';
        
        $db->query("
            INSERT INTO projeler (
                byk_id, baslik, aciklama, baslangic_tarihi, bitis_tarihi, 
                durum, olusturan_id, olusturma_tarihi
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $byk_id,
            $project['name'],
            $project['description'],
            $project['start_date'],
            $project['end_date'],
            $durum,
            $olusturan_id,
            $project['created_at']
        ]);
        
        $migrated++;
        echo "   ✓ Proje eklendi: {$project['name']}\n";
    }
    echo "   Toplam {$migrated} proje migrate edildi\n";
    
    echo "\n✅ Migration tamamlandı!\n";
    
} catch (Exception $e) {
    echo "\n❌ Hata: " . $e->getMessage() . "\n";
    echo "Satır: " . $e->getLine() . "\n";
    echo "Dosya: " . $e->getFile() . "\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "</pre></body></html>";
    }
    
    exit(1);
}

// HTML output için footer
if (php_sapi_name() !== 'cli') {
    echo "</pre><p><a href='/admin/dashboard.php'>Dashboard'a Dön</a></p></body></html>";
}

