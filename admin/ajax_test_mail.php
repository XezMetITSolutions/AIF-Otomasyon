<?php
/**
 * SMTP Test Mail AJAX Handler
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Mail.php';

// Güvenlik kontrolü
Middleware::requireSuperAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}
while (ob_get_level()) ob_end_clean();

$to = $_POST['test_email'] ?? '';
if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen geçerli bir e-posta adresi giriniz.']);
    exit;
}

// Varsayılan config'i yükle
$fileConfig = require __DIR__ . '/../config/mail.php';

// SMTP ayarlarını al (POST > DB > Dosya hiyerarşisi)
$config = [
    'host'       => ($_POST['smtp_host'] ?? '') ?: Config::get('smtp_host', $fileConfig['host']),
    'port'       => (int)(($_POST['smtp_port'] ?? 0) ?: Config::get('smtp_port', $fileConfig['port'])),
    'username'   => ($_POST['smtp_user'] ?? '') ?: Config::get('smtp_user', $fileConfig['username']),
    'password'   => !empty($_POST['smtp_pass']) ? $_POST['smtp_pass'] : Config::get('smtp_pass', $fileConfig['password']),
    'secure'     => ($_POST['smtp_secure'] ?? '') ?: Config::get('smtp_secure', $fileConfig['secure']),
    'from_email' => ($_POST['smtp_from_email'] ?? '') ?: Config::get('smtp_from_email', $fileConfig['from_email']),
    'from_name'  => ($_POST['smtp_from_name'] ?? '') ?: Config::get('smtp_from_name', $fileConfig['from_name']),
];

try {
    $subject = 'SMTP Test Mesajı - ' . Config::get('app_name', 'AIF Otomasyon');
    
    $body = '<h1>SMTP Testi Başarılı!</h1>';
    $body .= '<p>Bu e-posta, SMTP ayarlarınızın doğru yapılandırıldığını doğrulamak için gönderilmiştir.</p>';
    $body .= '<hr>';
    $body .= '<ul>';
    $body .= '<li><strong>Sunucu:</strong> ' . htmlspecialchars($config['host'] ?? '-') . '</li>';
    $body .= '<li><strong>Port:</strong> ' . (int)($config['port'] ?? 0) . '</li>';
    $body .= '<li><strong>Kullanıcı:</strong> ' . htmlspecialchars($config['username'] ?? '-') . '</li>';
    $body .= '<li><strong>Güvenlik:</strong> ' . htmlspecialchars($config['secure'] ?? '-') . '</li>';
    $body .= '</ul>';
    $body .= '<p>Tarih: ' . date('d.m.Y H:i:s') . '</p>';

    // Mail::send metodunun 4. parametresi custom config kabul eder
    $result = Mail::send($to, $subject, $body, $config);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Test e-postası başarıyla gönderildi.']);
    } else {
        $errorMsg = Mail::$lastError ?: 'Bağlantı hatası veya yanlış kimlik bilgileri.';
        echo json_encode(['success' => false, 'message' => 'E-posta gönderilemedi: ' . $errorMsg]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
