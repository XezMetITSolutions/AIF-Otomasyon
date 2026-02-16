<?php
// Output buffering to catch any stray output (warnings, etc.)
ob_start();

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Mail.php';
require_once __DIR__ . '/../classes/MeetingPDF.php';

// Turn off displaying errors to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->checkAuth()) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekmektedir.']);
    exit;
}

$user = $auth->getUser();
$db = Database::getInstance();

$toplanti_id = $_POST['id'] ?? null;

if (!$toplanti_id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Toplantı ID gereklidir.']);
    exit;
}

try {
    // Toplantı bilgilerini ve yetki kontrolünü getir
    $toplanti = $db->fetch("SELECT * FROM toplantilar WHERE toplanti_id = ? AND byk_id = ?", [$toplanti_id, $user['byk_id']]);

    if (!$toplanti) {
        throw new Exception('Toplantı bulunamadı veya bu toplantıya erişim yetkiniz yok.');
    }

    // PDF Üret
    $pdfData = MeetingPDF::generate($toplanti_id, 'S');
    if (empty($pdfData)) {
        throw new Exception('PDF raporu oluşturulamadı.');
    }
    $pdfName = 'Toplanti_Raporu_' . $toplanti_id . '.pdf';

    // Mail Gönder
    // Mail İçeriği (Profesyonel Şablon)
    $subject = 'Toplantı Raporu: ' . $toplanti['baslik'];
    $appName = Config::get('app_name', 'AİFNET');
    $appUrl = rtrim(Config::get('app_url', 'https://aifnet.islamfederasyonu.at'), '/');

    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <h2 style='color: #00936F; margin: 0;'>{$appName}</h2>
            <p style='color: #666; font-size: 14px;'>Toplantı Yönetim Sistemi</p>
        </div>
        <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
            <h3 style='margin-top: 0; color: #333;'>Toplantı Raporu Hazırlandı</h3>
            <p><strong>Toplantı:</strong> " . htmlspecialchars($toplanti['baslik']) . "</p>
            <p><strong>Tarih:</strong> " . date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi'])) . "</p>
        </div>
        <p>Merhaba,</p>
        <p>Gerçekleştirilen toplantıya ait detaylı rapor (katılımcı durumları, alınan kararlar ve görüşme notları) ekte PDF formatında sunulmuştur.</p>
        <div style='margin: 30px 0; text-align: center;'>
            <a href='{$appUrl}/panel/toplanti-duzenle.php?id={$toplanti_id}' style='background-color: #00936F; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Sistemde Görüntüle</a>
        </div>
        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
        <p style='font-size: 12px; color: #999; text-align: center;'>
            Bu e-posta {$appName} tarafından otomatik olarak oluşturulmuştur.<br>
            Lütfen bu mesajı yanıtlamayınız.
        </p>
    </div>";

    // Alıcıları Belirle (Katılımcılar ve Test Adresi)
    $recipients = [];
    $recipients[] = 'mete.burcak@gmx.at'; // Test adresi (istek üzerine)

    // Toplantı katılımcılarını getir
    $katilimcilar = $db->fetchAll("
        SELECT k.email 
        FROM toplanti_katilimcilar tk
        JOIN kullanicilar k ON tk.kullanici_id = k.kullanici_id
        WHERE tk.toplanti_id = ? AND k.email IS NOT NULL AND k.email != ''
    ", [$toplanti_id]);

    foreach ($katilimcilar as $k) {
        if (!in_array($k['email'], $recipients)) {
            $recipients[] = $k['email'];
        }
    }

    $successCount = 0;
    $errors = [];

    foreach ($recipients as $to) {
        $result = Mail::sendWithAttachment($to, $subject, $message, $pdfData, $pdfName);
        if ($result) {
            $successCount++;
        } else {
            $errors[] = $to . ": " . (Mail::$lastError ?? 'Bilinmeyen hata');
        }
    }

    // Any output captured so far is likely warnings, let's clear them
    $capturedOutput = ob_get_clean();

    if ($successCount > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Toplantı raporu başarıyla gönderildi.',
            'info' => "{$successCount} kişiye ulaştırıldı." . (!empty($errors) ? " (" . count($errors) . " hata)" : "")
        ]);
    } else {
        throw new Exception(!empty($errors) ? implode(", ", $errors) : 'E-posta gönderilemedi.');
    }

} catch (Exception $e) {
    if (ob_get_level() > 0)
        ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
