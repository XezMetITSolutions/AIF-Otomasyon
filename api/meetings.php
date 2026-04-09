<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$auth = new Auth();
$db = Database::getInstance();

try {
    // Tablonun varlığını ve içeriğini kontrol et
    $allTables = $db->fetchAll("SHOW TABLES");
    $tableList = array_map(function($row) { return reset($row); }, $allTables);
    
    $check = $db->fetch("SHOW TABLES LIKE 'toplantilar'");
    if (!$check) {
        throw new Exception("Toplantılar tablosu bulunamadı. Mevcut tablolar: " . implode(', ', $tableList));
    }

    // Debug: Sütun isimlerini kontrol et
    $columns = $db->fetchAll("DESCRIBE toplantilar");
    $colNames = array_map(function($c) { return $c['Field']; }, $columns);

    // En temel sorgu
    $meetings = $db->fetchAll("SELECT * FROM toplantilar ORDER BY toplanti_id DESC LIMIT 20");

    // Eğer veri geldiyse sütun isimlerini eşleştirerek modele dönüştür
    $formattedMeetings = array_map(function($m) {
        return [
            'toplanti_id' => $m['toplanti_id'],
            'baslik' => $m['baslik'],
            'tarih' => $m['toplanti_tarihi'] ?? $m['tarih'] ?? null,
            'saat' => $m['saat'] ?? null,
            'durum' => $m['durum'] ?? 'bilinmiyor',
            'katilimci_sayisi' => $m['katilimci_sayisi'] ?? 0
        ];
    }, $meetings);

    echo json_encode([
        'success' => true,
        'meetings' => $formattedMeetings,
        'debug_cols' => $colNames,
        'count' => count($meetings)
    ]);
} catch (Exception $e) {
    error_log("Meetings API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
