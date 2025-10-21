<?php
echo "Test sayfası çalışıyor!<br>";
echo "Tarih: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP Versiyonu: " . phpversion() . "<br>";

// Dosya varlığını kontrol et
$files = [
    'includes/database.php',
    'includes/byk_manager_db.php'
];

echo "<br>Dosya kontrolü:<br>";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file var<br>";
    } else {
        echo "❌ $file YOK<br>";
    }
}

// Basit BYK test
try {
    require_once 'includes/database.php';
    echo "<br>✅ database.php yüklendi<br>";
    
    require_once 'includes/byk_manager_db.php';
    echo "✅ byk_manager_db.php yüklendi<br>";
    
    $bykCategories = BYKManager::getBYKCategories();
    echo "✅ BYK kategorileri: " . count($bykCategories) . " adet<br>";
    
    foreach ($bykCategories as $byk) {
        echo "&nbsp;&nbsp;• " . $byk['code'] . " - " . $byk['name'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "<br>❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<br>Test tamamlandı!";
?>
