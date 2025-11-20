<?php
// Dosya izinlerini test et
$dataFile = 'submissions.json';

echo "<h2>Dosya İzin Testi</h2>";

if (file_exists($dataFile)) {
    $perms = fileperms($dataFile);
    echo "<p><strong>submissions.json dosyası var</strong></p>";
    echo "<p>İzinler: " . decoct($perms & 0777) . "</p>";
    echo "<p>Okunabilir: " . (is_readable($dataFile) ? 'Evet' : 'Hayır') . "</p>";
    echo "<p>Yazılabilir: " . (is_writable($dataFile) ? 'Evet' : 'Hayır') . "</p>";
    
    // Test yazma
    $testData = file_get_contents($dataFile);
    $result = file_put_contents($dataFile, $testData);
    echo "<p>Test yazma: " . ($result !== false ? 'Başarılı' : 'Başarısız') . "</p>";
    
} else {
    echo "<p><strong>submissions.json dosyası bulunamadı</strong></p>";
    
    // Test oluşturma
    $testData = json_encode([]);
    $result = file_put_contents($dataFile, $testData);
    echo "<p>Test oluşturma: " . ($result !== false ? 'Başarılı' : 'Başarısız') . "</p>";
}

echo "<h2>Dizin İzin Testi</h2>";
$uploadDir = __DIR__ . '/uploads/';
echo "<p>Upload dizini: " . $uploadDir . "</p>";
echo "<p>Dizin var: " . (is_dir($uploadDir) ? 'Evet' : 'Hayır') . "</p>";
if (is_dir($uploadDir)) {
    echo "<p>Dizin yazılabilir: " . (is_writable($uploadDir) ? 'Evet' : 'Hayır') . "</p>";
}

echo "<h2>PHP Bilgileri</h2>";
echo "<p>PHP Sürümü: " . phpversion() . "</p>";
echo "<p>Çalışan kullanıcı: " . get_current_user() . "</p>";
echo "<p>Çalışma dizini: " . getcwd() . "</p>";

// Error log testi
error_log("Test log mesajı - " . date('Y-m-d H:i:s'));
echo "<p>Error log test mesajı gönderildi</p>";
?>


