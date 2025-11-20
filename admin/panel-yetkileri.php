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
// Filter out 'uye' category if needed, or keep all. The previous code filtered them.
// $assignableModules = array_filter($moduleDefinitions, fn($info) => ($info['category'] ?? '') !== 'uye');
// Assuming we want to assign all available modules that are not strictly for 'uye' or maybe all of them.
// Let's stick to the previous logic of filtering if that was important, but usually admins assign admin panels.
$assignableModules = array_filter($moduleDefinitions, fn($info) => ($info['category'] ?? '') !== 'uye');

// Fetch Users (excluding Super Admin)
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

$selectedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// If no user selected but we have users, maybe select the first one or stay empty?
// Let's stay empty to force explicit selection, or select first for convenience.
// The user said "select from there", implying a selection action.
// Let's default to 0 (no selection) to avoid accidental edits.

$selectedUser = null;
if ($selectedUserId > 0) {
    foreach ($uyeler as $uye) {
        if ((int)$uye['kullanici_id'] === $selectedUserId) {
            $selectedUser = $uye;
            break;
        }
    }
}

$userPermissions = [];
$messages = [];
$errors = [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedUser) {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Güvenlik doğrulaması başarısız oldu.';
    } else {
        try {
            $postedPermissions = $_POST['permissions'] ?? [];
            
            // Delete existing permissions for this user
            $db->query("DELETE FROM baskan_modul_yetkileri WHERE kullanici_id = ?", [$selectedUserId]);
            
            // Insert new permissions
            if (!empty($postedPermissions)) {
                $insertValues = [];
                $params = [];
                foreach ($postedPermissions as $moduleKey) {
                    if (isset($assignableModules[$moduleKey])) {
                        $insertValues[] = "(?, ?, 1)";
                        $params[] = $selectedUserId;
                        $params[] = $moduleKey;
                    }
                }
                
                if (!empty($insertValues)) {
                    $sql = "INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view) VALUES " . implode(', ', $insertValues);
                    $db->query($sql, $params);
                }
            }
            
            $messages[] = 'Yetkiler başarıyla güncellendi.';
            
        } catch (Exception $e) {
            $errors[] = 'Hata oluştu: ' . $e->getMessage();
        }
    }
}

// Fetch Current Permissions for Selected User
if ($selectedUser) {
    try {
        $rows = $db->fetchAll("
            SELECT module_key
            FROM baskan_modul_yetkileri
            WHERE kullanici_id = ? AND can_view = 1
        ", [$selectedUserId]);
        $userPermissions = array_column($rows, 'module_key');
    } catch (Exception $e) {
        $userPermissions = [];
    }
}

$pageTitle = 'Panel Yetkilendirme';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="container-fluid mt-4">
    <style>
        .user-list-item {
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }
        .user-list-item:hover {
            background-color: #f8f9fa;
        }
        .user-list-item.active {
            background-color: #eef2ff;
            border-left-color: var(--bs-primary);
            color: var(--bs-primary);
        }
        
        .panel-checkbox-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            position: relative;
            background: #fff;
        }
        
        .panel-checkbox-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border-color: #cbd5e1;
        }
        
        .panel-checkbox-card.checked {
            border-color: var(--bs-primary);
            background-color: #eff6ff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .panel-icon {
            font-size: 2rem;
            color: #64748b;
            transition: color 0.2s;
        }
        
        .panel-checkbox-card.checked .panel-icon {
            color: var(--bs-primary);
        }
        
        .panel-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #334155;
        }
        
        .panel-checkbox-card.checked .panel-name {
            color: var(--bs-primary);
        }
        
        /* Hide actual checkbox */
        .custom-checkbox-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .custom-checkmark {
            width: 24px;
            height: 24px;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            background: #fff;
        }
        
        .panel-checkbox-card.checked .custom-checkmark {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        
        .custom-checkmark i {
            color: white;
            font-size: 14px;
            display: none;
        }
        
        .panel-checkbox-card.checked .custom-checkmark i {
            display: block;
        }

        .search-box {
            position: sticky;
            top: 0;
            z-index: 10;
            background: white;
            padding-bottom: 1rem;
        }
        
        .user-list-container {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
    </style>

    <div class="content-wrapper">
        <div class="row g-4">
            <!-- Left Column: User List -->
            <div class="col-lg-4 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-0">
                        <div class="p-3 border-bottom bg-light rounded-top">
                            <h5 class="mb-3"><i class="fas fa-users me-2"></i>Kullanıcılar</h5>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="userSearch" placeholder="İsim ara..." onkeyup="filterUsers()">
                            </div>
                        </div>
                        <div class="user-list-container p-2" id="userList">
                            <?php foreach ($uyeler as $uye): ?>
                                <?php 
                                    $isActive = $selectedUserId === (int)$uye['kullanici_id'];
                                    $fullName = $uye['ad'] . ' ' . $uye['soyad'];
                                ?>
                                <a href="?user_id=<?php echo $uye['kullanici_id']; ?>" class="d-block text-decoration-none text-reset mb-1">
                                    <div class="user-list-item p-3 rounded <?php echo $isActive ? 'active' : ''; ?>" data-name="<?php echo strtolower($fullName); ?>">
                                        <div class="fw-bold"><?php echo htmlspecialchars($fullName); ?></div>
                                        <div class="small text-muted d-flex justify-content-between">
                                            <span><?php echo htmlspecialchars($uye['gorev_adi'] ?? '-'); ?></span>
                                            <i class="fas fa-chevron-right opacity-50"></i>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($uyeler)): ?>
                                <div class="text-center p-4 text-muted">Kullanıcı bulunamadı.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Permissions -->
            <div class="col-lg-8 col-xl-9">
                <?php if ($selectedUser): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">
                                    <span class="text-primary"><?php echo htmlspecialchars($selectedUser['ad'] . ' ' . $selectedUser['soyad']); ?></span>
                                    <span class="text-muted fw-normal">için Yetkiler</span>
                                </h5>
                                <p class="text-muted mb-0 small">Aşağıdan bu kullanıcının görebileceği panelleri seçiniz.</p>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="toggleAll(true)">Tümünü Seç</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAll(false)">Temizle</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php foreach ($messages as $msg): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($msg); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($errors as $err): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($err); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endforeach; ?>

                            <form method="POST" id="permissionsForm">
                                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                
                                <div class="row g-3">
                                    <?php foreach ($assignableModules as $key => $module): ?>
                                        <?php 
                                            $isChecked = in_array($key, $userPermissions);
                                        ?>
                                        <div class="col-6 col-md-4 col-xl-3">
                                            <label class="panel-checkbox-card <?php echo $isChecked ? 'checked' : ''; ?>" onclick="toggleCard(this)">
                                                <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>" class="custom-checkbox-input" <?php echo $isChecked ? 'checked' : ''; ?>>
                                                
                                                <div class="panel-icon">
                                                    <i class="<?php echo $module['icon'] ?? 'fas fa-cube'; ?>"></i>
                                                </div>
                                                
                                                <div class="panel-name">
                                                    <?php echo htmlspecialchars($module['label'] ?? $key); ?>
                                                </div>
                                                
                                                <div class="custom-checkmark">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fas fa-save me-2"></i>Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center text-muted p-5 border rounded-3 bg-light" style="min-height: 400px;">
                        <div class="mb-3">
                            <i class="fas fa-user-edit fa-4x opacity-25"></i>
                        </div>
                        <h4>Kullanıcı Seçiniz</h4>
                        <p>Yetkilerini düzenlemek için sol taraftaki listeden bir kullanıcı seçin.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    function filterUsers() {
        const query = document.getElementById('userSearch').value.toLowerCase();
        const items = document.querySelectorAll('.user-list-item');
        
        items.forEach(item => {
            const name = item.getAttribute('data-name');
            const parentLink = item.closest('a');
            if (name.includes(query)) {
                parentLink.style.display = '';
            } else {
                parentLink.style.display = 'none';
            }
        });
    }

    function toggleCard(label) {
        // The click event propagates to the input, so we don't need to manually check it if we clicked the label.
        // However, we need to update the visual class 'checked'.
        // We use setTimeout to let the checkbox state update first.
        setTimeout(() => {
            const checkbox = label.querySelector('input[type="checkbox"]');
            if (checkbox.checked) {
                label.classList.add('checked');
            } else {
                label.classList.remove('checked');
            }
        }, 10);
    }

    function toggleAll(state) {
        const checkboxes = document.querySelectorAll('.custom-checkbox-input');
        checkboxes.forEach(cb => {
            cb.checked = state;
            const label = cb.closest('.panel-checkbox-card');
            if (state) {
                label.classList.add('checked');
            } else {
                label.classList.remove('checked');
            }
        });
    }
</script>

