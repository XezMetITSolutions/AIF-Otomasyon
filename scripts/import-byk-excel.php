<?php
/**
 * BYK.xlsx dosyasını analiz edip byk_categories tablosuna import eden script
 * 
 * Kullanım: php scripts/import-byk-excel.php
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

// Excel dosyasını okumak için PhpSpreadsheet kütüphanesi gerekli
// Alternatif: PHP'nin built-in fonksiyonlarıyla CSV'ye çevirip okuyalım

echo "📊 BYK.xlsx Dosyasını Analiz ve Import\n";
echo str_repeat("=", 50) . "\n\n";

$excelFile = __DIR__ . '/../BYK.xlsx';
$db = Database::getInstance();

// Excel dosyası var mı kontrol et
if (!file_exists($excelFile)) {
    die("❌ Hata: BYK.xlsx dosyası bulunamadı!\n");
}

echo "✅ BYK.xlsx dosyası bulundu.\n\n";

// Excel dosyasını okumak için basit bir yaklaşım
// Not: Gerçek Excel okuma için PhpSpreadsheet veya SimpleXLSX gerekli
// Bu script CSV formatını bekliyor, Excel'i önce CSV'ye çevirin

echo "⚠️  Not: Excel dosyası okumak için PhpSpreadsheet kütüphanesi gerekli.\n";
echo "Şimdilik dosya içeriğini manuel olarak kontrol edip SQL import scripti oluşturalım.\n\n";

// Alternatif: Kullanıcıdan Excel içeriğini CSV olarak alalım
// Veya Excel dosyasını manuel olarak CSV'ye çevirip import edelim

echo "📝 Excel dosyasını analiz etmek için:\n";
echo "1. Excel dosyasını CSV formatına çevirin\n";
echo "2. CSV dosyasını scripts/BYK.csv olarak kaydedin\n";
echo "3. Bu scripti tekrar çalıştırın\n\n";

// CSV dosyasını kontrol et
$csvFile = __DIR__ . '/../scripts/BYK.csv';
if (file_exists($csvFile)) {
    echo "✅ CSV dosyası bulundu, import başlıyor...\n\n";
    
    $handle = fopen($csvFile, 'r');
    if ($handle === false) {
        die("❌ Hata: CSV dosyası okunamadı!\n");
    }
    
    $imported = 0;
    $skipped = 0;
    $lineNumber = 0;
    
    while (($row = fgetcsv($handle)) !== false) {
        $lineNumber++;
        
        // Başlık satırını atla
        if ($lineNumber === 1) {
            echo "📋 Başlık satırı: " . implode(', ', $row) . "\n\n";
            continue;
        }
        
        // Satır boşsa atla
        if (empty($row) || empty(array_filter($row))) {
            continue;
        }
        
        // CSV formatı bekleniyor: code, name, color, description
        $code = trim($row[0] ?? '');
        $name = trim($row[1] ?? '');
        $color = trim($row[2] ?? '#009872');
        $description = trim($row[3] ?? '');
        
        if (empty($code) || empty($name)) {
            echo "⚠️  Satır {$lineNumber} atlandı: Kod veya ad eksik\n";
            $skipped++;
            continue;
        }
        
        // Renk kodu formatını kontrol et
        if (!empty($color) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            echo "⚠️  Satır {$lineNumber}: Geçersiz renk kodu '{$color}', varsayılan kullanılıyor (#009872)\n";
            $color = '#009872';
        }
        
        try {
            // Aynı kod var mı kontrol et
            $existing = $db->fetch("SELECT id FROM byk_categories WHERE code = ?", [$code]);
            
            if ($existing) {
                echo "⏭️  BYK zaten mevcut: {$code} - {$name}\n";
                $skipped++;
            } else {
                // Yeni BYK ekle
                $db->query("
                    INSERT INTO byk_categories (code, name, color, description, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ", [$code, $name, $color, $description]);
                
                echo "✅ BYK eklendi: {$code} - {$name}\n";
                $imported++;
            }
        } catch (Exception $e) {
            echo "❌ Satır {$lineNumber} hatası: " . $e->getMessage() . "\n";
            $skipped++;
        }
    }
    
    fclose($handle);
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Import Tamamlandı!\n";
    echo "   - {$imported} BYK eklendi\n";
    echo "   - {$skipped} BYK atlandı (zaten var veya hata)\n\n";
    
} else {
    echo "📋 Excel dosyasını CSV'ye çevirmek için:\n\n";
    echo "1. Excel'de BYK.xlsx dosyasını açın\n";
    echo "2. 'Farklı Kaydet' → 'CSV (Virgülle Ayrılmış) (*.csv)' seçin\n";
    echo "3. Dosyayı 'scripts/BYK.csv' olarak kaydedin\n";
    echo "4. Bu scripti tekrar çalıştırın\n\n";
    
    echo "📝 Beklenen CSV Formatı:\n";
    echo "code,name,color,description\n";
    echo "AT,Ana Teşkilat,#dc3545,Ana teşkilat birimi\n";
    echo "KT,Kadınlar Teşkilatı,#6f42c1,Kadınlar teşkilatı birimi\n";
    echo "...\n\n";
}

