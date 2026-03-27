<?php
/**
 * API - Yönetim: Alt Birimler (Next.js için)
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
        $search = $_GET['search'] ?? '';
        $bykFilter = $_GET['byk'] ?? '';

        $where = [];
        $params = [];

        if ($search) {
            $where[] = "(bsu.name LIKE ? OR bsu.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($bykFilter) {
            $where[] = "bsu.byk_category_id = ?";
            $params[] = $bykFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $bykList = $db->fetchAll("SELECT id, code, name, color FROM byk_categories ORDER BY code");
        } catch (Exception $e) {
            $bykList = [];
        }

        $altBirimler = [];

        try {
            $query = "
                SELECT 
                    bsu.id, bsu.byk_category_id, bsu.name, bsu.description, bsu.created_at, bsu.updated_at,
                    bc.name as byk_adi, bc.code as byk_kodu, bc.color as byk_renk
                FROM byk_sub_units bsu
                INNER JOIN byk_categories bc ON bsu.byk_category_id = bc.id
                $whereClause
                ORDER BY bc.code ASC, bsu.name ASC
            ";
            
            $altBirimler = $db->fetchAll($query, $params);
            
            foreach ($altBirimler as &$altBirim) {
                $sorumlu = null;
                $sorumluId = null;
                $description = $altBirim['description'] ?? '';
                
                if (strpos($description, '| Sorumlu:') !== false) {
                    $parts = explode('| Sorumlu:', $description);
                    if (isset($parts[1])) {
                        $sorumlu = trim($parts[1]);
                        try {
                            $nameParts = explode(' ', $sorumlu, 2);
                            if (count($nameParts) == 2) {
                                $kullanici = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE ad = ? AND soyad = ? AND aktif = 1 LIMIT 1", [$nameParts[0], $nameParts[1]]);
                                if ($kullanici) $sorumluId = $kullanici['kullanici_id'];
                            }
                        } catch (Exception $e) {}
                    }
                }
                
                $altBirim['sorumlu'] = $sorumlu;
                $altBirim['sorumlu_id'] = $sorumluId;
            }
            unset($altBirim);
        } catch (Exception $e) {
            try {
                $altBirimler = $db->fetchAll("
                    SELECT ab.*, b.byk_adi
                    FROM alt_birimler ab
                    INNER JOIN byk b ON ab.byk_id = b.byk_id
                    ORDER BY b.byk_adi, ab.alt_birim_adi
                ");
            } catch (Exception $e2) {}
        }

        echo json_encode([
            'success' => true,
            'subUnits' => $altBirimler,
            'constants' => [
                'byks' => $bykList
            ]
        ]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $name = $input['name'] ?? '';
            $byk_category_id = (int)($input['byk_id'] ?? 0);
            $description = $input['description'] ?? '';

            if (!$name || !$byk_category_id) {
                echo json_encode(['success' => false, 'error' => 'Birim adı ve bağlı BYK gereklidir.']);
                exit;
            }

            if ($action === 'create') {
                try {
                    $db->query("
                        INSERT INTO byk_sub_units (byk_category_id, name, description, created_at)
                        VALUES (?, ?, ?, NOW())
                    ", [$byk_category_id, $name, $description]);
                } catch (Exception $e) {
                    $db->query("
                        INSERT INTO alt_birimler (byk_id, alt_birim_adi, olusturma_tarihi)
                        VALUES (?, ?, NOW())
                    ", [$byk_category_id, $name]);
                }
                echo json_encode(['success' => true, 'message' => 'Alt birim başarıyla oluşturuldu.']);
            } else {
                $id = (int)($input['alt_birim_id'] ?? 0);
                if (!$id) {
                    echo json_encode(['success' => false, 'error' => 'ID bulunamadı.']);
                    exit;
                }
                
                try {
                    $db->query("
                        UPDATE byk_sub_units SET byk_category_id=?, name=?, description=?, updated_at=NOW() WHERE id=?
                    ", [$byk_category_id, $name, $description, $id]);
                } catch (Exception $e) {
                    $db->query("
                        UPDATE alt_birimler SET byk_id=?, alt_birim_adi=? WHERE alt_birim_id=?
                    ", [$byk_category_id, $name, $id]);
                }
                echo json_encode(['success' => true, 'message' => 'Alt birim başarıyla güncellendi.']);
            }
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($input['alt_birim_id'] ?? 0);
            
            try {
                $db->query("DELETE FROM byk_sub_units WHERE id = ?", [$id]);
            } catch (Exception $e) {}

            try {
                $db->query("DELETE FROM alt_birimler WHERE alt_birim_id = ?", [$id]);
            } catch (Exception $e) {}

            echo json_encode(['success' => true, 'message' => 'Alt birim başarıyla silindi.']);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Geçersiz POST aksiyonu.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
