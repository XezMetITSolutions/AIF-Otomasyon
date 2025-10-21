<?php
echo "Test çalışıyor!<br>";
echo "Tarih: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP Versiyonu: " . phpversion() . "<br>";

// Dosya kontrolü
if (file_exists('admin/includes/database.php')) {
    echo "✅ database.php var<br>";
} else {
    echo "❌ database.php YOK<br>";
}

if (file_exists('admin/includes/byk_manager_db.php')) {
    echo "✅ byk_manager_db.php var<br>";
} else {
    echo "❌ byk_manager_db.php YOK<br>";
}

// Basit BYK test
try {
    require_once 'admin/includes/database.php';
    echo "✅ database.php yüklendi<br>";
    
    require_once 'admin/includes/byk_manager_db.php';
    echo "✅ byk_manager_db.php yüklendi<br>";
    
    $bykCategories = BYKManager::getBYKCategories();
    echo "✅ BYK kategorileri: " . count($bykCategories) . " adet<br>";
    
    foreach ($bykCategories as $byk) {
        echo "&nbsp;&nbsp;• " . $byk['code'] . " - " . $byk['name'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<br>Test tamamlandı!";
?>
