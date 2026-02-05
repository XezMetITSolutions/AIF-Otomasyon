<?php
/**
 * Eksik kullanıcıları ekleme Wrapper (Web)
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$user = $auth->getUser();

if (!$user || $user['role'] !== 'super_admin') {
    die("Bu işlemi yapmak için Super Admin yetkisi gerekir.");
}

echo "<pre>";
require_once __DIR__ . '/database/add-missing-users.php';
echo "</pre>";
