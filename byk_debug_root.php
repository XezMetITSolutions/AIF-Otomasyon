<?php
// Root klasörde BYK Debug Sayfası - Session kontrolü YOK
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>BYK Debug Sayfası - Root Klasör</h1>";
echo "<p>Tarih: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Versiyonu: " . phpversion() . "</p>";

// Dosya varlığını kontrol et
$files = [
    'admin/includes/database.php',
    'admin/includes/byk_manager_db.php'
];

echo "<h2>Dosya Kontrolü:</h2>";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file var<br>";
    } else {
        echo "❌ $file YOK<br>";
    }
}

// BYK test
echo "<h2>BYK Test:</h2>";
try {
    require_once 'admin/includes/database.php';
    echo "✅ database.php yüklendi<br>";
    
    require_once 'admin/includes/byk_manager_db.php';
    echo "✅ byk_manager_db.php yüklendi<br>";
    
    $bykCategories = BYKManager::getBYKCategories();
    echo "✅ BYK kategorileri: " . count($bykCategories) . " adet<br>";
    
    echo "<h3>BYK Kategorileri:</h3>";
    foreach ($bykCategories as $byk) {
        echo "&nbsp;&nbsp;• " . $byk['code'] . " - " . $byk['name'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

// Test formu
echo "<h2>BYK Test Formu:</h2>";
echo '<form method="POST" action="test_byk_add.php">';
echo '<input type="text" name="first_name" placeholder="Ad" value="Debug" required><br><br>';
echo '<input type="text" name="last_name" placeholder="Soyad" value="Test" required><br><br>';
echo '<input type="email" name="email" placeholder="E-posta" value="debug.test@aif.com" required><br><br>';
echo '<select name="byk" required>';
echo '<option value="">BYK Seçin</option>';
if (isset($bykCategories)) {
    foreach ($bykCategories as $byk) {
        echo '<option value="' . $byk['code'] . '">' . $byk['code'] . ' - ' . $byk['name'] . '</option>';
    }
}
echo '</select><br><br>';
echo '<select name="role" required>';
echo '<option value="manager">Manager</option>';
echo '<option value="member">Üye</option>';
echo '</select><br><br>';
echo '<select name="status" required>';
echo '<option value="active">Aktif</option>';
echo '<option value="inactive">Pasif</option>';
echo '</select><br><br>';
echo '<button type="submit">Test Kullanıcı Ekle</button>';
echo '</form>';

echo "<br><p>Test tamamlandı!</p>";
?>
