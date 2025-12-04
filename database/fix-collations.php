<?php
/**
 * Manuel kolasyon düzeltme aracı
 * Süper admin hesabıyla çalıştırılmalıdır.
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$csrfToken = Middleware::generateCSRF();

$tables = [
    'roller',
    'kullanicilar',
    'byk',
    'byk_categories',
    'baskan_modul_yetkileri',
];

$sqlStatements = array_map(function ($table) {
    return sprintf(
        "ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
        $table
    );
}, $tables);

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Güvenlik doğrulaması başarısız oldu.';
    } else {
        foreach ($sqlStatements as $statement) {
            try {
                $db->query($statement);
                $messages[] = htmlspecialchars($statement) . ' OK';
            } catch (Exception $e) {
                $errors[] = htmlspecialchars($statement) . ' HATA: ' . $e->getMessage();
            }
        }
        if (empty($errors)) {
            $messages[] = 'Tüm tablolar başarıyla güncellendi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kolasyon Düzeltici</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="h4 mb-4"><i class="fas fa-database me-2"></i>Kolasyon Düzeltici</h1>

    <?php foreach ($messages as $msg): ?>
        <div class="alert alert-success"><?php echo $msg; ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?php echo $err; ?></div>
    <?php endforeach; ?>

    <div class="card mb-4">
        <div class="card-header">Otomatik Çalıştır</div>
        <div class="card-body">
            <p>Aşağıdaki buton belirtilen tabloları <code>utf8mb4_unicode_ci</code> kolasyonuna dönüştürür.</p>
            <form method="post">
                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-play me-2"></i> Kolasyonları Düzelt
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Manuel SQL</div>
        <div class="card-body">
            <p>İsterseniz bu komutları phpMyAdmin veya CLI üzerinden de çalıştırabilirsiniz:</p>
            <textarea class="form-control" rows="10" readonly><?php echo implode("\n", $sqlStatements); ?></textarea>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>

