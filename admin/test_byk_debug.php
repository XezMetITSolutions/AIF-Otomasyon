<?php
// Basit test sayfası - Session kontrolü YOK
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP çalışıyor!<br>";

// Dosya varlığını kontrol et
$files = [
    'includes/auth.php',
    'includes/user_manager_db.php', 
    'includes/byk_manager_db.php',
    'includes/database.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file var<br>";
    } else {
        echo "❌ $file YOK<br>";
    }
}

// Include test - Sadece database.php
try {
    require_once 'includes/database.php';
    echo "✅ database.php include başarılı<br>";
} catch (Exception $e) {
    echo "❌ database.php include hatası: " . $e->getMessage() . "<br>";
}

// BYKManager test
try {
    require_once 'includes/byk_manager_db.php';
    echo "✅ byk_manager_db.php include başarılı<br>";
    
    $bykCategories = BYKManager::getBYKCategories();
    echo "✅ BYKManager::getBYKCategories() çalışıyor - " . count($bykCategories) . " kategori<br>";
    
    // BYK kategorilerini listele
    foreach ($bykCategories as $byk) {
        echo "&nbsp;&nbsp;• " . $byk['code'] . " - " . $byk['name'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ BYKManager hatası: " . $e->getMessage() . "<br>";
}

echo "<br>Test tamamlandı!";
?>