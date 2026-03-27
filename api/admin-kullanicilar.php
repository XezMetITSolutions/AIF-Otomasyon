<?php
/**
 * API - Yönetim: Kullanıcılar (Next.js için)
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

// Yalnızca Superadmin veya Module Permission kontrolü
if (!$auth->isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkiniz bulunmamaktadır.']);
    exit;
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'list') {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = 20;
            $offset = ($page - 1) * $perPage;

            $search = $_GET['search'] ?? '';
            $roleFilter = $_GET['rol'] ?? '';
            $bykFilter = $_GET['byk'] ?? '';
            $statusFilter = $_GET['status'] ?? '1';

            $params = [];
            $where = [];

            if ($statusFilter !== 'all') {
                $where[] = "k.aktif = " . (int)$statusFilter;
            } else {
                $where[] = "1=1";
            }

            if ($search) {
                $where[] = "(LOWER(k.ad) LIKE LOWER(?) OR LOWER(k.soyad) LIKE LOWER(?) OR LOWER(k.email) LIKE LOWER(?) OR LOWER(CONCAT(k.ad, ' ', k.soyad)) LIKE LOWER(?))";
                $searchVal = "%" . mb_strtolower($search, 'UTF-8') . "%";
                array_push($params, $searchVal, $searchVal, $searchVal, $searchVal);
            }

            if ($roleFilter) {
                $where[] = "r.rol_adi = ?";
                $params[] = $roleFilter;
            }

            if ($bykFilter) {
                try {
                    $db->query("SELECT 1 FROM kullanici_byklar LIMIT 1");
                    $where[] = "EXISTS (SELECT 1 FROM kullanici_byklar kb WHERE kb.kullanici_id = k.kullanici_id AND kb.byk_id = ?)";
                    $params[] = $bykFilter;
                } catch (Exception $e) {
                    $where[] = "k.byk_id = ?";
                    $params[] = $bykFilter;
                }
            }

            $whereClause = implode(' AND ', $where);

            $totalQuery = "SELECT COUNT(*) as count FROM kullanicilar k LEFT JOIN roller r ON k.rol_id = r.rol_id LEFT JOIN byk b ON k.byk_id = b.byk_id WHERE $whereClause";
            $total = $db->fetch($totalQuery, $params)['count'];
            $totalPages = ceil($total / $perPage);

            try {
                $sql = "
                    SELECT k.kullanici_id, k.ad, k.soyad, k.email, k.telefon, k.aktif, k.divan_uyesi,
                           COALESCE(r.rol_adi, 'Tanımsız') as rol_adi,
                           (SELECT GROUP_CONCAT(COALESCE(bc.name, b2.byk_adi) SEPARATOR ', ') 
                            FROM kullanici_byklar kb 
                            JOIN byk b2 ON kb.byk_id = b2.byk_id 
                            LEFT JOIN byk_categories bc ON b2.byk_kodu = bc.code
                            WHERE kb.kullanici_id = k.kullanici_id) as tum_byklar,
                           COALESCE(bc_dir.name, bc_via_b.name, b.byk_adi, '-') as byk_adi,
                           COALESCE(bc_dir.code, bc_via_b.code, b.byk_kodu, '') as byk_kodu,
                           COALESCE(bc_dir.color, bc_via_b.color, b.renk_kodu, '#009872') as byk_renk,
                           ab.alt_birim_adi AS gorev_adi
                    FROM kullanicilar k
                    LEFT JOIN roller r ON k.rol_id = r.rol_id
                    LEFT JOIN byk b ON k.byk_id = b.byk_id
                    LEFT JOIN byk_categories bc_dir ON k.byk_id = bc_dir.id
                    LEFT JOIN byk_categories bc_via_b ON b.byk_kodu = bc_via_b.code
                    LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
                    WHERE $whereClause
                    ORDER BY k.olusturma_tarihi DESC
                    LIMIT ? OFFSET ?
                ";
                try {
                    $kullanicilar = $db->fetchAll($sql, array_merge($params, [$perPage, $offset]));
                } catch (Exception $e) {
                    $sqlFallback = str_replace("(SELECT GROUP_CONCAT(COALESCE(bc.name, b2.byk_adi) SEPARATOR ', ') 
                            FROM kullanici_byklar kb 
                            JOIN byk b2 ON kb.byk_id = b2.byk_id 
                            LEFT JOIN byk_categories bc ON b2.byk_kodu = bc.code
                            WHERE kb.kullanici_id = k.kullanici_id) as tum_byklar,", "'' as tum_byklar,", $sql);
                    $kullanicilar = $db->fetchAll($sqlFallback, array_merge($params, [$perPage, $offset]));
                }
            } catch (Exception $e) {
                $kullanicilar = $db->fetchAll("SELECT k.kullanici_id, k.ad, k.soyad, k.email, k.telefon, k.aktif, k.divan_uyesi, COALESCE(r.rol_adi, 'Tanımsız') as rol_adi, b.byk_adi, ab.alt_birim_adi AS gorev_adi FROM kullanicilar k LEFT JOIN roller r ON k.rol_id = r.rol_id LEFT JOIN byk b ON k.byk_id = b.byk_id LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id WHERE $whereClause ORDER BY k.olusturma_tarihi DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));
            }

            $roller = $db->fetchAll("SELECT * FROM roller ORDER BY rol_yetki_seviyesi DESC");
            try {
                $bykList = $db->fetchAll("SELECT id as byk_id, name as byk_adi, code as byk_kodu FROM byk_categories WHERE code IN ('AT', 'GT', 'KGT', 'gt', 'KT') ORDER BY code");
            } catch (Exception $e) {
                $bykList = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk WHERE aktif = 1 AND byk_kodu IN ('AT', 'GT', 'KGT', 'gt', 'KT') ORDER BY byk_adi");
            }

            echo json_encode([
                'success' => true,
                'users' => $kullanicilar,
                'total' => $total,
                'totalPages' => $totalPages,
                'page' => $page,
                'constants' => [
                    'roles' => $roller,
                    'byks' => $bykList
                ]
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Geçersiz GET aksiyonu.']);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $ad = $input['ad'] ?? '';
            $soyad = $input['soyad'] ?? '';
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            $rol_id = (int)($input['rol_id'] ?? 0);
            $byk_id = $input['byk_id'] ? (int)$input['byk_id'] : null;
            $telefon = $input['telefon'] ?? '';
            $divan_uyesi = (int)($input['divan_uyesi'] ?? 0);
            $aktif = (int)($input['aktif'] ?? 1);

            if (!$ad || !$soyad || !$email || !$rol_id) {
                echo json_encode(['success' => false, 'error' => 'Gerekli alanları doldurunuz.']);
                exit;
            }

            if ($action === 'create') {
                if (!$password) {
                    echo json_encode(['success' => false, 'error' => 'Şifre gereklidir.']);
                    exit;
                }
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $db->query("
                    INSERT INTO kullanicilar (ad, soyad, email, sifre, rol_id, byk_id, telefon, divan_uyesi, aktif, olusturma_tarihi)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ", [$ad, $soyad, $email, $hashed, $rol_id, $byk_id, $telefon, $divan_uyesi, $aktif]);
                
                echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla oluşturuldu.']);
            } else {
                $id = (int)($input['kullanici_id'] ?? 0);
                if (!$id) {
                    echo json_encode(['success' => false, 'error' => 'Kullanıcı ID bulunamadı.']);
                    exit;
                }
                
                $sql = "UPDATE kullanicilar SET ad=?, soyad=?, email=?, rol_id=?, byk_id=?, telefon=?, divan_uyesi=?, aktif=? WHERE kullanici_id=?";
                $params = [$ad, $soyad, $email, $rol_id, $byk_id, $telefon, $divan_uyesi, $aktif, $id];
                
                if ($password) {
                    $sql = "UPDATE kullanicilar SET ad=?, soyad=?, email=?, sifre=?, rol_id=?, byk_id=?, telefon=?, divan_uyesi=?, aktif=? WHERE kullanici_id=?";
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $params = [$ad, $soyad, $email, $hashed, $rol_id, $byk_id, $telefon, $divan_uyesi, $aktif, $id];
                }
                
                $db->query($sql, $params);
                echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla güncellendi.']);
            }
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($input['kullanici_id'] ?? 0);
            $currentUser = $auth->getUser();
            if ($id === $currentUser['id']) {
                echo json_encode(['success' => false, 'error' => 'Kendi hesabınızı silemezsiniz.']);
                exit;
            }
            $db->query("DELETE FROM kullanicilar WHERE kullanici_id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => 'Kullanıcı silindi.']);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Geçersiz POST aksiyonu.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
