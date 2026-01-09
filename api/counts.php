<?php
/**
 * Pending Counts API Endpoint
 * Returns counts of pending items for the sidebar badges
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

$auth = new Auth();
$user = $auth->getUser();

if (!$user) {
    echo json_encode(['success' => false]);
    exit;
}

$db = Database::getInstance();
$counts = [];

// Determine permissions
$isSuperAdmin = $user['role'] === 'super_admin';
$isBaskan = $user['role'] === 'uye'; // Using existing logic (though usually baskan is a specific detail)
$bykId = $user['byk_id'];

// --- 1. İzin Onayları ---
try {
    $c = $db->fetch("SELECT COUNT(*) as cnt FROM izin_talepleri WHERE durum = 'bekliyor'");
    $counts['pendingIzinCount'] = (int)($c['cnt'] ?? 0);
} catch (Exception $e) {}

// --- 2. Harcama Onayları ---
try {
    $c = $db->fetch("SELECT COUNT(*) as cnt FROM harcama_talepleri WHERE durum = 'bekliyor'");
    $counts['pendingHarcamaCount'] = (int)($c['cnt'] ?? 0);
} catch (Exception $e) {}

// --- 3. İade Onayları ---
try {
    $c = $db->fetch("SELECT COUNT(*) as cnt FROM iade_formlari WHERE durum = 'bekliyor'");
    $counts['pendingIadeCount'] = (int)($c['cnt'] ?? 0);
} catch (Exception $e) {}

// --- 4. Raggal Talepleri ---
try {
    // Return GLOBAL count for everyone. Permission to see/act is handled by Sidebar visibility.
    $c = $db->fetch("SELECT COUNT(*) as cnt FROM raggal_talepleri WHERE durum = 'bekliyor'");
    $counts['pendingRaggalCount'] = (int)($c['cnt'] ?? 0);
} catch (Exception $e) {}

echo json_encode([
    'success' => true,
    'counts' => $counts
]);
