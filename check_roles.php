<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();
$auth = new Auth();

echo "Current Session Role: " . ($_SESSION['user_role'] ?? 'NOT LOGGED IN') . "\n";
echo "Current Session Level: " . ($_SESSION['role_level'] ?? 'N/A') . "\n";

echo "\nAll Roles Table:\n";
$roles = $db->fetchAll("SELECT * FROM roller");
print_r($roles);

$user = $auth->getUser();
echo "\nLogged User Detay:\n";
print_r($user);
