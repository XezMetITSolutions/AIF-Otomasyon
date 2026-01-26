<?php
// admin_config.php - Kullanıcı bilgilerini JSON dosyasından yükler ve kaydeder.

$usersFile = __DIR__ . '/admin_users.json';

// Eğer JSON dosyası yoksa varsayılan kullanıcıları oluştur
if (!file_exists($usersFile)) {
    $initialUsers = [
        'MuhasebeAT' => ['password' => 'AIF571#', 'force_password_change' => true],
        'MuhasebeGT' => ['password' => 'AIF571#', 'force_password_change' => true],
        'MuhasebeKGT' => ['password' => 'AIF571#', 'force_password_change' => true],
        'MuhasebeKT' => ['password' => 'AIF571#', 'force_password_change' => true]
    ];
    file_put_contents($usersFile, json_encode($initialUsers, JSON_PRETTY_PRINT));
}

$users = json_decode(file_get_contents($usersFile), true);

function save_users($users) {
    global $usersFile;
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}
