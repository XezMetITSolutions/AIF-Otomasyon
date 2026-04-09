<?php
/**
 * API - Rezervasyon Talepleri (Next.js için)
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
$approvalConfig = require __DIR__ . '/../config/approval_workflow.php';
$harcamaWorkflow = $approvalConfig['approval_workflow']['rezervasyon_talepleri'] ?? [];

$hasPermissionBaskan = $auth->hasModulePermission('baskan_harcama_talepleri');
$hasPermissionUye = $auth->hasModulePermission('uye_harcama_talepleri');

$isApprover = false;
if ($harcamaWorkflow['type'] === 'two_level') {
    if (!empty($harcamaWorkflow['first_approver_user_id']) && $user['id'] == $harcamaWorkflow['first_approver_user_id']) {
        $isFirstApprover = true;
        $hasPermissionBaskan = true;
    }
    if (!empty($harcamaWorkflow['second_approver_user_id']) && $user['id'] == $harcamaWorkflow['second_approver_user_id']) {
        $isSecondApprover = true;
        $hasPermissionBaskan = true;
    }
} else {
    // single_level
    if (!empty($harcamaWorkflow['approver_user_id']) && $user['id'] == $harcamaWorkflow['approver_user_id']) {
        $isApprover = true;
        $hasPermissionBaskan = true;
    }
}

if (!$hasPermissionBaskan && !$hasPermissionUye) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkiniz bulunmamaktadır.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function splitRezervasyonAciklamaVeMeta(?string $aciklama): array {
    $marker = '---META---';
    if (!$aciklama || mb_strpos($aciklama, $marker) === false) {
        return [trim($aciklama ?? ''), []];
    }
    $pos = mb_strrpos($aciklama, $marker);
    if ($pos === false) {
        return [trim($aciklama), []];
    }
    $metaJson = trim(mb_substr($aciklama, $pos + mb_strlen($marker)));
    $metin = trim(mb_substr($aciklama, 0, $pos));
    $meta = json_decode($metaJson, true);
    if (!is_array($meta)) {
        return [trim($aciklama), []];
    }
    return [$metin, $meta];
}

function buildRezervasyonAciklamaMetni(?string $kullaniciAciklama, array $meta): string {
    $marker = '---META---';
    $trimmed = trim($kullaniciAciklama ?? '');
    $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
    if ($trimmed !== '') {
        return $trimmed . "\n\n" . $marker . $metaJson;
    }
    return $marker . $metaJson;
}

try {
    if ($method === 'GET') {
        $tab = $_GET['tab'] ?? ($hasPermissionBaskan ? 'onay' : 'talebim');
        
        if ($tab === 'talebim' && $hasPermissionUye) {
            $myRequests = $db->fetchAll("
                SELECT * FROM rezervasyon_talepleri WHERE kullanici_id = ? ORDER BY olusturma_tarihi DESC
            ", [$user['id']]);

            foreach ($myRequests as &$req) {
                [$req['kisa_aciklama'], $req['meta']] = splitRezervasyonAciklamaVeMeta($req['aciklama']);
            }

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
            $filters = ['ht.byk_id = ?'];
            $params = [$user['byk_id']];
            
            if ($harcamaWorkflow['type'] === 'single_level') {
                if ($isApprover && !$auth->hasModulePermission('baskan_harcama_talepleri')) {
                    if (!$durumFilter) $durumFilter = 'beklemede';
                }
            } else {
                if ($isFirstApprover && !$isSecondApprover && !$auth->hasModulePermission('baskan_harcama_talepleri')) {
                    if (!$durumFilter) $durumFilter = 'beklemede';
                }
                if ($isSecondApprover && !$isFirstApprover && !$auth->hasModulePermission('baskan_harcama_talepleri')) {
                    if (!$durumFilter) $durumFilter = 'ilk_onay';
                }
            }
            
            if ($durumFilter) {
                $filters[] = 'ht.durum = ?';
                $params[] = $durumFilter;
            }
            
            $where = 'WHERE ' . implode(' AND ', $filters);
            
            $pendingRequests = $db->fetchAll("
                SELECT ht.*, 
                       CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, k.email, k.telefon,
                       CONCAT(ilk_onay.ad, ' ', ilk_onay.soyad) as ilk_onaylayan_ad,
                       CONCAT(ikinci_onay.ad, ' ', ikinci_onay.soyad) as ikinci_onaylayan_ad
                FROM rezervasyon_talepleri ht
                INNER JOIN kullanicilar k ON ht.kullanici_id = k.kullanici_id
                LEFT JOIN kullanicilar ilk_onay ON ht.ilk_onaylayan_id = ilk_onay.kullanici_id
                LEFT JOIN kullanicilar ikinci_onay ON ht.ikinci_onaylayan_id = ikinci_onay.kullanici_id
                $where ORDER BY ht.olusturma_tarihi DESC LIMIT 100
            ", $params);

            foreach ($pendingRequests as &$req) {
                [$req['kisa_aciklama'], $req['meta']] = splitRezervasyonAciklamaVeMeta($req['aciklama']);
                
                // Onay hakkı
                $req['canApprove1'] = false;
                $req['canApprove2'] = false;
                
                if ($harcamaWorkflow['type'] === 'single_level') {
                    if ($req['durum'] === 'beklemede' && ($isApprover || $auth->hasModulePermission('baskan_harcama_talepleri'))) {
                        $req['canApprove1'] = true; // Use canApprove1 for the UI button
                    }
                } else {
                    if ($req['durum'] === 'beklemede' && ($isFirstApprover || $auth->hasModulePermission('baskan_harcama_talepleri'))) {
                        $req['canApprove1'] = true;
                    }
                    if ($req['durum'] === 'ilk_onay' && ($isSecondApprover || $auth->hasModulePermission('baskan_harcama_talepleri'))) {
                        $req['canApprove2'] = true;
                    }
                }
            }

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

        if ($action === 'yeni_harcama' && $hasPermissionUye) {
            $baslik = trim($input['baslik'] ?? '');
            $tutar = $input['tutar'] ?? 0;
            $kategori = $input['kategori'] ?? 'genel';
            $aciklama = trim($input['aciklama'] ?? '');

            if (!$baslik) {
                echo json_encode(['success' => false, 'error' => 'Geçerli başlık zorunludur.']);
                exit;
            }

            $meta = ['kategori' => $kategori];
            $aciklamaFinal = buildRezervasyonAciklamaMetni($aciklama, $meta);

            $db->query("
                INSERT INTO rezervasyon_talepleri (kullanici_id, byk_id, baslik, aciklama, tutar, durum)
                VALUES (?, ?, ?, ?, ?, 'beklemede')
            ", [$user['id'], $user['byk_id'], $baslik, $aciklamaFinal, number_format((float)$tutar, 2, '.', '')]);

            $approverId = ($harcamaWorkflow['type'] === 'single_level') ? $harcamaWorkflow['approver_user_id'] : $harcamaWorkflow['first_approver_user_id'];
            
            if ($approverId) {
                Notification::add($approverId, 'Yeni Rezervasyon Talebi', "{$user['name']} rezervasyon talebi oluşturdu: $baslik", 'bilgi', '/dashboard/rezervasyon-talepleri?tab=onay');
            }

            echo json_encode(['success' => true, 'message' => 'Rezervasyon talebi başarıyla oluşturuldu.']);
            exit;
        }

        if (($action === 'approve' || $action === 'reject') && $hasPermissionBaskan) {
            $talepId = (int)($input['talep_id'] ?? 0);
            $aciklama = trim($input['aciklama'] ?? '');

            $talep = $db->fetch("SELECT * FROM rezervasyon_talepleri WHERE talep_id = ? AND byk_id = ?", [$talepId, $user['byk_id']]);
            if (!$talep) {
                echo json_encode(['success' => false, 'error' => 'Talep bulunamadı.']);
                exit;
            }

            $currentLevel = $talep['onay_seviyesi'] ?? 0;
            $currentStatus = $talep['durum'];

            if ($action === 'reject') {
                if ($currentStatus === 'reddedildi' || $currentStatus === 'onaylandi') {
                    echo json_encode(['success' => false, 'error' => 'Talep zaten sonuçlandırılmış.']);
                    exit;
                }
                $db->query("UPDATE rezervasyon_talepleri SET durum = 'reddedildi', onay_seviyesi = 0 WHERE talep_id = ?", [$talepId]);
                Notification::add($talep['kullanici_id'], 'Rezervasyon Reddedildi', "Talebiniz reddedildi.", 'hata', '/dashboard/rezervasyon-talepleri?tab=talebim');
                echo json_encode(['success' => true, 'message' => 'Talep reddedildi.']);
                exit;
            }

            if ($action === 'approve') {
                $isSuperAdmin = $auth->hasModulePermission('baskan_harcama_talepleri') && !$isApprover && !$isFirstApprover && !$isSecondApprover;
                
                if ($harcamaWorkflow['type'] === 'single_level') {
                    if ($isApprover || $isSuperAdmin) {
                        $db->query("UPDATE rezervasyon_talepleri SET durum = 'onaylandi', onay_seviyesi = 2, ilk_onaylayan_id = ?, ilk_onay_tarihi = NOW(), ilk_onay_aciklama = ? WHERE talep_id = ?", [$user['id'], $aciklama, $talepId]);
                        Notification::add($talep['kullanici_id'], 'Rezervasyon Onaylandı', "Talebiniz tamamen onaylandı.", 'basarili', '/dashboard/rezervasyon-talepleri?tab=talebim');
                        echo json_encode(['success' => true, 'message' => 'Talep onaylandı.']);
                        exit;
                    }
                } else {
                    if ($isFirstApprover && $currentLevel == 0 && $currentStatus === 'beklemede') {
                        $db->query("UPDATE rezervasyon_talepleri SET durum = 'ilk_onay', onay_seviyesi = 1, ilk_onaylayan_id = ?, ilk_onay_tarihi = NOW(), ilk_onay_aciklama = ? WHERE talep_id = ?", [$user['id'], $aciklama, $talepId]);
                        if (!empty($harcamaWorkflow['second_approver_user_id'])) {
                            Notification::add($harcamaWorkflow['second_approver_user_id'], 'Rezervasyon Onayı (2. Aşama)', "2. onay bekleniyor: {$talep['baslik']}", 'uyari', '/dashboard/rezervasyon-talepleri?tab=onay');
                        }
                        echo json_encode(['success' => true, 'message' => 'İlk onay tamamlandı.']);
                        exit;
                    } elseif ($isSecondApprover && $currentLevel == 1 && $currentStatus === 'ilk_onay') {
                        $db->query("UPDATE rezervasyon_talepleri SET durum = 'onaylandi', onay_seviyesi = 2, ikinci_onaylayan_id = ?, ikinci_onay_tarihi = NOW(), ikinci_onay_aciklama = ? WHERE talep_id = ?", [$user['id'], $aciklama, $talepId]);
                        Notification::add($talep['kullanici_id'], 'Rezervasyon Onaylandı', "Talebiniz tamamen onaylandı.", 'basarili', '/dashboard/rezervasyon-talepleri?tab=talebim');
                        echo json_encode(['success' => true, 'message' => 'İkinci onay tamamlandı.']);
                        exit;
                    } elseif ($isSuperAdmin) {
                        $db->query("UPDATE rezervasyon_talepleri SET durum = 'onaylandi', onay_seviyesi = 2, ilk_onaylayan_id = ?, ikinci_onaylayan_id = ? WHERE talep_id = ?", [$user['id'], $user['id'], $talepId]);
                        Notification::add($talep['kullanici_id'], 'Rezervasyon Onaylandı', "Talebiniz onaylandı.", 'basarili', '/dashboard/rezervasyon-talepleri?tab=talebim');
                        echo json_encode(['success' => true, 'message' => 'Talep direkt onaylandı.']);
                        exit;
                    }
                }
                echo json_encode(['success' => false, 'error' => 'Bu seviyede onay yetkiniz yok.']);
                exit;
            }
        }

        if ($action === 'delete' && $hasPermissionBaskan) {
            $talepId = (int)($input['talep_id'] ?? 0);
            $db->query("DELETE FROM rezervasyon_talepleri WHERE talep_id = ? AND byk_id = ?", [$talepId, $user['byk_id']]);
            echo json_encode(['success' => true, 'message' => 'Talep silindi.']);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Geçersiz işlem.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
