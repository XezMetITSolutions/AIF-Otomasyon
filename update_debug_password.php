<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'admin/includes/database.php';

$db = Database::getInstance();

// debug.test kullanıcısının şifresini güncelle
$newPassword = 'Test123456';
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

$result = $db->update('users', 
    ['password_hash' => $newHash, 'updated_at' => date('Y-m-d H:i:s')], 
    'username = ?', 
    ['debug.test']
);

if ($result) {
    echo "✅ Şifre başarıyla güncellendi!\n";
    echo "Kullanıcı: debug.test\n";
    echo "Yeni şifre: Test123456\n";
    echo "Yeni hash: " . $newHash . "\n";
} else {
    echo "❌ Şifre güncellenirken hata oluştu!\n";
}

// Test et
$user = $db->fetchOne("SELECT * FROM users WHERE username = ?", ['debug.test']);
if ($user && password_verify($newPassword, $user['password_hash'])) {
    echo "✅ Şifre doğrulaması başarılı!\n";
} else {
    echo "❌ Şifre doğrulaması başarısız!\n";
}
?>
