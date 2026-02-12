<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Yetki kontrolü (Başkan veya Admin)
Middleware::requireUye();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$toplanti_id = $input['toplanti_id'] ?? null;
$gundem_id = $input['gundem_id'] ?? null; // Can be null or 0 for General
$karar_metni = trim($input['karar_metni'] ?? '');

if (!$toplanti_id) {
    echo json_encode(['success' => false, 'message' => 'Toplantı ID gereklidir']);
    exit;
}

$db = Database::getInstance();

try {
    // Check if a decision already exists for this agenda item in this meeting
    // We assume 1 decision per agenda item for this "Notes Style" workflow
    
    // Convert empty gundem_id to 0 for SQL safety if needed, 
    // but better to handle NULL in DB.
    // However, our table schema likely stores specific IDs.
    // If gundem_id is provided, look for it. If not, look for gundem_id IS NULL or 0? 
    // Let's assume passed gundem_id matches the DB column.
    
    $query = "SELECT karar_id FROM toplanti_kararlar WHERE toplanti_id = ?";
    $params = [$toplanti_id];
    
    if ($gundem_id) {
        $query .= " AND gundem_id = ?";
        $params[] = $gundem_id;
    } else {
        $query .= " AND (gundem_id IS NULL OR gundem_id = 0)";
    }
    
    // Sort by ID desc to get the latest if multiple exist (though we aim for one)
    $query .= " ORDER BY karar_id DESC LIMIT 1";
    
    $existing = $db->fetch($query, $params);

    if ($existing) {
        // Update existing decision
        $db->query("UPDATE toplanti_kararlar SET karar_metni = ? WHERE karar_id = ?", [$karar_metni, $existing['karar_id']]);
        $message = "Karar güncellendi";
    } else {
        // Create new decision
        // Default values
        $baslik = $gundem_id ? "Gündem Kararı" : "Genel Karar";
        $karar_sonucu = 'kabul'; // Default
        $oylama_yapildi = 0;
        
        $db->query("
            INSERT INTO toplanti_kararlar 
            (toplanti_id, gundem_id, baslik, karar_metni, karar_sonucu, oylama_yapildi)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $toplanti_id, 
            $gundem_id ?: null, 
            $baslik, 
            $karar_metni, 
            $karar_sonucu, 
            $oylama_yapildi
        ]);
        $message = "Karar oluşturuldu";
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
