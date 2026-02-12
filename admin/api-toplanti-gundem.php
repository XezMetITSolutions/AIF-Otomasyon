<?php
/**
 * API - Toplantı Gündem İşlemleri
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

try {
    Middleware::requireRole(['super_admin', 'uye']);
    
    $db = Database::getInstance();
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Action gereklidir');
    }
    
    switch ($action) {
        case 'add':
            $toplanti_id = $input['toplanti_id'] ?? null;
            $sira_no = $input['sira_no'] ?? 1;
            $baslik = trim($input['baslik'] ?? '');
            $aciklama = trim($input['aciklama'] ?? '');
            $durum = $input['durum'] ?? 'beklemede';
            
            if (!$toplanti_id || !$baslik) {
                throw new Exception('Toplantı ID ve başlık gereklidir');
            }
            
            $db->query("
                INSERT INTO toplanti_gundem (toplanti_id, sira_no, baslik, aciklama, durum)
                VALUES (?, ?, ?, ?, ?)
            ", [$toplanti_id, $sira_no, $baslik, $aciklama, $durum]);
            
            $gundem_id = $db->lastInsertId();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Gündem maddesi eklendi',
                'gundem_id' => $gundem_id
            ]);
            break;

        case 'add_bulk':
            $toplanti_id = $input['toplanti_id'] ?? null;
            $items = $input['items'] ?? [];

            if (!$toplanti_id || empty($items)) {
                throw new Exception('Toplantı ID ve maddeler gereklidir');
            }

            // Get current max sort order
            $max_sort = $db->fetch("SELECT MAX(sira_no) as max_sira FROM toplanti_gundem WHERE toplanti_id = ?", [$toplanti_id]);
            $current_sort = ($max_sort['max_sira'] ?? 0) + 1;

            $stmt = $db->getConnection()->prepare("INSERT INTO toplanti_gundem (toplanti_id, sira_no, baslik, durum) VALUES (?, ?, ?, 'beklemede')");

            foreach ($items as $item) {
                // Check if already exists to avoid duplicates (optional but good)
                $exists = $db->fetch("SELECT 1 FROM toplanti_gundem WHERE toplanti_id = ? AND baslik = ?", [$toplanti_id, $item]);
                if (!$exists) {
                    $stmt->execute([$toplanti_id, $current_sort, $item]);
                    $current_sort++;
                }
            }
            
            echo json_encode(['success' => true, 'message' => count($items) . ' gündem maddesi işlendi']);
            break;
            
        case 'update':
            $gundem_id = $input['gundem_id'] ?? null;
            $sira_no = $input['sira_no'] ?? null;
            $baslik = trim($input['baslik'] ?? '');
            $aciklama = trim($input['aciklama'] ?? '');
            $durum = $input['durum'] ?? null;
            
            if (!$gundem_id) {
                throw new Exception('Gündem ID gereklidir');
            }
            
            $db->query("
                UPDATE toplanti_gundem 
                SET sira_no = ?, baslik = ?, aciklama = ?, durum = ?
                WHERE gundem_id = ?
            ", [$sira_no, $baslik, $aciklama, $durum, $gundem_id]);
            
            echo json_encode(['success' => true, 'message' => 'Gündem maddesi güncellendi']);
            break;
            
        case 'delete':
            $gundem_id = $input['gundem_id'] ?? null;
            
            if (!$gundem_id) {
                throw new Exception('Gündem ID gereklidir');
            }
            
            $db->query("DELETE FROM toplanti_gundem WHERE gundem_id = ?", [$gundem_id]);
            
            echo json_encode(['success' => true, 'message' => 'Gündem maddesi silindi']);
            break;
            
        case 'get':
            $gundem_id = $_GET['gundem_id'] ?? null;
            
            if (!$gundem_id) {
                throw new Exception('Gündem ID gereklidir');
            }
            
            $gundem = $db->fetch("SELECT * FROM toplanti_gundem WHERE gundem_id = ?", [$gundem_id]);
            
            if (!$gundem) {
                throw new Exception('Gündem maddesi bulunamadı');
            }
            
            echo json_encode(['success' => true, 'gundem' => $gundem]);
            break;
            
        default:
            throw new Exception('Geçersiz action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
