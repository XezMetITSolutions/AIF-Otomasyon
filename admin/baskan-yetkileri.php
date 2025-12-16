<?php
/**
 * Ana Yönetici - Başkan Yetki Yönetimi
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
$message = null;
$messageType = 'success';

// Tablo yoksa oluştur
$db->query("
    CREATE TABLE IF NOT EXISTS `baskan_modul_yetkileri` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `kullanici_id` int(11) NOT NULL,
      `module_key` varchar(100) NOT NULL,
      `can_view` tinyint(1) NOT NULL DEFAULT '1',
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_module` (`kullanici_id`,`module_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$baskans = $db->fetchAll("
    SELECT k.kullanici_id, k.ad, k.soyad, COALESCE(bc.name, b.byk_adi, '-') AS byk_adi
    FROM kullanicilar k
    INNER JOIN roller r ON k.rol_id = r.rol_id
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    LEFT JOIN byk_categories bc ON b.byk_kodu = bc.code
    WHERE r.rol_adi != ?
    ORDER BY k.ad, k.soyad
", [Auth::ROLE_SUPER_ADMIN]);

$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($selectedId === 0 && !empty($baskans)) {
    $selectedId = (int)$baskans[0]['kullanici_id'];
}

$selectedBaskan = null;
foreach ($baskans as $b) {
    if ((int)$b['kullanici_id'] === $selectedId) {
        $selectedBaskan = $b;
        break;
    }
}

if (!$selectedBaskan && $selectedId !== 0) {
    header('Location: /admin/baskan-yetkileri.php');
    exit;
}

// Mevcut izinleri oku
$existingPermissions = [];
if ($selectedBaskan) {
    $rows = $db->fetchAll("
        SELECT module_key, can_view
        FROM baskan_modul_yetkileri
        WHERE kullanici_id = ?
    ", [$selectedId]);
    foreach ($rows as $row) {
        $existingPermissions[$row['module_key']] = (int)$row['can_view'];
    }
}

if ($selectedBaskan && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız oldu.';
        $messageType = 'danger';
    } else {
        $selectedModules = $_POST['modules'] ?? [];
        if (!is_array($selectedModules)) {
            $selectedModules = [];
        }
        try {
            foreach ($moduleDefinitions as $moduleKey => $moduleInfo) {
                $canView = in_array($moduleKey, $selectedModules, true) ? 1 : 0;
                $db->query("
                    INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE can_view = VALUES(can_view)
                ", [$selectedId, $moduleKey, $canView]);
            }
            $message = 'Yetkiler başarıyla güncellendi.';
            $existingPermissions = [];
            $rows = $db->fetchAll("
                SELECT module_key, can_view
                FROM baskan_modul_yetkileri
                WHERE kullanici_id = ?
            ", [$selectedId]);
            foreach ($rows as $row) {
                $existingPermissions[$row['module_key']] = (int)$row['can_view'];
            }
        } catch (Exception $e) {
            $message = 'Yetkiler güncellenirken bir hata oluştu: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Modülleri gruplara ayır
$groupedModules = [];
foreach ($moduleDefinitions as $key => $info) {
    $group = $info['group'] ?? 'Genel';
    if (!isset($groupedModules[$group])) {
        $groupedModules[$group] = [];
    }
    $groupedModules[$group][$key] = $info;
}

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h1 class="h3 mb-0">
                                <i class="fas fa-user-shield me-2"></i>Modül Yetkileri
            </h1>
            <a href="/admin/kullanicilar.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kullanıcı Listesine Dön
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-user-crown me-2 text-warning"></i>Başkanlar
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($baskans)): ?>
                            <div class="list-group-item text-muted">Sistemde atanan başkan bulunamadı.</div>
                        <?php else: ?>
                            <?php foreach ($baskans as $baskan): ?>
                                <a href="/admin/baskan-yetkileri.php?id=<?php echo $baskan['kullanici_id']; ?>"
                                   class="list-group-item list-group-item-action <?php echo $selectedId === (int)$baskan['kullanici_id'] ? 'active' : ''; ?>">
                                    <div class="fw-semibold"><?php echo htmlspecialchars($baskan['ad'] . ' ' . $baskan['soyad']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($baskan['byk_adi'] ?? '-'); ?></small>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <?php if ($selectedBaskan): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($selectedBaskan['ad'] . ' ' . $selectedBaskan['soyad']); ?></strong>
                                <div class="small text-muted"><?php echo htmlspecialchars($selectedBaskan['byk_adi'] ?? '-'); ?></div>
                            </div>
                            <span class="badge text-bg-primary">ID: <?php echo $selectedBaskan['kullanici_id']; ?></span>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                <?php foreach ($groupedModules as $groupName => $modules): ?>
                                    <div class="mb-4">
                                        <h5 class="mb-3 text-primary"><?php echo htmlspecialchars($groupName); ?></h5>
                                        <div class="row">
                                            <?php foreach ($modules as $moduleKey => $info): ?>
                                                <?php
                                                    $default = (bool)($info['default'] ?? true);
                                                    $checked = isset($existingPermissions[$moduleKey])
                                                        ? (bool)$existingPermissions[$moduleKey]
                                                        : $default;
                                                ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="module-<?php echo $moduleKey; ?>"
                                                            name="modules[]"
                                                            value="<?php echo $moduleKey; ?>"
                                                            <?php echo $checked ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="module-<?php echo $moduleKey; ?>">
                                                            <?php echo htmlspecialchars($info['label']); ?>
                                                            <?php if (($info['category'] ?? '') === 'uye'): ?>
                                                                <span class="badge bg-info ms-1">Üye Modülü</span>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Yetkileri Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Yetkilerini düzenlemek istediğiniz başkanı seçiniz.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>

