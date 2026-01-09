<?php
/**
 * API Endpoint: Cancel Meeting
 * Cancels a meeting and notifies all participants via email
 */

// Disable error display but enable logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set JSON header first
header('Content-Type: application/json; charset=utf-8');

// Shutdown handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sistem hatası: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']]);
        exit;
    }
});

try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../classes/Database.php';
    require_once __DIR__ . '/../classes/Auth.php';
    require_once __DIR__ . '/../classes/Mail.php';

    // Check authentication
    $auth = new Auth();
    if (!$auth->checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı']);
        exit;
    }

    // Check if user is super admin or baskan
    $user = $auth->getUser();
    if ($user['role'] !== 'super_admin' && $user['role'] !== 'uye') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Geçersiz JSON verisi');
    }

    $toplanti_id = $data['toplanti_id'] ?? null;
    $iptal_nedeni = $data['iptal_nedeni'] ?? '';

    if (!$toplanti_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Toplantı ID gereklidir']);
        exit;
    }

    $db = Database::getInstance();

    // Toplantı bilgilerini al
    $toplanti = $db->fetch("
        SELECT t.*, b.byk_adi 
        FROM toplantilar t
        INNER JOIN byk b ON t.byk_id = b.byk_id
        WHERE t.toplanti_id = ?
    ", [$toplanti_id]);
    
    if (!$toplanti) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Toplantı bulunamadı']);
        exit;
    }
    
    // Toplantıyı iptal et
    $db->query("UPDATE toplantilar SET durum = 'iptal' WHERE toplanti_id = ?", [$toplanti_id]);
    
    // Katılımcıları al
    $katilimcilar = $db->fetchAll("
        SELECT k.email, CONCAT(k.ad, ' ', k.soyad) as ad_soyad
        FROM toplanti_katilimcilar tk
        INNER JOIN kullanicilar k ON tk.kullanici_id = k.kullanici_id
        WHERE tk.toplanti_id = ?
    ", [$toplanti_id]);
    
    // E-posta gönder
    $emailsSent = 0;
    $emailsFailed = 0;
    
    foreach ($katilimcilar as $katilimci) {
        if (!empty($katilimci['email'])) {
            $emailData = [
                'baslik' => $toplanti['baslik'],
                'toplanti_tarihi' => $toplanti['toplanti_tarihi'],
                'konum' => $toplanti['konum'] ?? '-',
                'ad_soyad' => $katilimci['ad_soyad'],
                'iptal_nedeni' => $iptal_nedeni
            ];
            
            $emailBody = Mail::getMeetingCancellationTemplate($emailData);
            $subject = "❌ Toplantı İptali: " . $toplanti['baslik'];
            
            if (Mail::send($katilimci['email'], $subject, $emailBody)) {
                $emailsSent++;
            } else {
                $emailsFailed++;
            }
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Toplantı iptal edildi. $emailsSent e-posta gönderildi" . ($emailsFailed > 0 ? ", $emailsFailed başarısız" : ""),
        'emails_sent' => $emailsSent,
        'emails_failed' => $emailsFailed
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
