<?php
// Custom DB connection for update
$config = [
    'host' => '127.0.0.1',
    'dbname' => 'd045d2b0',
    'username' => 'd045d2b0',
    'password' => '01528797Mb##',
    'charset' => 'utf8mb4'
];

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM kullanicilar LIKE 'iban'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE kullanicilar ADD COLUMN iban VARCHAR(34) DEFAULT NULL");
        echo "IBAN column added successfully.";
    } else {
        echo "IBAN column already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
