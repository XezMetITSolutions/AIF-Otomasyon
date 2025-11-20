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

$superRole = $db->fetch("SELECT * FROM roller WHERE rol_adi = ?", [Auth::ROLE_SUPER_ADMIN]) ?: ['rol_id' => 0];

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

// Modülleri kategori bazlı ayır
$memberModules = array_filter($moduleDefinitions, fn($info) => ($info['category'] ?? '') === 'uye');
$baskanModules = array_filter($moduleDefinitions, fn($info) => ($info['category'] ?? '') !== 'uye');

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <style>
        .stat-card .icon-bubble {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .panel-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            transition: box-shadow .2s ease;
        }
        .panel-card:hover {
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .panel-card select {
            min-height: 160px;
        }
        .panel-group-title {
            letter-spacing: .08em;
            font-size: .8rem;
            color: #94a3b8;
        }
    </style>
    <div class="content-wrapper">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-bubble bg-primary-subtle text-primary me-3">
                                    <i class="fas fa-users fa-lg"></i>
                                </div>
                                <div>
                                    <div class="text-muted text-uppercase small fw-semibold">Toplam Rol</div>
                                    <div class="fs-4 fw-bold"><?php echo count($roller); ?></div>
                                    <small class="text-muted">Sistem genelindeki rol sayısı</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Toplu İşlem</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-outline-primary" type="button" onclick="selectAllModules(true)">
                                <i class="fas fa-check-double me-1"></i>Tüm Panelleri Aç
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="selectAllModules(false)">
                                <i class="fas fa-minus-circle me-1"></i>Tüm Panelleri Kapat
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">Başkan panelleri tüm üyeler için toplu olarak açılıp kapatılabilir.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-bubble bg-success-subtle text-success me-3">
                                    <i class="fas fa-user-check fa-lg"></i>
                                </div>
                                <div>
                                    <div class="text-muted text-uppercase small fw-semibold">Aktif Üye</div>
                                    <div class="fs-4 fw-bold">
                                        <?php
                                        $activeMembers = $db->fetch("
                                            SELECT COUNT(*) as count
                                            FROM kullanicilar
                                            WHERE rol_id != ? AND aktif = 1
                                        ", [$superRole['rol_id'] ?? 0])['count'] ?? 0;
                                        echo $activeMembers;
                                        ?>
                                    </div>
                                    <small class="text-muted">Süper admin hariç</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="icon-bubble bg-warning-subtle text-warning me-3">
                                    <i class="fas fa-sliders fa-lg"></i>
                                </div>
                                <div>
                                    <div class="text-muted text-uppercase small fw-semibold">Panel Yönetimi</div>
                                    <div class="fs-4 fw-bold"><?php echo count($moduleDefinitions); ?></div>
                                    <small class="text-muted">Toplam modül anahtarı</small>
                                </div>
                            </div>
                        </div>
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

                            <div class="mb-5">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5 class="mb-0 text-success">Üye Panelleri</h5>
                                    <span class="badge bg-success-subtle text-success">Tüm üyeler görebilir</span>
                                </div>

                                <?php if (empty($memberModules)): ?>
                                    <div class="alert alert-light border">Üye modülü tanımlı değil.</div>
                                <?php else: ?>
                                    <div class="row g-3 row-cols-1 row-cols-lg-2">
                                        <?php foreach ($memberModules as $moduleKey => $info): ?>
                                            <div class="col">
                                                <div class="panel-card p-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($info['label'] ?? $moduleKey); ?></div>
                                                            <small class="text-muted">Bu panel tüm üyelere açıktır.</small>
                                                        </div>
                                                        <div class="badge bg-success text-white">Aktif</div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5 class="mb-0 text-primary">Başkan Panelleri</h5>
                                    <small class="text-muted">Görebilecek üyeleri seçin</small>
                                </div>
                                <div class="row g-3 row-cols-1 row-cols-lg-2">
                                    <?php foreach ($baskanModules as $moduleKey => $info): ?>
                                        <?php $selected = $modulePermissions[$moduleKey] ?? []; ?>
                                        <div class="col">
                                            <div class="panel-card p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($info['label'] ?? $moduleKey); ?></div>
                                                </div>
                                                <select class="form-select" id="module-select-<?php echo $moduleKey; ?>" name="module_permissions[<?php echo $moduleKey; ?>][]" multiple size="6">
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
                                                <div class="d-flex justify-content-between mt-2">
                                                    <small class="text-muted">Ctrl/Cmd + tıklamayla birden fazla üye seçebilirsiniz.</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
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
<script>
    function selectAll(moduleKey, selectAll) {
        const select = document.getElementById('module-select-' + moduleKey);
        if (!select) return;
        Array.from(select.options).forEach(option => option.selected = selectAll);
    }

    function selectAllModules(selectAllUsers) {
        const selects = document.querySelectorAll('[id^="module-select-"]');
        selects.forEach(select => {
            Array.from(select.options).forEach(option => option.selected = selectAllUsers);
        });
    }
</script>

