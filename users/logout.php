<?php
require_once 'auth.php';

// Kullanıcıyı çıkış yap
SessionManager::logout();

// Cache temizleme header'ları
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Ana sayfaya yönlendir
header('Location: ../index.php');
exit;
?>
