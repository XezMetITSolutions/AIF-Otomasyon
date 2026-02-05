<?php
/**
 * Şube Ziyaret Planı Uygulama Wrapper (Web)
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$user = $auth->getUser();

// Sadece Super Admin veya AT Birimi çalıştırabilsin
$isAT = false;
if ($user) {
    $db = Database::getInstance();
    $userByk = $db->fetch("SELECT b.byk_kodu FROM byk b WHERE b.byk_id = ?", [$user['byk_id']]);
    if ($userByk && $userByk['byk_kodu'] === 'AT') {
        $isAT = true;
    }
}

if (!$isAT && (!$user || $user['role'] !== 'super_admin')) {
    die("Bu scripti çalıştırmak için yetkiniz yok.");
}

echo "<pre>";
echo "🚀 Ziyaret Planı Uygulanıyor...\n";
echo "=================================\n";

// Script içeriğini dahil et
require_once __DIR__ . '/database/apply-visit-plan.php';

echo "</pre>";
unlink(__FILE__); // Güvenlik için çalıştıktan sonra kendini sil
?>
