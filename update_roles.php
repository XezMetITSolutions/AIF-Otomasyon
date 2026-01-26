<?php
/**
 * Role Update Script
 * 1. Updates all non-super_admin users to 'uye' role.
 * 2. Removes 'baskan' role from the 'roller' table.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "Starting role migration...<br>";

// 1. Get Role IDs
$roles = $db->fetchAll("SELECT * FROM roller");
$roleMap = [];
foreach ($roles as $r) {
    // Normalize role names for lookup
    $key = strtolower(trim($r['rol_adi']));
    $roleMap[$key] = $r['rol_id'];
    echo "Found Role: " . $r['rol_adi'] . " (ID: " . $r['rol_id'] . ")<br>";
}

$superAdminId = $roleMap['super_admin'] ?? null;
$uyeId = $roleMap['uye'] ?? null;
$baskanId = $roleMap['baskan'] ?? null; // Assuming 'baskan' is the name

if (!$superAdminId) {
    die("Error: 'super_admin' role not found.<br>");
}
if (!$uyeId) {
    die("Error: 'uye' role not found.<br>");
}

echo "Super Admin ID: $superAdminId<br>";
echo "Uye ID: $uyeId<br>";

// 2. Update Users
// "superadmin haric herkesi uye olarak yap"
// This means UPDATE kullanicilar SET rol_id = $uyeId WHERE rol_id != $superAdminId
try {
    $stmt = $db->query("
        UPDATE kullanicilar 
        SET rol_id = ? 
        WHERE rol_id != ?
    ", [$uyeId, $superAdminId]);
    
    echo "Updated users count: " . $stmt->rowCount() . "<br>";
} catch (Exception $e) {
    echo "Error updating users: " . $e->getMessage() . "<br>";
}

// 3. Remove 'baskan' role
// "rollden baskani kaldir"
if ($baskanId) {
    try {
        $db->query("DELETE FROM roller WHERE rol_id = ?", [$baskanId]);
        echo "Deleted role 'baskan' (ID: $baskanId).<br>";
    } catch (Exception $e) {
        echo "Error deleting baskan role: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Role 'baskan' not found in roller table (or already deleted).<br>";
}

echo "Migration complete.<br>";
?>
