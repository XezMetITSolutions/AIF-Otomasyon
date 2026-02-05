<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
$user = $auth->getUser();

if (!$user || $user['role'] !== 'super_admin') {
    die("Super Admin required.");
}

echo "<pre>";
require_once __DIR__ . '/debug_groups.php';
echo "</pre>";
