<?php
/**
 * AIF Otomasyon - Rol ENUM Güncelleme Scripti
 * Bu script users tablosundaki role ENUM'unu günceller
 */

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Rol ENUM Güncelleme</title></head><body>";
echo "<h2>AIF Otomasyon - Rol ENUM Güncelleme</h2>";

try {
    // Veritabanı bağlantısını test et
    require_once 'includes/database.php';
    $db = Database::getInstance();
    echo "✓ Veritabanı bağlantısı başarılı<br>";
    
    echo "<h3>1. Mevcut Rol Yapısı Kontrol Ediliyor...</h3>";
    
    // Mevcut ENUM değerlerini kontrol et
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    $column = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "Mevcut role ENUM: " . $column['Type'] . "<br>";
        
        // ENUM değerlerini parse et
        preg_match_all("/'([^']+)'/", $column['Type'], $matches);
        $currentValues = $matches[1];
        echo "Mevcut değerler: " . implode(', ', $currentValues) . "<br>";
        
        // Manager rolü var mı kontrol et
        if (in_array('manager', $currentValues)) {
            echo "✓ Manager rolü zaten mevcut<br>";
        } else {
            echo "⚠ Manager rolü bulunamadı, ekleniyor...<br>";
            
            // Admin rolünü manager olarak değiştir
            echo "<h3>2. Admin Rollerini Manager Olarak Güncelleniyor...</h3>";
            
            // Önce admin rolündeki kullanıcıları manager olarak güncelle
            $updateResult = $db->query("UPDATE users SET role = 'manager' WHERE role = 'admin'");
            $affectedRows = $updateResult->rowCount();
            echo "✓ $affectedRows kullanıcının rolü admin'den manager'a güncellendi<br>";
            
            // ENUM'u güncelle
            echo "<h3>3. ENUM Değerleri Güncelleniyor...</h3>";
            
            $alterQuery = "ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'manager', 'member') DEFAULT 'member'";
            $db->query($alterQuery);
            echo "✓ Role ENUM güncellendi: superadmin, manager, member<br>";
            
            // Güncellenmiş ENUM değerlerini kontrol et
            $result = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
            $column = $result->fetch(PDO::FETCH_ASSOC);
            echo "Yeni role ENUM: " . $column['Type'] . "<br>";
        }
        
        // Kullanıcı sayılarını göster
        echo "<h3>4. Rol Dağılımı:</h3>";
        $roleStats = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        while ($stat = $roleStats->fetch(PDO::FETCH_ASSOC)) {
            echo "• " . ucfirst($stat['role']) . ": " . $stat['count'] . " kullanıcı<br>";
        }
        
    } else {
        echo "❌ Users tablosunda role sütunu bulunamadı<br>";
    }
    
    echo "<h3>5. İşlem Tamamlandı!</h3>";
    echo "<p style='color: green; font-weight: bold;'>✓ Rol sistemi başarıyla güncellendi</p>";
    echo "<p><a href='dashboard_superadmin.php'>Dashboard'a Dön</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Hata Oluştu:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Hata detayları: " . htmlspecialchars($e->getTraceAsString()) . "</p>";
}

echo "</body></html>";
?>

 * AIF Otomasyon - Rol ENUM Güncelleme Scripti
 * Bu script users tablosundaki role ENUM'unu günceller
 */

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Rol ENUM Güncelleme</title></head><body>";
echo "<h2>AIF Otomasyon - Rol ENUM Güncelleme</h2>";

try {
    // Veritabanı bağlantısını test et
    require_once 'includes/database.php';
    $db = Database::getInstance();
    echo "✓ Veritabanı bağlantısı başarılı<br>";
    
    echo "<h3>1. Mevcut Rol Yapısı Kontrol Ediliyor...</h3>";
    
    // Mevcut ENUM değerlerini kontrol et
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    $column = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "Mevcut role ENUM: " . $column['Type'] . "<br>";
        
        // ENUM değerlerini parse et
        preg_match_all("/'([^']+)'/", $column['Type'], $matches);
        $currentValues = $matches[1];
        echo "Mevcut değerler: " . implode(', ', $currentValues) . "<br>";
        
        // Manager rolü var mı kontrol et
        if (in_array('manager', $currentValues)) {
            echo "✓ Manager rolü zaten mevcut<br>";
        } else {
            echo "⚠ Manager rolü bulunamadı, ekleniyor...<br>";
            
            // Admin rolünü manager olarak değiştir
            echo "<h3>2. Admin Rollerini Manager Olarak Güncelleniyor...</h3>";
            
            // Önce admin rolündeki kullanıcıları manager olarak güncelle
            $updateResult = $db->query("UPDATE users SET role = 'manager' WHERE role = 'admin'");
            $affectedRows = $updateResult->rowCount();
            echo "✓ $affectedRows kullanıcının rolü admin'den manager'a güncellendi<br>";
            
            // ENUM'u güncelle
            echo "<h3>3. ENUM Değerleri Güncelleniyor...</h3>";
            
            $alterQuery = "ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'manager', 'member') DEFAULT 'member'";
            $db->query($alterQuery);
            echo "✓ Role ENUM güncellendi: superadmin, manager, member<br>";
            
            // Güncellenmiş ENUM değerlerini kontrol et
            $result = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
            $column = $result->fetch(PDO::FETCH_ASSOC);
            echo "Yeni role ENUM: " . $column['Type'] . "<br>";
        }
        
        // Kullanıcı sayılarını göster
        echo "<h3>4. Rol Dağılımı:</h3>";
        $roleStats = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        while ($stat = $roleStats->fetch(PDO::FETCH_ASSOC)) {
            echo "• " . ucfirst($stat['role']) . ": " . $stat['count'] . " kullanıcı<br>";
        }
        
    } else {
        echo "❌ Users tablosunda role sütunu bulunamadı<br>";
    }
    
    echo "<h3>5. İşlem Tamamlandı!</h3>";
    echo "<p style='color: green; font-weight: bold;'>✓ Rol sistemi başarıyla güncellendi</p>";
    echo "<p><a href='dashboard_superadmin.php'>Dashboard'a Dön</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Hata Oluştu:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Hata detayları: " . htmlspecialchars($e->getTraceAsString()) . "</p>";
}

echo "</body></html>";
?>




