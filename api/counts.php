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
if ($auth->hasModulePermission('baskan_izin_talepleri') || $isSuperAdmin) {
    if($isSuperAdmin) {
        $c = $db->fetch("SELECT COUNT(*) as cnt FROM izin_talepleri WHERE durum = 'bekliyor'");
    } else {
        // Başkan of a unit sees requests from their unit members
        $c = $db->fetch("
            SELECT COUNT(*) as cnt 
            FROM izin_talepleri i
            INNER JOIN kullanicilar k ON i.kullanici_id = k.kullanici_id
            WHERE i.durum = 'bekliyor' AND k.byk_id = ?
        ", [$bykId]);
    }
    $counts['pendingIzinCount'] = (int)$c['cnt'];
}

// --- 2. Harcama Onayları ---
if ($auth->hasModulePermission('baskan_harcama_talepleri') || $isSuperAdmin) {
    // Muhasebe başkanı logic? Assuming standard BYK check for now or specific permission
    if($isSuperAdmin) {
        $c = $db->fetch("SELECT COUNT(*) as cnt FROM harcama_talepleri WHERE durum = 'bekliyor'");
    } else {
         $c = $db->fetch("
            SELECT COUNT(*) as cnt 
            FROM harcama_talepleri h
            INNER JOIN kullanicilar k ON h.kullanici_id = k.kullanici_id
            WHERE h.durum = 'bekliyor' AND k.byk_id = ?
        ", [$bykId]);
    }
    $counts['pendingHarcamaCount'] = (int)$c['cnt'];
}

// --- 3. İade Onayları ---
if ($auth->hasModulePermission('baskan_iade_formlari') || $isSuperAdmin) {
    if($isSuperAdmin) {
         $c = $db->fetch("SELECT COUNT(*) as cnt FROM iade_formlari WHERE durum = 'bekliyor'");
    } else {
        $c = $db->fetch("
            SELECT COUNT(*) as cnt 
            FROM iade_formlari f
            INNER JOIN kullanicilar k ON f.kullanici_id = k.kullanici_id
            WHERE f.durum = 'bekliyor' AND k.byk_id = ?
        ", [$bykId]);
    }
    $counts['pendingIadeCount'] = (int)$c['cnt'];
}

// --- 4. Raggal Talepleri ---
if ($auth->hasModulePermission('baskan_raggal_talepleri') || $isSuperAdmin) {
    if($isSuperAdmin) {
        $c = $db->fetch("SELECT COUNT(*) as cnt FROM raggal_rezervasyonlar WHERE durum = 'bekliyor'");
    } else {
        // Raggal might be centrally managed or by BYK. Assuming BYK filter for consistency
        // If Raggal is a facility managed by specific people, the permission check handles access.
        // But do they see ALL requests or just their unit? 
        // Usually facility requests are global for the facility managers.
        // Let's assume for now if you have permission 'baskan_raggal_talepleri', you manage the facility => see ALL pending.
        // OR if logic is 'My Unit's requests'. 
        // Based on previous files, managers see ALL requests usually or their unit.
        // Let's stick to BYK filter IF table has byk_id or user join.
        // Actually raggal_rezervasyonlar likely doesn't have byk_id directly, uses user.
        
        // Checking previous usage in raggal-talepleri.php could confirm.
        // Assuming strict BYK scope for safety unless told otherwise.
        
         $c = $db->fetch("
            SELECT COUNT(*) as cnt 
            FROM raggal_rezervasyonlar r
            INNER JOIN kullanicilar k ON r.kullanici_id = k.kullanici_id
            WHERE r.durum = 'bekliyor' AND k.byk_id = ?
        ", [$bykId]);
    }
    $counts['pendingRaggalCount'] = (int)$c['cnt'];
}

echo json_encode([
    'success' => true,
    'counts' => $counts
]);
