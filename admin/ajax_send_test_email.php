<?php
/**
 * AJAX - Test E-postası Gönder
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Mail.php';

header('Content-Type: application/json');

try {
    Middleware::requireSuperAdmin();

    $userId = $_POST['user_id'] ?? null;
    $kod = $_POST['kod'] ?? null;
    $content = $_POST['content'] ?? null;
    $subjectLine = $_POST['subject'] ?? null;

    if (!$userId || !$kod) {
        throw new Exception('Geçersiz parametreler.');
    }

    $db = Database::getInstance();
    $targetUser = $db->fetch("SELECT ad, soyad, email FROM kullanicilar WHERE kullanici_id = ?", [$userId]);

    if (!$targetUser) {
        throw new Exception('Kullanıcı bulunamadı.');
    }

    // Mock data for test
    $data = [
        'ad_soyad' => $targetUser['ad'] . ' ' . $targetUser['soyad'],
        'email' => $targetUser['email'],
        'baslik' => 'Test Toplantısı / Duyurusu',
        'tarih' => date('d.m.Y H:i'),
        'konum' => 'Viyana Merkez Ofis',
        'detay' => 'Bu bir test içeriğidir. Şablonun doğru göründüğünü doğrulamak için gönderilmiştir.',
        'talep_turu' => 'Harcama Talebi (Test)',
        'durum' => 'Onaylandı (Test)',
        'aciklama' => 'Test açıklaması içeriği.',
        'accept_url' => '#',
        'reject_url' => '#',
        'reset_url' => '#',
        'duyuru_url' => '#',
        'iptal_nedeni' => 'Test amaçlı iptal edilmiştir.',
        'gundem_html' => '<p>• Gündem maddesi 1 (Test)<br>• Gündem maddesi 2 (Test)</p>',
        'token' => 'test-token'
    ];

    // Şablonu veritabanından değil, gelen içerikten parse edelim (düzenleme sırasında test edebilmek için)
    // Ancak Mail::sendWithTemplate veritabanından çekiyor. 
    // Test için içeriği manuel parse edip Mail::send kullanalım.

    // Add common global variables
    $data['app_name'] = Config::get('app_name', 'AİFNET');
    $data['app_url'] = Config::get('app_url', 'https://aifnet.islamfederasyonu.at');
    $data['year'] = date('Y');
    $data['panel_url'] = $data['app_url'] . '/panel/dashboard.php';

    $parsedSubject = Mail::parseTemplate($subjectLine, $data);
    $parsedBody = Mail::parseTemplate($content, $data);

    if (Mail::send($targetUser['email'], $parsedSubject, $parsedBody)) {
        echo json_encode(['success' => true]);
    } else {
        $error = Mail::$lastError ? ': ' . Mail::$lastError : '.';
        throw new Exception('E-posta gönderilemedi. SMTP ayarlarını kontrol edin' . $error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
