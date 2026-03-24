<?php
/**
 * API - Yönetim: Panel Yetkileri (Next.js için)
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
$moduleDefinitions = require __DIR__ . '/../config/baskan_modules.php';
$assignableModules = array_filter($moduleDefinitions, fn($info) => ($info['category'] ?? '') !== 'uye');

try {
    if ($method === 'GET') {
        $uyeler = $db->fetchAll("
            SELECT k.kullanici_id, k.ad, k.soyad,
                   COALESCE(bc.name, b.byk_adi, '-') AS byk_adi,
                   ab.alt_birim_adi AS gorev_adi
            FROM kullanicilar k
            INNER JOIN roller r ON k.rol_id = r.rol_id
            LEFT JOIN byk b ON k.byk_id = b.byk_id
            LEFT JOIN byk_categories bc ON b.byk_kodu = bc.code
            LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
            WHERE r.rol_adi != ?
            ORDER BY k.ad, k.soyad
        ", [Auth::ROLE_SUPER_ADMIN]);

        $allPermissions = [];
        try {
            $rows = $db->fetchAll("SELECT kullanici_id, module_key, can_view FROM baskan_modul_yetkileri");
            foreach ($rows as $row) {
                $allPermissions[$row['kullanici_id']][$row['module_key']] = (bool)$row['can_view'];
            }
        } catch (Exception $e) {}

        // Format rules dynamically based on definition defaults
        foreach ($uyeler as &$uye) {
            $uId = $uye['kullanici_id'];
            $uye['permissions'] = [];
            foreach ($assignableModules as $key => $module) {
                $default = (bool)($module['default'] ?? true);
                $isChecked = isset($allPermissions[$uId][$key]) ? $allPermissions[$uId][$key] : $default;
                $uye['permissions'][$key] = $isChecked;
            }
        }
        unset($uye);

        echo json_encode([
            'success' => true,
            'users' => $uyeler,
            'modules' => $assignableModules
        ]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'save_permissions') {
            $permissions = $input['permissions'] ?? [];
            if (!is_array($permissions)) {
                 echo json_encode(['success' => false, 'error' => 'Geçersiz veri formatı.']);
                 exit;
            }

            $uyeler = $db->fetchAll("SELECT kullanici_id FROM kullanicilar k INNER JOIN roller r ON k.rol_id = r.rol_id WHERE r.rol_adi != ?", [Auth::ROLE_SUPER_ADMIN]);
            $validUserIds = array_column($uyeler, 'kullanici_id');

            $db->getConnection()->beginTransaction();
            $insertValues = [];
            $params = [];

            foreach ($validUserIds as $uId) {
                foreach ($assignableModules as $moduleKey => $mInfo) {
                    $isChecked = !empty($permissions[$uId][$moduleKey]) ? 1 : 0;
                    $insertValues[] = "(?, ?, ?)";
                    $params[] = $uId;
                    $params[] = $moduleKey;
                    $params[] = $isChecked;
                }
            }

            if (!empty($insertValues)) {
                $chunks = array_chunk($insertValues, 500);
                $paramChunks = array_chunk($params, 500 * 3);
                foreach ($chunks as $i => $chunk) {
                    $sql = "INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view) 
                            VALUES " . implode(', ', $chunk) . "
                            ON DUPLICATE KEY UPDATE can_view = VALUES(can_view)";
                    $db->query($sql, $paramChunks[$i]);
                }
            }

            $db->getConnection()->commit();
            echo json_encode(['success' => true, 'message' => 'Tüm yetkiler başarıyla matrise işlendi.']);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Geçersiz POST aksiyonu.']);
        exit;
    }

} catch (Exception $e) {
    if ($db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
