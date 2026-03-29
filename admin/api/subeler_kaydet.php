<?php
/**
 * Şube Kayıt API
 */
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Middleware.php';
require_once __DIR__ . '/../../classes/Database.php';

Middleware::requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    
    $sube_adi = $_POST['sube_adi'] ?? '';
    $adres = $_POST['adres'] ?? '';
    $sehir = $_POST['sehir'] ?? '';
    $posta_kodu = $_POST['posta_kodu'] ?? '';
    
    if (empty($sube_adi) || empty($adres)) {
        header('Location: ../subeler.php?status=error&message=Gerekli alanları doldurunuz.');
        exit;
    }
    
    try {
        $db->query(
            "INSERT INTO subeler (sube_adi, adres, sehir, posta_kodu) VALUES (?, ?, ?, ?)",
            [$sube_adi, $adres, $sehir, $posta_kodu]
        );
        header('Location: ../subeler.php?status=success&message=Şube başarıyla eklendi.');
    } catch (Exception $e) {
        header('Location: ../subeler.php?status=error&message=' . urlencode($e->getMessage()));
    }
} else {
    header('Location: ../subeler.php');
}
