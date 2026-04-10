<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/init.php';

$auth = new Auth();
$db = Database::getInstance();

try {
    $id = $_GET['id'] ?? null;
    if (!$id) throw new Exception('ID gereklidir');

    // Basic Info
    $toplanti = $db->fetch("
        SELECT t.*, b.byk_adi, b.byk_kodu
        FROM toplantilar t
        LEFT JOIN byk b ON t.byk_id = b.byk_id
        WHERE t.toplanti_id = ?
    ", [$id]);

    if (!$toplanti) throw new Exception('Toplantı bulunamadı');

    // Agenda
    $gundem = $db->fetchAll("SELECT * FROM toplanti_gundem WHERE toplanti_id = ? ORDER BY sira_no", [$id]);

    // Participants
    $katilimcilar = $db->fetchAll("
        SELECT tk.*, k.ad, k.soyad, k.email
        FROM toplanti_katilimcilar tk
        INNER JOIN kullanicilar k ON tk.kullanici_id = k.kullanici_id
        WHERE tk.toplanti_id = ?
        ORDER BY k.ad, k.soyad
    ", [$id]);

    // Decisions
    $kararlar = $db->fetchAll("SELECT * FROM toplanti_kararlar WHERE toplanti_id = ?", [$id]);

    echo json_encode([
        'success' => true,
        'meeting' => $toplanti,
        'gundem' => $gundem,
        'katilimcilar' => $katilimcilar,
        'kararlar' => $kararlar
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
