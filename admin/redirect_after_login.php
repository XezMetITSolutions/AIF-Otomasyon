<?php
require_once 'auth.php';
SessionManager::requireLogin();
SessionManager::redirectBasedOnRole();
exit;
?>



