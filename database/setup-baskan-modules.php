<?php
/**
 * Manuel Kurulum - Başkan Modül Yetkileri
 * Bu sayfa, yeni yetki tablosunu oluşturup varsayılan kayıtları hazırlar.
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$db = Database::getInstance();
$moduleDefinitions = require __DIR__ . '/../config/baskan_modules.php';
$appConfig = require __DIR__ . '/../config/app.php';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$csrfToken = Middleware::generateCSRF();

$messages = [];
$errors = [];

$createTableSQL = <<<SQL
CREATE TABLE IF NOT EXISTS `baskan_modul_yetkileri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `module_key` varchar(100) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_module` (`kullanici_id`,`module_key`),
  CONSTRAINT `baskan_modul_yetkileri_fk_user` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Güvenlik doğrulaması başarısız oldu.';
    } else {
        try {
            // Tabloyu oluştur
            $db->query($createTableSQL);
            $messages[] = 'baskan_modul_yetkileri tablosu oluşturuldu / güncellendi.';

            // Mevcut başkanlar için varsayılan kayıtları hazırla
            $baskans = $db->fetchAll("
                SELECT k.kullanici_id
                FROM kullanicilar k
                INNER JOIN roller r ON k.rol_id = r.rol_id
                WHERE r.rol_adi = ?
            ", [Auth::ROLE_UYE]);

            foreach ($baskans as $baskan) {
                foreach ($moduleDefinitions as $moduleKey => $info) {
                    $default = (int)($info['default'] ?? 1);
                    $db->query("
                        INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE can_view = VALUES(can_view)
                    ", [$baskan['kullanici_id'], $moduleKey, $default]);
                }
            }

            $messages[] = 'Mevcut başkanlar için varsayılan yetkiler uygulandı (' . count($baskans) . ' kullanıcı).';
        } catch (Exception $e) {
            $errors[] = 'Kurulum sırasında hata oluştu: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Başkan Modül Yetkileri Kurulumu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="mb-4">
        <h1 class="h3">
            <i class="fas fa-database me-2"></i>Başkan Modül Yetkileri Kurulumu
        </h1>
        <p class="text-muted mb-0">Bu sayfa yalnızca ana yönetici tarafından kullanılmalıdır.</p>
    </div>

    <?php foreach ($messages as $msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
    <?php endforeach; ?>

    <div class="card mb-4">
        <div class="card-header">Otomatik Kurulum</div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                <p>Bu işlem:</p>
                <ul>
                    <li><code>baskan_modul_yetkileri</code> tablosunu oluşturur (varsa günceller).</li>
                    <li>Sistemdeki tüm başkanlar için varsayılan modül izinlerini kaydeder.</li>
                </ul>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-play me-2"></i>Kurulumu Çalıştır
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Manuel SQL Komutu</div>
        <div class="card-body">
            <p>Gerekirse aşağıdaki SQL komutunu manuel olarak veritabanınızda çalıştırabilirsiniz:</p>
            <textarea class="form-control" rows="10" readonly><?php echo $createTableSQL; ?></textarea>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>

