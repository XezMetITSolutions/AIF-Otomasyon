<?php
// Duplicate positions temizleme scripti
require_once 'includes/database.php';

try {
    $db = new DBHelper();
    
    echo "<h2>Duplicate Positions Temizleme</h2>";
    
    // Önce duplicate'leri bul
    $sql = "SELECT name, COUNT(*) as count FROM positions GROUP BY name HAVING COUNT(*) > 1";
    $duplicates = $db->query($sql);
    
    echo "<h3>Bulunan Duplicate'ler:</h3>";
    echo "<pre>";
    foreach ($duplicates as $dup) {
        echo "Görev: " . $dup['name'] . " - Sayı: " . $dup['count'] . "\n";
    }
    echo "</pre>";
    
    if (!empty($duplicates)) {
        echo "<h3>Duplicate'leri Temizleme:</h3>";
        
        foreach ($duplicates as $dup) {
            $name = $dup['name'];
            
            // Bu görev için tüm kayıtları al
            $sql = "SELECT * FROM positions WHERE name = ? ORDER BY id";
            $records = $db->query($sql, [$name]);
            
            echo "Görev: $name - Toplam kayıt: " . count($records) . "\n";
            
            // İlk kaydı tut, diğerlerini sil
            if (count($records) > 1) {
                $keepId = $records[0]['id'];
                $deleteIds = array_slice(array_column($records, 'id'), 1);
                
                echo "  Tutulacak ID: $keepId\n";
                echo "  Silinecek ID'ler: " . implode(', ', $deleteIds) . "\n";
                
                // Duplicate'leri sil
                $placeholders = str_repeat('?,', count($deleteIds) - 1) . '?';
                $sql = "DELETE FROM positions WHERE id IN ($placeholders)";
                $result = $db->execute($sql, $deleteIds);
                
                echo "  Silinen kayıt sayısı: " . $result . "\n\n";
            }
        }
        
        echo "<h3>Temizleme Tamamlandı!</h3>";
        
        // Son durumu kontrol et
        $sql = "SELECT COUNT(*) as total FROM positions";
        $total = $db->query($sql)[0]['total'];
        
        $sql = "SELECT COUNT(DISTINCT name) as unique_count FROM positions";
        $unique = $db->query($sql)[0]['unique_count'];
        
        echo "Toplam kayıt: $total<br>";
        echo "Unique görev: $unique<br>";
        
    } else {
        echo "<h3>Duplicate kayıt bulunamadı!</h3>";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
