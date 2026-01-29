<?php
/**
 * Dosya Güvenli Erişim Noktası
 * Sadece giriş yapmış kullanıcılar dosyalara erişebilir.
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';

// Oturum kontrolü
$auth = new Auth();
$user = $auth->getUser();

if (!$user) {
    header('HTTP/1.0 403 Forbidden');
    die("Bu dosyayı görüntülemek için giriş yapmalısınız.");
}

// Dosya yolu parametresi
$requestPath = $_GET['path'] ?? '';

// Güvenlik kontrolleri
if (empty($requestPath)) {
    die("Dosya belirtilmedi.");
}

// Dizin değiştirme saldırılarını engelle
$requestPath = str_replace(['../', '..\\'], '', $requestPath); // Basit temizlik

// Ana uploads dizini
$baseUploadsDir = realpath(__DIR__ . '/../uploads');
$targetFile = realpath(__DIR__ . '/..' . $requestPath);

// Dosya gerçekten uploads klasöründe mi?
if ($targetFile && strpos($targetFile, $baseUploadsDir) === 0 && file_exists($targetFile)) {
    
    // Mime tipini belirle
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $targetFile);
    finfo_close($finfo);
    
    // Headerları gönder
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    
    // Eğer indirme isteniyorsa
    if (isset($_GET['download'])) {
        header('Content-Disposition: attachment; filename="' . basename($targetFile) . '"');
    } else {
        // Tarayıcıda aç (PDF, Resim vs için)
        header('Content-Disposition: inline; filename="' . basename($targetFile) . '"');
    }
    
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($targetFile));
    
    // Dosyayı oku ve bitir
    readfile($targetFile);
    exit;
} else {
    header('HTTP/1.0 404 Not Found');
    echo "Dosya bulunamadı veya erişim izniniz yok. ($requestPath)";
}
?>
