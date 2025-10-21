<?php
session_start();

// Cache temizleme header'ları
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Session'ı temizle
session_destroy();

// Login sayfasına yönlendir
header('Location: index.php');
exit;
?>


