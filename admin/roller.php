<?php
/**
 * Ana Yönetici - Rol & Yetki Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$csrfToken = Middleware::generateCSRF();

$pageTitle = 'Rol & Yetki Yönetimi';
$moduleDefinitions = require __DIR__ . '/../config/baskan_modules.php';

// Roller
$roller = $db->fetchAll("
    SELECT r.*, COUNT(k.kullanici_id) as kullanici_sayisi
    FROM roller r
    LEFT JOIN kullanicilar k ON r.rol_id = k.rol_id AND k.aktif = 1
    GROUP BY r.rol_id
    ORDER BY r.rol_yetki_seviyesi DESC
");

// Üye listesi (süper admin hariç)
$uyeler = $db->fetchAll("
    SELECT k.kullanici_id, k.ad, k.soyad, COALESCE(bc.name, b.byk_adi, '-') AS byk_adi
    FROM kullanicilar k
    INNER JOIN roller r ON k.rol_id = r.rol_id
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    LEFT JOIN byk_categories bc ON b.byk_kodu = bc.code
    WHERE r.rol_adi != ?
    ORDER BY k.ad, k.soyad
", [Auth::ROLE_SUPER_ADMIN]);

// Mevcut modül yetkileri
$modulePermissions = [];
try {
    $rows = $db->fetchAll("
        SELECT module_key, kullanici_id
        FROM baskan_modul_yetkileri
        WHERE can_view = 1
    ");
    foreach ($rows as $row) {
        $modulePermissions[$row['module_key']][] = (int)$row['kullanici_id'];
    }
} catch (Exception $e) {
    $modulePermissions = [];
}

$moduleMessages = [];
$moduleErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['panel_permissions'])) {
    if (!Middleware::verifyCSRF()) {
        $moduleErrors[] = 'Güvenlik doğrulaması başarısız oldu.';
    } else {
        try {
            $submitted = $_POST['module_permissions'] ?? [];

            foreach ($moduleDefinitions as $moduleKey => $info) {
                $selectedUsers = isset($submitted[$moduleKey])
                    ? array_map('intval', (array)$submitted[$moduleKey])
                    : [];

                $db->query("DELETE FROM baskan_modul_yetkileri WHERE module_key = ?", [$moduleKey]);

                foreach (array_unique($selectedUsers) as $userId) {
                    $db->query("
                        INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view)
                        VALUES (?, ?, 1)
                    ", [$userId, $moduleKey]);
                }
            }

            $moduleMessages[] = 'Panel yetkileri başarıyla güncellendi.';

            // Yeniden yükle
            $modulePermissions = [];
            $rows = $db->fetchAll("
                SELECT module_key, kullanici_id
                FROM baskan_modul_yetkileri
                WHERE can_view = 1
            ");
            foreach ($rows as $row) {
                $modulePermissions[$row['module_key']][] = (int)$row['kullanici_id'];
            }
        } catch (Exception $e) {
            $moduleErrors[] = 'Panel yetkileri kaydedilirken hata oluştu: ' . $e->getMessage();
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

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-user-shield me-2"></i>Rol & Yetki Yönetimi
                </h1>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($roller); ?></strong> rol
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rol Adı</th>
                                    <th>Açıklama</th>
                                    <th>Yetki Seviyesi</th>
                                    <th>Kullanıcı Sayısı</th>
                                    <th>Oluşturma Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roller as $rol): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($rol['rol_adi']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($rol['rol_aciklama'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $rol['rol_yetki_seviyesi'] == 3 ? 'danger' : ($rol['rol_yetki_seviyesi'] == 2 ? 'warning' : 'info'); ?>">
                                                Seviye <?php echo $rol['rol_yetki_seviyesi']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $rol['kullanici_sayisi']; ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($rol['olusturma_tarihi'])); ?></td>
                                        <td>
                                            <a href="/admin/rol-yetkiler.php?id=<?php echo $rol['rol_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-key"></i> Yetkiler
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-th-large me-2"></i>Panel Yetkileri
                    </div>
                    <small class="text-muted">Her paneli kimlerin görebileceğini seçin</small>
                </div>
                <div class="card-body">
                    <?php foreach ($moduleMessages as $msg): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
                    <?php endforeach; ?>
                    <?php foreach ($moduleErrors as $err): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
                    <?php endforeach; ?>

                    <?php if (empty($uyeler)): ?>
                        <div class="alert alert-info">Henüz atanmış üye bulunmadığı için panel yetkisi tanımlayamazsınız.</div>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="panel_permissions" value="1">
                            <?php foreach ($groupedModules as $groupName => $modules): ?>
                                <div class="mb-4">
                                    <h5 class="mb-3 text-primary"><?php echo htmlspecialchars($groupName); ?></h5>
                                    <div class="row g-3">
                                        <?php foreach ($modules as $moduleKey => $info): ?>
                                            <?php $selected = $modulePermissions[$moduleKey] ?? []; ?>
                                            <div class="col-lg-6">
                                                <div class="border rounded p-3">
                                                    <label class="form-label fw-semibold">
                                                        <?php echo htmlspecialchars($info['label'] ?? $moduleKey); ?>
                                                        <?php if (($info['category'] ?? '') === 'uye'): ?>
                                                            <span class="badge bg-info ms-1">Üye Modülü</span>
                                                        <?php endif; ?>
                                                    </label>
                                                    <select class="form-select" name="module_permissions[<?php echo $moduleKey; ?>][]" multiple size="6">
                                                        <?php foreach ($uyeler as $uye): ?>
                                                            <?php $fullName = $uye['ad'] . ' ' . $uye['soyad']; ?>
                                                            <option value="<?php echo $uye['kullanici_id']; ?>" <?php echo in_array($uye['kullanici_id'], $selected, true) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($fullName); ?>
                                                                <?php if (!empty($uye['byk_adi']) && $uye['byk_adi'] !== '-'): ?>
                                                                    (<?php echo htmlspecialchars($uye['byk_adi']); ?>)
                                                                <?php endif; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="text-muted d-block mt-1">Ctrl/Cmd + tıklamayla birden fazla üye seçebilirsiniz.</small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Panel Yetkilerini Kaydet
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>

