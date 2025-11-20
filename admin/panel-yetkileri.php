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
    SELECT k.kullanici_id,
           k.ad,
           k.soyad,
           COALESCE(bc.name, b.byk_adi, '-') AS byk_adi,
           ab.alt_birim_adi AS gorev_adi
    FROM kullanicilar k
    INNER JOIN roller r ON k.rol_id = r.rol_id
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    LEFT JOIN byk_categories bc ON b.byk_kodu = bc.code
    LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
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
        .panel-grid-card {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            transition: transform .15s ease, box-shadow .15s ease;
            cursor: pointer;
        }
        .panel-grid-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(15,23,42,.08);
        }
        .panel-grid-card.active {
            border-color: var(--bs-primary);
            box-shadow: 0 18px 40px rgba(59,130,246,.25);
        }
        .panel-grid-card .icon-bubble {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .user-pill input {
            position: absolute;
            opacity: 0;
        }
        .user-pill label {
            display: block;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 14px;
            cursor: pointer;
            transition: all .15s ease;
        }
        .user-pill input:checked + label {
            border-color: var(--bs-primary);
            background: rgba(59,130,246,.08);
            color: var(--bs-primary);
            box-shadow: 0 6px 16px rgba(59,130,246,.2);
        }
    </style>
    <div class="content-wrapper">
        <div class="row g-3 align-items-center mb-4">
            <div class="col-lg-6">
                <h1 class="h3 mb-1"><i class="fas fa-sliders me-2"></i>Panel Yetkilendirme</h1>
                <p class="text-muted mb-0">Görev bazlı yetki tanımla, kullanıcıları hızla bul.</p>
            </div>
            <div class="col-lg-6">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="panelSearch" placeholder="Panel adı veya açıklamada ara..." onkeyup="filterPanels()">
                    <button class="btn btn-outline-secondary" id="panelAssignedToggle" data-active="0" type="button" onclick="toggleAssigned()">Sadece Yetki Verilenler</button>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="row g-3" id="panelGrid">
                    <?php foreach ($moduleCards as $moduleKey => $moduleInfo): ?>
                        <div class="col-12 col-md-6" data-panel-name="<?php echo strtolower(htmlspecialchars($moduleInfo['label'] ?? $moduleKey)); ?>" data-panel-assigned="<?php echo isset($modulePermissions[$moduleKey]) && count($modulePermissions[$moduleKey]) ? '1' : '0'; ?>">
                            <a href="/admin/panel-yetkileri.php?module=<?php echo urlencode($moduleKey); ?>" class="text-decoration-none text-reset">
                                <div class="panel-grid-card p-3 h-100 <?php echo $selectedModuleKey === $moduleKey ? 'active' : ''; ?>">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-bubble bg-primary-subtle text-primary">
                                            <i class="<?php echo $moduleInfo['icon'] ?? 'fas fa-layer-group'; ?>"></i>
                                        </div>
                                        <div class="w-100">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($moduleInfo['label'] ?? $moduleKey); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($moduleInfo['description'] ?? ''); ?></small>
                                                </div>
                                                <span class="badge bg-primary-subtle text-primary"><?php echo isset($modulePermissions[$moduleKey]) ? count($modulePermissions[$moduleKey]) : 0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <?php foreach ($messages as $msg): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
                        <?php endforeach; ?>
                        <?php foreach ($errors as $err): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
                        <?php endforeach; ?>

                        <?php if (!$selectedModule): ?>
                            <p class="text-muted text-center mb-0">Soldan bir panel seçerek yetkilendirmeye başlayın.</p>
                        <?php else: ?>
                            <form method="post" id="panelForm">
                                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="module_key" value="<?php echo htmlspecialchars($selectedModuleKey); ?>">

                                <div class="mb-3">
                                    <div class="text-uppercase text-muted small fw-semibold">Seçilen Panel</div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($selectedModule['label'] ?? $selectedModuleKey); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($selectedModule['description'] ?? ''); ?></p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Yetkili Üyeler</label>
                                    <div class="border rounded p-3" id="selectedSummary">
                                        <?php if (empty($selectedUsers)): ?>
                                            <span class="text-muted">Henüz üye seçilmedi.</span>
                                        <?php else: ?>
                                            <?php foreach ($uyeler as $uye):
                                                if (!in_array($uye['kullanici_id'], $selectedUsers, true)) continue;
                                                ?>
                                                <span class="badge bg-primary-subtle text-primary me-1 mb-1">
                                                    <?php echo htmlspecialchars(($uye['gorev_adi'] ?? 'GÖREV YOK') . ' - ' . $uye['ad'] . ' ' . $uye['soyad']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mb-3 flex-wrap">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                                        <i class="fas fa-users me-1"></i>Üye Seç
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="toggleUserList(true)">
                                        <i class="fas fa-check-double me-1"></i>Tümünü Seç
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="toggleUserList(false)">
                                        <i class="fas fa-eraser me-1"></i>Temizle
                                    </button>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Kaydet
                                    </button>
                                </div>
                                <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-users me-2"></i>Üye Seç</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                    <input type="text" class="form-control" id="userSearch" placeholder="Görev veya isim ara..." onkeyup="filterUsers()">
                                                </div>
                                                <div class="row g-2" id="user-list">
                                                    <?php foreach ($uyeler as $uye): ?>
                                                        <?php
                                                            $fullName = $uye['ad'] . ' ' . $uye['soyad'];
                                                            $gorev = $uye['gorev_adi'] ?? 'Görev Yok';
                                                            $inputId = 'user-' . $selectedModuleKey . '-' . $uye['kullanici_id'];
                                                            $isSelected = in_array($uye['kullanici_id'], $selectedUsers, true);
                                                        ?>
                                                        <div class="col-12 user-pill" data-name="<?php echo strtolower(htmlspecialchars($fullName)); ?>" data-gorev="<?php echo strtolower(htmlspecialchars($gorev)); ?>">
                                                            <input type="checkbox" name="module_permissions[]" value="<?php echo $uye['kullanici_id']; ?>" id="<?php echo $inputId; ?>" <?php echo $isSelected ? 'checked' : ''; ?> onchange="updateSummary()">
                                                            <label for="<?php echo $inputId; ?>">
                                                                <div class="fw-semibold"><?php echo htmlspecialchars($gorev); ?></div>
                                                                <div><?php echo htmlspecialchars($fullName); ?></div>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
    function toggleUserList(selectAll) {
        const container = document.getElementById('user-list');
        if (!container) return;
        container.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = selectAll);
        updateSummary();
    }

    function updateSummary() {
        const summary = document.getElementById('selectedSummary');
        if (!summary) return;
        const selected = Array.from(document.querySelectorAll('#user-list input[type="checkbox"]:checked'));
        if (!selected.length) {
            summary.innerHTML = '<span class="text-muted">Henüz üye seçilmedi.</span>';
            return;
        }
        const fragments = selected.map(cb => {
            const label = cb.nextElementSibling;
            const duty = label.querySelector('.fw-semibold')?.textContent || '';
            const name = label.querySelector('div:nth-child(2)')?.textContent || '';
            return `<span class="badge bg-primary-subtle text-primary me-1 mb-1">${duty} - ${name}</span>`;
        });
        summary.innerHTML = fragments.join('');
    }

    function filterUsers() {
        const query = (document.getElementById('userSearch').value || '').toLowerCase();
        document.querySelectorAll('#user-list .user-pill').forEach(pill => {
            const matches = pill.dataset.name.includes(query) || pill.dataset.gorev.includes(query);
            pill.style.display = matches ? '' : 'none';
        });
    }

    function filterPanels() {
        const query = (document.getElementById('panelSearch').value || '').toLowerCase();
        const onlyAssigned = document.getElementById('panelAssignedToggle')?.dataset.active === '1';
        document.querySelectorAll('#panelGrid > div').forEach(card => {
            const nameMatch = card.dataset.panelName.includes(query);
            const assignedMatch = !onlyAssigned || card.dataset.panelAssigned === '1';
            card.style.display = nameMatch && assignedMatch ? '' : 'none';
        });
    }

    function toggleAssigned() {
        const toggleBtn = document.getElementById('panelAssignedToggle');
        const isActive = toggleBtn.dataset.active === '1';
        toggleBtn.dataset.active = isActive ? '0' : '1';
        toggleBtn.classList.toggle('btn-outline-secondary', isActive);
        toggleBtn.classList.toggle('btn-secondary', !isActive);
        filterPanels();
    }
    document.addEventListener('DOMContentLoaded', () => {
        updateSummary();
        filterPanels();
    });
</script>

