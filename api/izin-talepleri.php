<?php
/**
 * API - İzin Talepleri (Next.js için)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Mail.php';

header('Content-Type: application/json');

$auth = new Auth();

if (!$auth->checkAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Oturum açılmamış.']);
    exit;
}

$user = $auth->getUser();
$db = Database::getInstance();

$hasPermissionBaskan = $auth->hasModulePermission('baskan_izin_talepleri');
$hasPermissionUye = $auth->hasModulePermission('uye_izin_talepleri');

if (!$hasPermissionBaskan && !$hasPermissionUye) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkiniz bulunmamaktadır.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $tab = $_GET['tab'] ?? ($hasPermissionBaskan ? 'onay' : 'talebim');
        
        if ($tab === 'talebim' && $hasPermissionUye) {
            $myRequests = $db->fetchAll("
                SELECT *
                FROM izin_talepleri
                WHERE kullanici_id = ?
                ORDER BY olusturma_tarihi DESC
            ", [$user['id']]);

            echo json_encode([
                'success' => true,
                'tab' => 'talebim',
                'hasPermissionBaskan' => $hasPermissionBaskan,
                'hasPermissionUye' => $hasPermissionUye,
                'requests' => $myRequests
            ]);
            exit;
        }
        
        if ($tab === 'onay' && $hasPermissionBaskan) {
            $durumFilter = $_GET['durum'] ?? '';
            $filters = ['k.byk_id = ?'];
            $params = [$user['byk_id']];
            
            if ($durumFilter) {
                $filters[] = "it.durum = ?";
                $params[] = $durumFilter;
            }
            
            $where = 'WHERE ' . implode(' AND ', $filters);
            
            $pendingRequests = $db->fetchAll("
                SELECT it.*, CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, k.email, k.telefon
                FROM izin_talepleri it
                INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
                $where
                ORDER BY it.olusturma_tarihi DESC
                LIMIT 200
            ", $params);

            echo json_encode([
                'success' => true,
                'tab' => 'onay',
                'hasPermissionBaskan' => $hasPermissionBaskan,
                'hasPermissionUye' => $hasPermissionUye,
                'requests' => $pendingRequests
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Geçersiz parametre.']);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        // Yeni İzin Talebi
        if ($action === 'yeni_izin' && $hasPermissionUye) {
            $baslangic = $input['baslangic_tarihi'] ?? '';
            $bitis = $input['bitis_tarihi'] ?? '';
            $izinNedeni = trim($input['izin_nedeni'] ?? '');
            $aciklama = trim($input['aciklama'] ?? '');

            if (!$baslangic || !$bitis) {
                echo json_encode(['success' => false, 'error' => 'Tarih alanları zorunludur.']);
                exit;
            }

            $db->query("
                INSERT INTO izin_talepleri (kullanici_id, baslangic_tarihi, bitis_tarihi, izin_nedeni, aciklama, durum)
                VALUES (?, ?, ?, ?, ?, 'beklemede')
            ", [$user['id'], $baslangic, $bitis, $izinNedeni, $aciklama]);

            $bykInfo = $db->fetch("SELECT muhasebe_baskani_id FROM byk WHERE byk_id = ?", [$user['byk_id']]);
            if ($bykInfo && $bykInfo['muhasebe_baskani_id']) {
                $muhBaskani = $bykInfo['muhasebe_baskani_id'];
                Notification::add($muhBaskani, 'Yeni İzin Talebi', "{$user['name']} yeni bir izin talebi oluşturdu.", 'bilgi', '/panel/izin-talepleri.php?tab=onay');
            }

            echo json_encode(['success' => true, 'message' => 'Talebiniz başarıyla oluşturuldu.']);
            exit;
        }

        // Onay / Red İşlemi
        if (($action === 'approve' || $action === 'reject') && $hasPermissionBaskan) {
            $izinId = (int)($input['izin_id'] ?? 0);
            $aciklama = trim($input['aciklama'] ?? '');
            
            $izin = $db->fetch("
                SELECT it.*, k.email, CONCAT(k.ad, ' ', k.soyad) as ad_soyad
                FROM izin_talepleri it
                INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
                WHERE it.izin_id = ? AND k.byk_id = ?
            ", [$izinId, $user['byk_id']]);

            if (!$izin) {
                echo json_encode(['success' => false, 'error' => 'Talep bulunamadı veya yetkisiz.']);
                exit;
            }

            $yeniDurum = ($action === 'approve') ? 'onaylandi' : 'reddedildi';
            $db->query("
                UPDATE izin_talepleri
                SET durum = ?, onaylayan_id = ?, onay_tarihi = NOW(), onay_aciklama = ?
                WHERE izin_id = ?
            ", [$yeniDurum, $user['id'], $aciklama, $izinId]);

            Notification::add($izin['kullanici_id'], "İzin Talebi Sonucu", "Talebiniz $yeniDurum.", $yeniDurum == 'onaylandi' ? 'basarili' : 'uyari', '/panel/izin-talepleri.php?tab=talebim');

            echo json_encode(['success' => true, 'message' => 'Talep ' . $yeniDurum]);
            exit;
        }

        // Sil İşlemi
        if ($action === 'delete' && $hasPermissionBaskan) {
            $izinId = (int)($input['izin_id'] ?? 0);
            $db->query("DELETE it FROM izin_talepleri it INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id WHERE it.izin_id = ? AND k.byk_id = ?", [$izinId, $user['byk_id']]);
            echo json_encode(['success' => true, 'message' => 'Talep silindi.']);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Geçersiz işlem.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
