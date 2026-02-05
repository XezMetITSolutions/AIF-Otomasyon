<?php
$host = '127.0.0.1';
$dbname = 'd0451622';
$username = 'd0451622';
$password = '01528797Mb##';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "--- USERS ---\n";
    $users = $pdo->query("SELECT kullanici_id, ad, soyad FROM kullanicilar")->fetchAll();
    foreach ($users as $u) {
        echo $u['kullanici_id'] . ": " . $u['ad'] . " " . $u['soyad'] . "\n";
    }

    echo "\n--- BYKS ---\n";
    $byks = $pdo->query("SELECT byk_id, byk_adi FROM byk")->fetchAll();
    foreach ($byks as $b) {
        echo $b['byk_id'] . ": " . $b['byk_adi'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
