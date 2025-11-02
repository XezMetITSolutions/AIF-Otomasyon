<?php
/**
 * SQL Dosyasından Verileri Mevcut Tablolara Birleştirme (Unified Migration)
 * Aynı içerikli tabloları birleştirir (users = kullanicilar, events = etkinlikler, vb.)
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

// HTML output için header
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Unified Migration</title><style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style></head><body>";
    echo "<h1>Unified Migration Başlıyor...</h1><pre>";
}

$db = Database::getInstance();

echo "=== Unified Migration Başlıyor ===\n\n";
echo "Aynı içerikli tablolar birleştirilecek:\n";
echo "- users → kullanicilar (zaten aynı)\n";
echo "- events → etkinlikler (zaten aynı)\n";
echo "- announcements → duyurular (zaten aynı)\n";
echo "- meetings → toplantilar (zaten aynı)\n";
echo "- expenses → harcama_talepleri (zaten aynı)\n";
echo "- inventory → demirbaslar (zaten aynı)\n";
echo "- projects → projeler (zaten aynı)\n\n";

try {
    // 1. Users → Kullanicilar (Direkt birleştirme)
    echo "1. Users → Kullanicilar tablosuna birleştiriliyor...\n";
    
    try {
        // Önce users tablosu var mı kontrol et
        $usersExists = $db->fetch("SHOW TABLES LIKE 'users'");
        
        if ($usersExists) {
            // users tablosundaki tüm kullanıcıları kullanicilar tablosuna ekle
            $users = $db->fetchAll("SELECT * FROM users");
            $migrated = 0;
            $skipped = 0;
            
            foreach ($users as $user) {
                // E-posta kontrolü - zaten varsa atla
                $existing = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = ?", [$user['email']]);
                if ($existing) {
                    $skipped++;
                    continue;
                }
                
                // Rol ID'yi map et
                $rol_id = 3; // varsayılan: uye
                if ($user['role'] === 'superadmin') {
                    $rol_id = 1; // super_admin
                } elseif ($user['role'] === 'manager') {
                    $rol_id = 2; // baskan
                }
                
                // BYK ID'yi bul (byk_category_id'den)
                $byk_id = null;
                if ($user['byk_category_id']) {
                    try {
                        // byk_categories tablosundan code'u al
                        $bykCategory = $db->fetch("SELECT code FROM byk_categories WHERE id = ?", [$user['byk_category_id']]);
                        if ($bykCategory) {
                            // byk tablosundan ID'yi bul
                            $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$bykCategory['code']]);
                            if ($byk) {
                                $byk_id = $byk['byk_id'];
                            }
                        }
                    } catch (Exception $e) {
                        // BYK bulunamazsa null kalır
                    }
                }
                
                // Alt birim ID'yi bul
                $alt_birim_id = null;
                if ($user['sub_unit_id']) {
                    try {
                        $alt_birim = $db->fetch("SELECT alt_birim_id FROM alt_birimler WHERE alt_birim_id = ?", [$user['sub_unit_id']]);
                        if ($alt_birim) {
                            $alt_birim_id = $alt_birim['alt_birim_id'];
                        }
                    } catch (Exception $e) {
                        // Alt birim bulunamazsa null kalır
                    }
                }
                
                // Aktif durumu
                $aktif = ($user['status'] === 'active') ? 1 : 0;
                
                // Kullanıcıyı ekle
                $db->query("
                    INSERT INTO kullanicilar (
                        rol_id, byk_id, alt_birim_id, email, sifre, ad, soyad, 
                        telefon, aktif, ilk_giris_zorunlu, son_giris, olusturma_tarihi
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $rol_id,
                    $byk_id,
                    $alt_birim_id,
                    $user['email'],
                    $user['password_hash'],
                    $user['first_name'],
                    $user['last_name'],
                    null, // telefon
                    $aktif,
                    $user['must_change_password'] ?? 0,
                    $user['last_login'],
                    $user['created_at'] ?? date('Y-m-d H:i:s')
                ]);
                
                $migrated++;
                echo "   ✓ Kullanıcı eklendi: {$user['email']} ({$user['first_name']} {$user['last_name']})\n";
            }
            
            echo "   Toplam: {$migrated} kullanıcı eklendi, {$skipped} kullanıcı atlandı (zaten var)\n";
            
            // users tablosunu artık kullanmayacaksak silebiliriz (opsiyonel)
            echo "\n   ⚠ users tablosu hala mevcut. Gerekirse manuel olarak silebilirsiniz.\n";
            
        } else {
            echo "   ⚠ users tablosu bulunamadı, atlanıyor.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Hata: " . $e->getMessage() . "\n";
    }
    
    // 2. Events → Etkinlikler (Direkt birleştirme)
    echo "\n2. Events → Etkinlikler tablosuna birleştiriliyor...\n";
    
    try {
        $eventsExists = $db->fetch("SHOW TABLES LIKE 'events'");
        
        if ($eventsExists) {
            $events = $db->fetchAll("SELECT * FROM events");
            $migrated = 0;
            $skipped = 0;
            
            foreach ($events as $event) {
                // Başlık ve tarih kontrolü - aynı etkinlik zaten var mı?
                $existing = $db->fetch("
                    SELECT etkinlik_id FROM etkinlikler 
                    WHERE baslik = ? AND baslangic_tarihi = ?
                ", [$event['title'], $event['start_date']]);
                
                if ($existing) {
                    $skipped++;
                    continue;
                }
                
                // BYK ID'yi bul
                $byk_id = null;
                if (!empty($event['byk_category_id'])) {
                    try {
                        $bykCategory = $db->fetch("SELECT code FROM byk_categories WHERE id = ?", [$event['byk_category_id']]);
                        if ($bykCategory) {
                            $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$bykCategory['code']]);
                            if ($byk) {
                                $byk_id = $byk['byk_id'];
                            }
                        }
                    } catch (Exception $e) {
                        // BYK bulunamazsa devam et
                    }
                }
                
                // Eğer BYK bulunamadıysa varsayılan BYK'yi kontrol et
                if (!$byk_id) {
                    $defaultByk = $db->fetch("SELECT byk_id FROM byk WHERE byk_id = 1");
                    if ($defaultByk) {
                        $byk_id = 1;
                    } else {
                        $firstByk = $db->fetch("SELECT byk_id FROM byk ORDER BY byk_id LIMIT 1");
                        if ($firstByk) {
                            $byk_id = $firstByk['byk_id'];
                        } else {
                            echo "   ⚠ Etkinlik atlandı: {$event['title']} - BYK bulunamadı\n";
                            $skipped++;
                            continue;
                        }
                    }
                }
                
                // Oluşturan ID'yi bul
                $olusturan_id = 1; // Varsayılan admin
                if ($event['created_by']) {
                    $kullanici = $db->fetch("
                        SELECT kullanici_id FROM kullanicilar 
                        WHERE email = (SELECT email FROM users WHERE id = ?)
                    ", [$event['created_by']]);
                    if ($kullanici) {
                        $olusturan_id = $kullanici['kullanici_id'];
                    }
                }
                
                // Etkinliği ekle
                $db->query("
                    INSERT INTO etkinlikler (
                        byk_id, baslik, aciklama, baslangic_tarihi, bitis_tarihi,
                        konum, olusturan_id, olusturma_tarihi
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $byk_id,
                    $event['title'],
                    $event['description'] ?? null,
                    $event['start_date'],
                    $event['end_date'] ?? $event['start_date'],
                    $event['location'] ?? null,
                    $olusturan_id,
                    $event['created_at'] ?? date('Y-m-d H:i:s')
                ]);
                
                $migrated++;
                echo "   ✓ Etkinlik eklendi: {$event['title']}\n";
            }
            
            echo "   Toplam: {$migrated} etkinlik eklendi, {$skipped} etkinlik atlandı (zaten var)\n";
        } else {
            echo "   ⚠ events tablosu bulunamadı, atlanıyor.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Hata: " . $e->getMessage() . "\n";
    }
    
    // 3. Announcements → Duyurular (Direkt birleştirme)
    echo "\n3. Announcements → Duyurular tablosuna birleştiriliyor...\n";
    
    try {
        $announcementsExists = $db->fetch("SHOW TABLES LIKE 'announcements'");
        
        if ($announcementsExists) {
            $announcements = $db->fetchAll("SELECT * FROM announcements");
            $migrated = 0;
            $skipped = 0;
            
            foreach ($announcements as $announcement) {
                // Başlık kontrolü - aynı duyuru zaten var mı?
                $existing = $db->fetch("
                    SELECT duyuru_id FROM duyurular 
                    WHERE baslik = ? AND olusturma_tarihi = ?
                ", [$announcement['title'], $announcement['created_at']]);
                
                if ($existing) {
                    $skipped++;
                    continue;
                }
                
                // BYK ID'yi bul (target_audience'dan)
                $byk_id = 1; // Varsayılan
                if ($announcement['target_audience']) {
                    try {
                        // target_audience'da byk_code var mı kontrol et
                        $bykCodes = ['AT', 'KT', 'KGT', 'GT'];
                        foreach ($bykCodes as $code) {
                            if (stripos($announcement['target_audience'], $code) !== false) {
                                $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$code]);
                                if ($byk) {
                                    $byk_id = $byk['byk_id'];
                                    break;
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // BYK bulunamazsa varsayılan kullanılır
                    }
                }
                
                // Oluşturan ID'yi bul
                $olusturan_id = 1;
                if ($announcement['created_by']) {
                    $kullanici = $db->fetch("
                        SELECT kullanici_id FROM kullanicilar 
                        WHERE email = (SELECT email FROM users WHERE id = ?)
                    ", [$announcement['created_by']]);
                    if ($kullanici) {
                        $olusturan_id = $kullanici['kullanici_id'];
                    }
                }
                
                // Aktif durumu
                $aktif = ($announcement['status'] === 'active') ? 1 : 0;
                
                // Duyuruyu ekle
                $db->query("
                    INSERT INTO duyurular (
                        byk_id, baslik, icerik, olusturan_id, aktif, olusturma_tarihi
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ", [
                    $byk_id,
                    $announcement['title'],
                    $announcement['content'],
                    $olusturan_id,
                    $aktif,
                    $announcement['created_at'] ?? date('Y-m-d H:i:s')
                ]);
                
                $migrated++;
                echo "   ✓ Duyuru eklendi: {$announcement['title']}\n";
            }
            
            echo "   Toplam: {$migrated} duyuru eklendi, {$skipped} duyuru atlandı (zaten var)\n";
        } else {
            echo "   ⚠ announcements tablosu bulunamadı, atlanıyor.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Hata: " . $e->getMessage() . "\n";
    }
    
    // 4. Meetings → Toplantilar (Direkt birleştirme)
    echo "\n4. Meetings → Toplantilar tablosuna birleştiriliyor...\n";
    
    try {
        $meetingsExists = $db->fetch("SHOW TABLES LIKE 'meetings'");
        
        if ($meetingsExists) {
            $meetings = $db->fetchAll("SELECT * FROM meetings");
            $migrated = 0;
            $skipped = 0;
            
            foreach ($meetings as $meeting) {
                // Başlık ve tarih kontrolü
                $existing = $db->fetch("
                    SELECT toplanti_id FROM toplantilar 
                    WHERE baslik = ? AND toplanti_tarihi = ?
                ", [$meeting['title'], $meeting['meeting_date']]);
                
                if ($existing) {
                    $skipped++;
                    continue;
                }
                
                // BYK ID'yi bul
                $byk_id = null;
                if (!empty($meeting['byk_code'])) {
                    $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$meeting['byk_code']]);
                    if ($byk) {
                        $byk_id = $byk['byk_id'];
                    }
                }
                
                // Eğer byk_code yoksa veya bulunamadıysa, byk_category_id'den dene
                if (!$byk_id && !empty($meeting['byk_category_id'])) {
                    try {
                        $bykCategory = $db->fetch("SELECT code FROM byk_categories WHERE id = ?", [$meeting['byk_category_id']]);
                        if ($bykCategory) {
                            $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$bykCategory['code']]);
                            if ($byk) {
                                $byk_id = $byk['byk_id'];
                            }
                        }
                    } catch (Exception $e) {
                        // BYK category bulunamazsa devam et
                    }
                }
                
                // Hala bulunamadıysa varsayılan BYK'yi kontrol et
                if (!$byk_id) {
                    // Varsayılan BYK var mı kontrol et (ID = 1)
                    $defaultByk = $db->fetch("SELECT byk_id FROM byk WHERE byk_id = 1");
                    if ($defaultByk) {
                        $byk_id = 1;
                    } else {
                        // Hiç BYK yoksa ilk BYK'yi al
                        $firstByk = $db->fetch("SELECT byk_id FROM byk ORDER BY byk_id LIMIT 1");
                        if ($firstByk) {
                            $byk_id = $firstByk['byk_id'];
                        } else {
                            echo "   ⚠ Toplantı atlandı: {$meeting['title']} - BYK bulunamadı\n";
                            $skipped++;
                            continue;
                        }
                    }
                }
                
                // Oluşturan ID'yi bul
                $olusturan_id = 1;
                if ($meeting['chairman_id']) {
                    $kullanici = $db->fetch("
                        SELECT kullanici_id FROM kullanicilar 
                        WHERE email = (SELECT email FROM users WHERE id = ?)
                    ", [$meeting['chairman_id']]);
                    if ($kullanici) {
                        $olusturan_id = $kullanici['kullanici_id'];
                    }
                }
                
                // Durum mapping
                $durum_map = [
                    'planned' => 'planlandi',
                    'ongoing' => 'devam_ediyor',
                    'completed' => 'tamamlandi',
                    'cancelled' => 'iptal'
                ];
                $durum = $durum_map[$meeting['status']] ?? 'planlandi';
                
                // Toplantı türü mapping
                $toplanti_turu_map = [
                    'regular' => 'normal',
                    'emergency' => 'acil',
                    'special' => 'ozel'
                ];
                $toplanti_turu = $toplanti_turu_map[$meeting['meeting_type']] ?? 'normal';
                
                // Toplantıyı ekle
                try {
                    $db->query("
                        INSERT INTO toplantilar (
                            byk_id, baslik, aciklama, toplanti_tarihi, konum, gundem,
                            toplanti_turu, olusturan_id, durum, olusturma_tarihi
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ", [
                        $byk_id,
                        $meeting['title'],
                        $meeting['notes'] ?? null,
                        $meeting['meeting_date'],
                        $meeting['location'] ?? null,
                        is_string($meeting['agenda']) ? $meeting['agenda'] : null,
                        $toplanti_turu,
                        $olusturan_id,
                        $durum,
                        $meeting['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    
                    $migrated++;
                    echo "   ✓ Toplantı eklendi: {$meeting['title']}\n";
                } catch (Exception $e) {
                    echo "   ❌ Toplantı eklenemedi: {$meeting['title']} - " . $e->getMessage() . "\n";
                    $skipped++;
                }
            }
            
            echo "   Toplam: {$migrated} toplantı eklendi, {$skipped} toplantı atlandı/hata aldı\n";
        } else {
            echo "   ⚠ meetings tablosu bulunamadı, atlanıyor.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Hata: " . $e->getMessage() . "\n";
    }
    
    // 5. Expenses → Harcama_talepleri (Direkt birleştirme)
    echo "\n5. Expenses → Harcama_talepleri tablosuna birleştiriliyor...\n";
    
    try {
        $expensesExists = $db->fetch("SHOW TABLES LIKE 'expenses'");
        
        if ($expensesExists) {
            $expenses = $db->fetchAll("
                SELECT e.*, u.byk_category_id, bc.code as byk_code
                FROM expenses e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN byk_categories bc ON u.byk_category_id = bc.id
            ");
            
            $migrated = 0;
            $skipped = 0;
            $errors = 0;
            
            foreach ($expenses as $expense) {
                // Kullanıcı ID'yi bul
                $kullanici_id = 1; // Varsayılan
                if ($expense['user_id']) {
                    $kullanici = $db->fetch("
                        SELECT kullanici_id FROM kullanicilar 
                        WHERE email = (SELECT email FROM users WHERE id = ?)
                    ", [$expense['user_id']]);
                    if ($kullanici) {
                        $kullanici_id = $kullanici['kullanici_id'];
                    }
                }
                
                // BYK ID'yi bul
                $byk_id = null;
                if (!empty($expense['byk_code'])) {
                    $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$expense['byk_code']]);
                    if ($byk) {
                        $byk_id = $byk['byk_id'];
                    }
                }
                
                // Eğer BYK bulunamadıysa varsayılan BYK'yi kontrol et
                if (!$byk_id) {
                    $defaultByk = $db->fetch("SELECT byk_id FROM byk WHERE byk_id = 1");
                    if ($defaultByk) {
                        $byk_id = 1;
                    } else {
                        $firstByk = $db->fetch("SELECT byk_id FROM byk ORDER BY byk_id LIMIT 1");
                        if ($firstByk) {
                            $byk_id = $firstByk['byk_id'];
                        } else {
                            echo "   ⚠ Harcama talebi atlandı: #{$expense['id']} - BYK bulunamadı\n";
                            $skipped++;
                            continue;
                        }
                    }
                }
                
                // Durum mapping
                $durum_map = [
                    'pending' => 'beklemede',
                    'paid' => 'odenmistir',
                    'rejected' => 'reddedildi'
                ];
                $durum = $durum_map[$expense['status']] ?? 'beklemede';
                
                // Harcama talebini ekle
                $baslik = "Harcama Talebi #{$expense['id']}";
                $aciklama = "İsim: {$expense['isim']} {$expense['soyisim']}, IBAN: {$expense['iban']}";
                
                try {
                    $db->query("
                        INSERT INTO harcama_talepleri (
                            kullanici_id, byk_id, baslik, aciklama, tutar, durum, olusturma_tarihi
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ", [
                        $kullanici_id,
                        $byk_id,
                        $baslik,
                        $aciklama,
                        $expense['total'],
                        $durum,
                        $expense['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    
                    $migrated++;
                    echo "   ✓ Harcama talebi eklendi: #{$expense['id']}\n";
                } catch (Exception $e) {
                    $errors++;
                    echo "   ❌ Harcama talebi eklenemedi: #{$expense['id']} - " . $e->getMessage() . "\n";
                }
            }
            
            echo "   Toplam: {$migrated} harcama talebi eklendi";
            if ($skipped > 0) echo ", {$skipped} atlandı";
            if ($errors > 0) echo ", {$errors} hata";
            echo "\n";
        } else {
            echo "   ⚠ expenses tablosu bulunamadı, atlanıyor.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Hata: " . $e->getMessage() . "\n";
    }
    
    // 6. Inventory → Demirbaslar (Direkt birleştirme)
    echo "\n6. Inventory → Demirbaslar tablosuna birleştiriliyor...\n";
    
    try {
        $inventoryExists = $db->fetch("SHOW TABLES LIKE 'inventory'");
        
        if ($inventoryExists) {
            $inventory = $db->fetchAll("SELECT * FROM inventory");
            $migrated = 0;
            
            foreach ($inventory as $item) {
                // Durum mapping
                $durum_map = [
                    'active' => 'kullanimda',
                    'maintenance' => 'arizali',
                    'disposed' => 'depoda'
                ];
                $durum = $durum_map[$item['status']] ?? 'kullanimda';
                
                // Demirbaşı ekle
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
            
            echo "   Toplam: {$migrated} demirbaş eklendi\n";
        } else {
            echo "   ⚠ inventory tablosu bulunamadı, atlanıyor.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Hata: " . $e->getMessage() . "\n";
    }
    
    // 7. Projects → Projeler (Direkt birleştirme)
    echo "\n7. Projects → Projeler tablosuna birleştiriliyor...\n";
    
    try {
        $projectsExists = $db->fetch("SHOW TABLES LIKE 'projects'");
        
        if ($projectsExists) {
            $projects = $db->fetchAll("
                SELECT p.*, bc.code as byk_code, u.id as user_id
                FROM projects p
                LEFT JOIN byk_categories bc ON p.byk_category_id = bc.id
                LEFT JOIN users u ON p.created_by = u.id
            ");
            
            $migrated = 0;
            $skipped = 0;
            
            foreach ($projects as $project) {
                // Başlık kontrolü
                $existing = $db->fetch("
                    SELECT proje_id FROM projeler 
                    WHERE baslik = ? AND baslangic_tarihi = ?
                ", [$project['name'], $project['start_date'] ?? null]);
                
                if ($existing) {
                    $skipped++;
                    continue;
                }
                
                // BYK ID'yi bul
                $byk_id = null;
                if ($project['byk_code']) {
                    $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$project['byk_code']]);
                    if ($byk) {
                        $byk_id = $byk['byk_id'];
                    }
                }
                
                if (!$byk_id) {
                    $byk_id = 1; // Varsayılan
                }
                
                // Oluşturan ID'yi bul
                $olusturan_id = 1;
                if ($project['user_id']) {
                    $kullanici = $db->fetch("
                        SELECT kullanici_id FROM kullanicilar 
                        WHERE email = (SELECT email FROM users WHERE id = ?)
                    ", [$project['user_id']]);
                    if ($kullanici) {
                        $olusturan_id = $kullanici['kullanici_id'];
                    }
                }
                
                // Durum mapping
                $durum_map = [
                    'planning' => 'planlama',
                    'active' => 'aktif',
                    'on_hold' => 'beklemede',
                    'completed' => 'tamamlandi',
                    'cancelled' => 'iptal'
                ];
                $durum = $durum_map[$project['status']] ?? 'planlama';
                
                // Projeyi ekle
                $db->query("
                    INSERT INTO projeler (
                        byk_id, baslik, aciklama, baslangic_tarihi, bitis_tarihi,
                        durum, olusturan_id, olusturma_tarihi
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $byk_id,
                    $project['name'],
                    $project['description'] ?? null,
                    $project['start_date'],
                    $project['end_date'],
                    $durum,
                    $olusturan_id,
                    $project['created_at'] ?? date('Y-m-d H:i:s')
                ]);
                
                $migrated++;
                echo "   ✓ Proje eklendi: {$project['name']}\n";
            }
            
            echo "   Toplam: {$migrated} proje eklendi, {$skipped} proje atlandı (zaten var)\n";
        } else {
            echo "   ⚠ projects tablosu bulunamadı, atlanıyor.\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Hata: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== ✅ Unified Migration Tamamlandı! ===\n";
    echo "\nNot: Eski tablolar (users, events, announcements, vb.) hala mevcut.\n";
    echo "Veriler başarıyla birleştirildi. İsterseniz eski tabloları manuel olarak silebilirsiniz.\n";
    
} catch (Exception $e) {
    echo "\n❌ Genel Hata: " . $e->getMessage() . "\n";
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

