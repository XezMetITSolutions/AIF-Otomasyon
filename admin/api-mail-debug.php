<?php
/**
 * API: Mail Debug
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Mail.php';

Middleware::requireSuperAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$toplanti_id = $data['toplanti_id'] ?? 0;
$katilimci_ids = $data['katilimci_ids'] ?? [];

if (empty($toplanti_id) || empty($katilimci_ids)) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz parametreler.']);
    exit;
}

$db = Database::getInstance();

// Toplantı detaylarını getir
$toplanti = $db->fetch("SELECT * FROM toplantilar WHERE toplanti_id = ?", [$toplanti_id]);
if (!$toplanti) {
    echo json_encode(['success' => false, 'error' => 'Toplantı bulunamadı.']);
    exit;
}

$results = [];

foreach ($katilimci_ids as $katilimci_id) {
    // Katılımcı bilgisini çek
    $katilimci = $db->fetch("
        SELECT tk.*, k.ad, k.soyad, k.email 
        FROM toplanti_katilimcilar tk
        JOIN kullanicilar k ON tk.kullanici_id = k.kullanici_id
        WHERE tk.katilimci_id = ? AND tk.toplanti_id = ?
    ", [$katilimci_id, $toplanti_id]);

    if (!$katilimci) {
        $results[] = [
            'name' => 'Bilinmiyor',
            'email' => '-',
            'success' => false,
            'log' => 'Katılımcı bulunamadı.'
        ];
        continue;
    }

    // Token yoksa oluştur (geçici)
    $token = $katilimci['token'];
    if (empty($token)) {
        $token = 'debug-' . bin2hex(random_bytes(8));
    }

    $mailData = [
        'email' => $katilimci['email'],
        'ad_soyad' => $katilimci['ad'] . ' ' . $katilimci['soyad'],
        'baslik' => $toplanti['baslik'],
        'toplanti_tarihi' => $toplanti['toplanti_tarihi'],
        'konum' => $toplanti['konum'],
        'aciklama' => $toplanti['aciklama'],
        'token' => $token
    ];

    $success = Mail::sendMeetingInvitation($mailData);
    
    $results[] = [
        'name' => $katilimci['ad'] . ' ' . $katilimci['soyad'],
        'email' => $katilimci['email'],
        'success' => $success,
        'log' => Mail::$lastLog ?? ($success ? 'Başarıyla gönderildi.' : 'Hata: ' . Mail::$lastError)
    ];
}

echo json_encode(['success' => true, 'results' => $results]);
