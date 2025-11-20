<?php
/**
 * Admin - Panel Yetkilendirme
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
$moduleDefinitions = require __DIR__ . '/../config/baskan_modules.php';
$assignableModules = array_filter($moduleDefinitions, fn($info) => ($info['category'] ?? '') !== 'uye');

$superRole = $db->fetch("SELECT * FROM roller WHERE rol_adi = ?", [Auth::ROLE_SUPER_ADMIN]) ?: ['rol_id' => 0];

$uyeler = $db->fetchAll("
    SELECT k.kullanici_id, k.ad, k.soyad, COALESCE(bc.name, b.byk_adi, '-') AS byk_adi
    FROM kullanicilar k
    INNER JOIN roller r ON k.rol_id = r.rol_id
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    LEFT JOIN byk_categories bc ON b.byk_kodu = bc.code
    WHERE r.rol_adi != ?
    ORDER BY k.ad, k.soyad
", [Auth::ROLE_SUPER_ADMIN]);

$selectedModuleKey = $_GET['module'] ?? null;

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

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Güvenlik doğrulaması başarısız oldu.';
    } else {
        $moduleKey = $_POST['module_key'] ?? '';
        if (!isset($moduleDefinitions[$moduleKey])) {
            $errors[] = 'Geçersiz panel seçimi.';
        } else {
            try {
                $selectedUsers = array_map('intval', (array)($_POST['module_permissions'] ?? []));
                $db->query("DELETE FROM baskan_modul_yetkileri WHERE module_key = ?", [$moduleKey]);
                foreach (array_unique($selectedUsers) as $userId) {
                    $db->query("
                        INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view)
                        VALUES (?, ?, 1)
                    ", [$userId, $moduleKey]);
                }
                $messages[] = 'Panel yetkileri güncellendi.';
                $modulePermissions[$moduleKey] = $selectedUsers;
                $selectedModuleKey = $moduleKey;
            } catch (Exception $e) {
                $errors[] = 'Panel yetkileri kaydedilirken hata oluştu: ' . $e->getMessage();
            }
        }
    }
}

$moduleCards = $assignableModules;

$selectedModule = $selectedModuleKey && isset($assignableModules[$selectedModuleKey])
    ? $assignableModules[$selectedModuleKey]
    : null;
$selectedUsers = $selectedModuleKey && isset($modulePermissions[$selectedModuleKey])
    ? $modulePermissions[$selectedModuleKey]
    : [];

$pageTitle = 'Panel Yetkilendirme';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main class="container-fluid mt-4">
    <style>
        .panel-card-button {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            transition: transform .15s ease, box-shadow .15s ease;
            cursor: pointer;
        }
        .panel-card-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(15,23,42,.08);
        }
        .panel-card-button.active {
            border-color: var(--bs-primary);
            box-shadow: 0 12px 30px rgba(59,130,246,.25);
        }
        .icon-bubble {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .user-pill input {
            position: absolute;
            opacity: 0;
        }
        .user-pill label {
            display: block;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px 14px;
            cursor: pointer;
            font-weight: 500;
            transition: all .15s ease;
        }
        .user-pill input:checked + label {
            border-color: var(--bs-primary);
            background: rgba(59,130,246,.08);
            color: var(--bs-primary);
            box-shadow: 0 4px 12px rgba(59,130,246,.15);
        }
    </style>
    <div class="content-wrapper">
        <div class="d-flex flex-column flex-lg-row gap-4">
            <div class="flex-grow-1">
                <div class="mb-3">
                    <h1 class="h3 mb-2">
                        <i class="fas fa-sliders me-2"></i>Panel Yetkilendirme
                    </h1>
                    <p class="text-muted mb-0">Bir panel seçip hangi kullanıcıların görebileceğini belirleyin.</p>
                </div>
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                    <?php foreach ($moduleCards as $moduleKey => $moduleInfo): ?>
                        <div class="col">
                            <a href="/admin/panel-yetkileri.php?module=<?php echo urlencode($moduleKey); ?>"
                               class="text-decoration-none text-reset">
                                <div class="panel-card-button card h-100 border-0 shadow-sm <?php echo $selectedModuleKey === $moduleKey ? 'active' : ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="icon-bubble bg-primary-subtle text-primary">
                                                <i class="<?php echo $moduleInfo['icon'] ?? 'fas fa-puzzle-piece'; ?>"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h5 class="card-title h6 mb-1"><?php echo htmlspecialchars($moduleInfo['label'] ?? $moduleKey); ?></h5>
                                                    <?php if (($moduleInfo['category'] ?? '') === 'uye'): ?>
                                                        <span class="badge bg-success">Üye</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary-subtle text-primary">Başkan</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="card-text text-muted mb-2" style="min-height: 40px;">
                                                    <?php echo htmlspecialchars($moduleInfo['description'] ?? ''); ?>
                                                </p>
                                                <div class="d-flex align-items-center gap-2 text-muted small">
                                                    <i class="fas fa-user-check"></i>
                                                    <?php echo isset($modulePermissions[$moduleKey]) ? count($modulePermissions[$moduleKey]) : 0; ?> yetkili üye
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex-shrink-0" style="min-width:320px; max-width:420px;">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <?php foreach ($messages as $msg): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
                        <?php endforeach; ?>
                        <?php foreach ($errors as $err): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
                        <?php endforeach; ?>

                        <?php if (!$selectedModule): ?>
                            <p class="text-muted text-center mb-0">Sol taraftan bir panel seçerek yetkilendirme yapabilirsiniz.</p>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="module_key" value="<?php echo htmlspecialchars($selectedModuleKey); ?>">
                                <div class="mb-3">
                                    <div class="text-uppercase text-muted small fw-semibold">Seçilen Panel</div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($selectedModule['label'] ?? $selectedModuleKey); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($selectedModule['description'] ?? ''); ?></p>
                                </div>
                                <div class="d-flex gap-2 mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleUserList(true)">
                                        <i class="fas fa-check-double me-1"></i>Tümünü Seç
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleUserList(false)">
                                        <i class="fas fa-eraser me-1"></i>Temizle
                                    </button>
                                </div>
                                <div class="row g-2" id="user-list">
                                    <?php foreach ($uyeler as $uye): ?>
                                        <?php
                                            $fullName = $uye['ad'] . ' ' . $uye['soyad'];
                                            $inputId = 'user-' . $selectedModuleKey . '-' . $uye['kullanici_id'];
                                            $isSelected = in_array($uye['kullanici_id'], $selectedUsers, true);
                                        ?>
                                        <div class="col-12 col-sm-6 user-pill">
                                            <input type="checkbox" name="module_permissions[]" value="<?php echo $uye['kullanici_id']; ?>" id="<?php echo $inputId; ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                                            <label for="<?php echo $inputId; ?>">
                                                <?php echo htmlspecialchars($fullName); ?>
                                                <?php if (!empty($uye['byk_adi']) && $uye['byk_adi'] !== '-'): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($uye['byk_adi']); ?></small>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Kaydet
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
    function toggleUserList(selectAll) {
        const container = document.getElementById('user-list');
        if (!container) return;
        container.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = selectAll);
    }
</script>

