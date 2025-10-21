<?php
// Temporary password reset utility (remove after use)
require_once 'includes/database.php';

$token = $_GET['token'] ?? '';
if ($token !== 'RESET01528797') {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$username = $_GET['u'] ?? 'AIF-Admin';
$newPass  = $_GET['p'] ?? 'AIF571#';

try {
    $db = Database::getInstance();
    $user = $db->fetchOne('SELECT id, username FROM users WHERE username = ? LIMIT 1', [$username]);
    if (!$user) {
        echo 'User not found: ' . htmlspecialchars($username);
        exit;
    }

    $hash = password_hash($newPass, PASSWORD_DEFAULT);
    // Use positional params update
    $db->update('users', [
        'password_hash' => $hash,
        'must_change_password' => 1,
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$user['id']]);

    echo 'Password reset OK for @' . htmlspecialchars($username) . '. New: ' . htmlspecialchars($newPass);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>



