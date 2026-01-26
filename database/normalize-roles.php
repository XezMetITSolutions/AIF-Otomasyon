<?php
/**
 * Rol normalize etme aracı
 * Tüm kullanıcıları (süper admin haricinde) tek "üye" rolünde toplar.
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

$messages = [];
$errors = [];

/**
 * Rol bilgilerini getir ya da oluştur.
 */
function ensureRole(Database $db, string $roleName, string $label, int $level): array
{
    $role = $db->fetch("SELECT * FROM roller WHERE rol_adi = ?", [$roleName]);
    if ($role) {
        return $role;
    }
    $db->query("
        INSERT INTO roller (rol_adi, rol_label, rol_yetki_seviyesi)
        VALUES (?, ?, ?)
    ", [$roleName, $label, $level]);

    return $db->fetch("SELECT * FROM roller WHERE rol_adi = ?", [$roleName]);
}

$superRole = ensureRole($db, 'super_admin', 'Süper Admin', 100);
$memberRole = ensureRole($db, 'uye', 'Üye', 10);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Güvenlik doğrulaması başarısız oldu.';
    } else {
        try {
            $otherRoles = $db->fetchAll("
                SELECT * FROM roller
                WHERE rol_id NOT IN (?, ?)
            ", [$superRole['rol_id'], $memberRole['rol_id']]);

            $updatedUsers = 0;
            foreach ($otherRoles as $role) {
                $count = $db->query("
                    UPDATE kullanicilar
                    SET rol_id = ?
                    WHERE rol_id = ?
                ", [$memberRole['rol_id'], $role['rol_id']])->rowCount();
                $updatedUsers += $count;
                $messages[] = sprintf('%s rolündeki %d kullanıcı üye rolüne taşındı.', $role['rol_adi'], $count);
            }

            if ($updatedUsers === 0) {
                $messages[] = 'Taşınacak kullanıcı bulunamadı. Tüm kullanıcılar zaten üye rolünde olabilir.';
            } else {
                $messages[] = sprintf('Toplam %d kullanıcı üye rolüne geçirilmiştir.', $updatedUsers);
            }
        } catch (Exception $e) {
            $errors[] = 'İşlem sırasında hata oluştu: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Rol Normalize Aracı</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="h4 mb-4"><i class="fas fa-user-tag me-2"></i>Rol Normalize Aracı</h1>

    <?php foreach ($messages as $msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
    <?php endforeach; ?>

    <div class="card mb-4">
        <div class="card-header">Kullanıcıları Üye Rolüne Taşı</div>
        <div class="card-body">
            <p>Süper admin haricindeki tüm kullanıcılar tek tip "üye" rolünde toplanacaktır. Bu işlem:</p>
            <ul>
                <li>Gerekirse "üye" rolünü otomatik oluşturur.</li>
                <li>"super_admin" dışındaki tüm rollerin kullanıcılarını "üye" rolüne taşır.</li>
                <li>Modül yetkileri `baskan-yetkileri.php` ekranından yönetilecektir.</li>
            </ul>
            <form method="post">
                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-sync me-2"></i>Normalize Et
                </button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>

