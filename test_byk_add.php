<?php
// Root klasörde BYK Test Backend - Session kontrolü YOK
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>BYK Test Sonucu</h1>";
echo "<p>Tarih: " . date('Y-m-d H:i:s') . "</p>";

// POST verilerini al
$input = $_POST;
echo "<h2>Gelen Veri:</h2>";
echo "<pre>" . print_r($input, true) . "</pre>";

// BYK test
echo "<h2>BYK Test:</h2>";
try {
    require_once 'admin/includes/database.php';
    require_once 'admin/includes/byk_manager_db.php';
    
    $bykCategoryId = null;
    if (!empty($input['byk'])) {
        echo "BYK kodu alındı: " . $input['byk'] . "<br>";
        
        $bykCategory = BYKManager::getBYKCategoryByCode($input['byk']);
        echo "BYK kategori lookup sonucu: " . json_encode($bykCategory) . "<br>";
        
        if ($bykCategory) {
            $bykCategoryId = $bykCategory['id'];
            echo "BYK kategori ID çözüldü: " . $bykCategoryId . "<br>";
        } else {
            echo "BYK kategori BULUNAMADI: " . $input['byk'] . "<br>";
        }
    } else {
        echo "BYK kodu sağlanmadı<br>";
    }
    
    echo "<h3>Sonuç:</h3>";
    echo "BYK Input: " . ($input['byk'] ?? 'YOK') . "<br>";
    echo "BYK Category ID: " . ($bykCategoryId ?? 'NULL') . "<br>";
    echo "BYK Found: " . ($bykCategory ? 'EVET' : 'HAYIR') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

echo "<br><a href='byk_debug_root.php'>Geri Dön</a>";
?>
