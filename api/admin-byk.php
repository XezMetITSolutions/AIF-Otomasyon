<?php
/**
 * API - Yönetim: BYK (Next.js için)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->checkAuth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Oturum açılmamış.']);
    exit;
}

if (!$auth->isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkiniz bulunmamaktadır.']);
    exit;
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $bykList = [];
        try {
            $bykList = $db->fetchAll("
                SELECT id, code, name, color, description, created_at, updated_at, 0 as kullanici_sayisi
                FROM byk_categories ORDER BY code ASC
            ");
            
            foreach ($bykList as &$byk) {
                $kullaniciSayisi = 0;
                try {
                    $usersCount = $db->fetch("SELECT COUNT(*) as cnt FROM users WHERE byk_category_id = ? AND status = 'active'", [$byk['id']]);
                    if ($usersCount) $kullaniciSayisi += (int)$usersCount['cnt'];
                } catch (Exception $e) {}
                
                try {
                    $kullanicilarCount = $db->fetch("SELECT COUNT(*) as cnt FROM kullanicilar k INNER JOIN byk b ON k.byk_id = b.byk_id WHERE b.byk_kodu = ? AND k.aktif = 1", [$byk['code']]);
                    if ($kullanicilarCount) $kullaniciSayisi += (int)$kullanicilarCount['cnt'];
                } catch (Exception $e) {}
                
                $byk['kullanici_sayisi'] = $kullaniciSayisi;
            }
            unset($byk);
        } catch (Exception $e) {
            try {
                $bykList = $db->fetchAll("
                    SELECT b.*, COUNT(k.kullanici_id) as kullanici_sayisi
                    FROM byk b
                    LEFT JOIN kullanicilar k ON b.byk_id = k.byk_id AND k.aktif = 1
                    GROUP BY b.byk_id ORDER BY b.olusturma_tarihi DESC
                ");
            } catch (Exception $e2) {
                $bykList = [];
            }
        }

        echo json_encode(['success' => true, 'byks' => $bykList]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $name = $input['name'] ?? '';
            $code = $input['code'] ?? '';
            $color = $input['color'] ?? '#009872';
            $description = $input['description'] ?? '';

            if (!$name || !$code) {
                echo json_encode(['success' => false, 'error' => 'İsim ve kod gereklidir.']);
                exit;
            }

            if ($action === 'create') {
                try {
                    $db->query("
                        INSERT INTO byk_categories (code, name, color, description, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ", [$code, $name, $color, $description]);
                } catch (Exception $e) {
                    $db->query("
                        INSERT INTO byk (byk_kodu, byk_adi, renk_kodu, olusturma_tarihi)
                        VALUES (?, ?, ?, NOW())
                    ", [$code, $name, $color]);
                }
                echo json_encode(['success' => true, 'message' => 'BYK/Bölge başarıyla oluşturuldu.']);
            } else {
                $id = (int)($input['byk_id'] ?? 0);
                if (!$id) {
                    echo json_encode(['success' => false, 'error' => 'ID bulunamadı.']);
                    exit;
                }
                
                try {
                    $db->query("
                        UPDATE byk_categories SET code=?, name=?, color=?, description=?, updated_at=NOW() WHERE id=?
                    ", [$code, $name, $color, $description, $id]);
                } catch (Exception $e) {
                    $db->query("
                        UPDATE byk SET byk_kodu=?, byk_adi=?, renk_kodu=? WHERE byk_id=?
                    ", [$code, $name, $color, $id]);
                }
                echo json_encode(['success' => true, 'message' => 'BYK/Bölge başarıyla güncellendi.']);
            }
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($input['byk_id'] ?? 0);
            
            // Delete from byk_categories and/or byk securely depending on whichever exists
            try {
                $db->query("DELETE FROM byk_categories WHERE id = ?", [$id]);
            } catch (Exception $e) {}

            try {
                $db->query("DELETE FROM byk WHERE byk_id = ?", [$id]);
            } catch (Exception $e) {}

            echo json_encode(['success' => true, 'message' => 'BYK başarıyla silindi.']);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Geçersiz POST aksiyonu.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
