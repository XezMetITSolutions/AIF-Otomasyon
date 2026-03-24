<?php
/**
 * API - Kontrol Paneli Özet Verileri (Next.js için)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->checkAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Oturum açılmamış.']);
    exit;
}

$user = $auth->getUser();
$db = Database::getInstance();

try {
    // 1. İstatistik Sayaçları (MOCK veya DB Sorguları)
    // Yaklaşan Toplantı Sayısı
    $upcomingMeetingsCount = $db->fetchColumn("
        SELECT COUNT(*) 
        FROM toplantilar t
        INNER JOIN toplanti_katilimcilar tk ON t.toplanti_id = tk.toplanti_id
        WHERE tk.kullanici_id = ? AND t.toplanti_tarihi >= CURDATE()
    ", [$user['id']]);

    // Aktif Duyuru Sayısı
    $activeAnnouncementsCount = $db->fetchColumn("
        SELECT COUNT(*) FROM duyurular WHERE byk_id = ? AND aktif = 1
    ", [$user['byk_id'] ?? 0]);

    // Bekleyen İzin Sayısı
    $pendingLeavesCount = $db->fetchColumn("
        SELECT COUNT(*) FROM izin_talepleri WHERE kullanici_id = ? AND durum = 'beklemede'
    ", [$user['id']]);

    // Toplam Harcama (Bu ay)
    $totalExpenses = $db->fetchColumn("
        SELECT SUM(tutar) FROM harcama_talepleri 
        WHERE kullanici_id = ? AND durum = 'onaylandi' AND MONTH(olusturma_tarihi) = MONTH(CURDATE())
    ", [$user['id']]);

    // 2. Detaylı Listeler
    // Yaklaşan 5 Toplantı
    $meetings = $db->fetchAll("
        SELECT t.toplanti_id, t.baslik, t.toplanti_tarihi, tk.katilim_durumu
        FROM toplantilar t
        INNER JOIN toplanti_katilimcilar tk ON t.toplanti_id = tk.toplanti_id
        WHERE tk.kullanici_id = ? AND t.toplanti_tarihi >= CURDATE()
        ORDER BY t.toplanti_tarihi ASC
        LIMIT 5
    ", [$user['id']]);

    // Son 5 Duyuru
    $announcements = $db->fetchAll("
        SELECT duyuru_id, baslik, icerik, olusturma_tarihi
        FROM duyurular
        WHERE byk_id = ? AND aktif = 1
        ORDER BY olusturma_tarihi DESC
        LIMIT 5
    ", [$user['byk_id'] ?? 0]);

    echo json_encode([
        'success' => true,
        'stats' => [
            'upcoming_meetings' => (int)$upcomingMeetingsCount,
            'active_announcements' => (int)$activeAnnouncementsCount,
            'pending_leaves' => (int)$pendingLeavesCount,
            'expenses' => number_format((float)$totalExpenses, 2, '.', '')
        ],
        'meetings' => $meetings,
        'announcements' => $announcements
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Sunucu hatası oluştu.',
        'message' => $e->getMessage()
    ]);
}
