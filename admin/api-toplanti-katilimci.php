<?php
/**
 * API - Toplantı Katılımcı İşlemleri
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    $user = $auth->getUser();

    if (!$user) {
        http_response_code(401);
        throw new Exception('Oturum açmanız gerekiyor');
    }

    $currentUserId = $user['id'] ?? $user['kullanici_id'];
    $db = Database::getInstance();
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? null;

    if (!$action) {
        throw new Exception('Action gereklidir');
    }

    switch ($action) {
        case 'add':
            $toplanti_id = $input['toplanti_id'] ?? null;
            $kullanici_ids = $input['kullanici_id'] ?? null;
            $katilim_durumu = $input['katilim_durumu'] ?? 'beklemede';

            if (!$toplanti_id || empty($kullanici_ids)) {
                throw new Exception('Toplantı ID ve Kullanıcı(lar) gereklidir');
            }

            // Yetki Kontrolü: Sadece oluşturan ekleyebilir
            $toplanti = $db->fetch("SELECT olusturan_id FROM toplantilar WHERE toplanti_id = ?", [$toplanti_id]);
            if (!$toplanti)
                throw new Exception('Toplantı bulunamadı');

            if ($toplanti['olusturan_id'] != $currentUserId) {
                throw new Exception('Sadece toplantıyı oluşturan kişi katılımcı ekleyebilir');
            }

            // Normalize to array
            if (!is_array($kullanici_ids)) {
                $kullanici_ids = [$kullanici_ids];
            }

            $added_count = 0;
            foreach ($kullanici_ids as $kid) {
                $exists = $db->fetch("SELECT katilimci_id FROM toplanti_katilimcilar WHERE toplanti_id = ? AND kullanici_id = ?", [$toplanti_id, $kid]);
                if (!$exists) {
                    $db->query("INSERT INTO toplanti_katilimcilar (toplanti_id, kullanici_id, katilim_durumu) VALUES (?, ?, ?)", [$toplanti_id, $kid, $katilim_durumu]);
                    $added_count++;
                }
            }

            // Update counts
            $count = $db->fetch("SELECT COUNT(*) as total FROM toplanti_katilimcilar WHERE toplanti_id = ?", [$toplanti_id]);
            $db->query("UPDATE toplantilar SET katilimci_sayisi = ? WHERE toplanti_id = ?", [$count['total'], $toplanti_id]);

            echo json_encode(['success' => true, 'message' => "$added_count katılımcı başarıyla eklendi"]);
            break;

        case 'update':
            $katilimci_id = $input['katilimci_id'] ?? null;
            $katilim_durumu = $input['katilim_durumu'] ?? null;

            if (!$katilimci_id || !$katilim_durumu) {
                throw new Exception('Katılımcı ID ve durum gereklidir');
            }

            // Yetki Kontrolü: Oluşturan herkesi, Diğerleri sadece kendini
            $katilimci = $db->fetch("SELECT * FROM toplanti_katilimcilar WHERE katilimci_id = ?", [$katilimci_id]);
            if (!$katilimci)
                throw new Exception('Katılımcı bulunamadı');

            $toplanti = $db->fetch("SELECT olusturan_id FROM toplantilar WHERE toplanti_id = ?", [$katilimci['toplanti_id']]);

            $isCreator = ($toplanti['olusturan_id'] == $currentUserId);
            $isSelf = ($katilimci['kullanici_id'] == $currentUserId);

            if (!$isCreator && !$isSelf) {
                throw new Exception('Başkalarının katılım durumunu değiştiremezsiniz');
            }

            $db->query("UPDATE toplanti_katilimcilar SET katilim_durumu = ? WHERE katilimci_id = ?", [$katilim_durumu, $katilimci_id]);

            echo json_encode(['success' => true, 'message' => 'Katılım durumu güncellendi']);
            break;

        case 'delete':
            $katilimci_id = $input['katilimci_id'] ?? null;

            if (!$katilimci_id) {
                throw new Exception('Katılımcı ID gereklidir');
            }

            // Yetki Kontrolü: Sadece oluşturan silebilir
            $katilimci = $db->fetch("SELECT toplanti_id FROM toplanti_katilimcilar WHERE katilimci_id = ?", [$katilimci_id]);
            if (!$katilimci)
                throw new Exception('Katılımcı bulunamadı');

            $toplanti = $db->fetch("SELECT olusturan_id FROM toplantilar WHERE toplanti_id = ?", [$katilimci['toplanti_id']]);

            if ($toplanti['olusturan_id'] != $currentUserId) {
                throw new Exception('Sadece toplantıyı oluşturan kişi katılımcı silebilir');
            }

            $db->query("DELETE FROM toplanti_katilimcilar WHERE katilimci_id = ?", [$katilimci_id]);

            // Update counts
            $count = $db->fetch("SELECT COUNT(*) as total FROM toplanti_katilimcilar WHERE toplanti_id = ?", [$katilimci['toplanti_id']]);
            $db->query("UPDATE toplantilar SET katilimci_sayisi = ? WHERE toplanti_id = ?", [$count['total'], $katilimci['toplanti_id']]);

            echo json_encode(['success' => true, 'message' => 'Katılımcı silindi']);
            break;

        default:
            throw new Exception('Geçersiz action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
