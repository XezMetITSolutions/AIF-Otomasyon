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
    $toplanti = $db->fetch("
        SELECT t.*, 
               CONCAT(u_ols.ad, ' ', u_ols.soyad) as olusturan,
               CONCAT(u_sek.ad, ' ', u_sek.soyad) as sekreter_adi
        FROM toplantilar t
        INNER JOIN kullanicilar u_ols ON t.olusturan_id = u_ols.kullanici_id
        LEFT JOIN kullanicilar u_sek ON t.sekreter_id = u_sek.kullanici_id
        WHERE t.toplanti_id = ? AND t.byk_id = ?
    ", [$toplanti_id, $user['byk_id']]);

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
    $subject = 'Toplantı Raporu: ' . $toplanti['baslik'];
    $tarih = date('d.m.Y', strtotime($toplanti['toplanti_tarihi']));
    $sekreter = !empty($toplanti['sekreter_adi']) ? $toplanti['sekreter_adi'] : $toplanti['olusturan'];

    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #e0e0e0; border-radius: 10px; line-height: 1.6; color: #333;'>
        <p>Değerli Başkanlarım,</p>
        
        <p><strong>{$tarih}</strong> tarihinde gerçekleştirmiş olduğumuz <strong>" . htmlspecialchars($toplanti['baslik']) . "</strong> toplantısına ait tutanak ekte bilginize sunulmuştur.</p>
        
        <p>Toplantı sırasında alınan kararlar ve değerlendirilen hususlar tutanağa işlenmiş olup, incelemenizi rica ederiz. İlave edilmesini istediğiniz hususlar veya düzeltme önerileriniz olması hâlinde tarafımıza bildirmenizi memnuniyetle rica ederiz.</p>
        
        <p style='margin-top: 30px;'>Selam ve Dua ile,</p>
        
        <p style='margin-bottom: 0;'><strong>{$sekreter}</strong></p>
        <p style='margin-top: 0; color: #00936F; font-weight: bold;'>Avusturya İslam Federasyonu</p>
    </div>";

    // Alıcıları Belirle
    $isTest = isset($_POST['is_test']) && $_POST['is_test'] === '1';
    $recipients = [];
    $testEmail = 'mete.burcak@gmx.at';

    if ($isTest) {
        $recipients[] = $testEmail;
    } else {
        // Asıl gönderim: Tüm katılımcılar + Test adresi
        $recipients[] = $testEmail;

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
            'message' => $isTest ? 'Test raporu başarıyla gönderildi.' : 'Toplantı raporu başarıyla gönderildi.',
            'info' => $isTest ? "Alıcı: {$testEmail}" : "{$successCount} kişiye ulaştırıldı." . (!empty($errors) ? " (" . count($errors) . " hata)" : "")
        ]);
    } else {
        throw new Exception(!empty($errors) ? implode(", ", $errors) : 'E-posta gönderilemedi.');
    }

} catch (Exception $e) {
    if (ob_get_level() > 0)
        ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
