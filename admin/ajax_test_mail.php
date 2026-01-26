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

$to = $_POST['test_email'] ?? '';
if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen geçerli bir e-posta adresi giriniz.']);
    exit;
}

// SMTP ayarlarını al (POST'tan geliyorsa onları kullan, yoksa DB'den)
$config = [
    'host' => $_POST['smtp_host'] ?? Config::get('smtp_host'),
    'port' => $_POST['smtp_port'] ?? Config::get('smtp_port'),
    'username' => $_POST['smtp_user'] ?? Config::get('smtp_user'),
    'password' => !empty($_POST['smtp_pass']) ? $_POST['smtp_pass'] : getenv('MAIL_PASS'),
    'secure' => $_POST['smtp_secure'] ?? Config::get('smtp_secure'),
    'from_email' => $_POST['smtp_from_email'] ?? Config::get('smtp_from_email'),
    'from_name' => $_POST['smtp_from_name'] ?? Config::get('smtp_from_name'),
];

try {
    require_once __DIR__ . '/../classes/SimpleSMTP.php';
    $smtp = new SimpleSMTP($config);

    $subject = 'SMTP Test Mesajı - ' . Config::get('app_name', 'AIF Otomasyon');
    $body = '<h1>SMTP Testi Başarılı!</h1>';
    $body .= '<p>Bu e-posta, SMTP ayarlarınızın doğru yapılandırıldığını doğrulamak için gönderilmiştir.</p>';
    $body .= '<hr>';
    $body .= '<ul>';
    $body .= '<li><strong>Sunucu:</strong> ' . htmlspecialchars($config['host']) . '</li>';
    $body .= '<li><strong>Port:</strong> ' . htmlspecialchars($config['port']) . '</li>';
    $body .= '<li><strong>Kullanıcı:</strong> ' . htmlspecialchars($config['username']) . '</li>';
    $body .= '<li><strong>Güvenlik:</strong> ' . htmlspecialchars($config['secure']) . '</li>';
    $body .= '</ul>';
    $body .= '<p>Tarih: ' . date('d.m.Y H:i:s') . '</p>';

    $result = $smtp->send(
        $to,
        $subject,
        $body,
        $config['from_email'],
        $config['from_name']
    );

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Test e-postası başarıyla gönderildi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'E-posta gönderilemedi. Lütfen ayarları ve bağlantıyı kontrol edin.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
