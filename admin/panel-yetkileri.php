<?php
/**
 * Admin - Panel Yetkilendirme (Matris Görünümü)
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

// Filter modules
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

$messages = [];
$errors = [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Güvenlik doğrulaması başarısız oldu.';
    } else {
        try {
            $db->getConnection()->beginTransaction();

            // 1. Prepare batch insert/update
            $insertValues = [];
            $params = [];
            
            // Iterate over ALL manageable users and ALL assignable modules
            // This ensures we explicitly save 0 for unchecked items, overriding defaults
            foreach ($uyeler as $uye) {
                $uId = $uye['kullanici_id'];
                
                foreach ($assignableModules as $moduleKey => $mInfo) {
                    // Check if this module was checked for this user
                    $isChecked = isset($_POST['perm'][$uId][$moduleKey]);
                    $canView = $isChecked ? 1 : 0;
                    
                    $insertValues[] = "(?, ?, ?)";
                    $params[] = $uId;
                    $params[] = $moduleKey;
                    $params[] = $canView;
                }
            }
            
            if (!empty($insertValues)) {
                // Chunking to be safe, though MySQL limit is high
                $chunks = array_chunk($insertValues, 500);
                $paramChunks = array_chunk($params, 500 * 3); // 3 params per row
                
                foreach ($chunks as $i => $chunk) {
                    $sql = "INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view) 
                            VALUES " . implode(', ', $chunk) . "
                            ON DUPLICATE KEY UPDATE can_view = VALUES(can_view)";
                    $db->query($sql, $paramChunks[$i]);
                }
            }

            $db->getConnection()->commit();
            $messages[] = 'Tüm yetkiler başarıyla güncellendi.';
            
        } catch (Exception $e) {
            $db->getConnection()->rollBack();
            $errors[] = 'Hata oluştu: ' . $e->getMessage();
        }
    }
}

// Fetch All Permissions
$allPermissions = [];
try {
    $rows = $db->fetchAll("SELECT kullanici_id, module_key, can_view FROM baskan_modul_yetkileri");
    foreach ($rows as $row) {
        $allPermissions[$row['kullanici_id']][$row['module_key']] = (bool)$row['can_view'];
    }
} catch (Exception $e) {
    // Ignore
}

$pageTitle = 'Panel Yetkilendirme';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="container-fluid mt-4">
    <style>
        .table-matrix th.rotate {
            height: 140px;
            white-space: nowrap;
            vertical-align: bottom;
            position: relative;
        }
        
        .table-matrix th.rotate > div {
            transform: translate(0px, 0px) rotate(-45deg);
            width: 30px;
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform-origin: bottom left;
        }
        
        .table-matrix th.rotate span {
            border-bottom: 1px solid #ccc;
            padding: 5px 10px;
        }

        .matrix-icon-header {
            cursor: pointer;
            transition: all 0.2s;
            padding: 10px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        .matrix-icon-header:hover {
            background-color: #f1f5f9;
        }
        .matrix-icon-header i {
            font-size: 1.5rem;
            color: #64748b;
        }
        .matrix-icon-header.active i {
            color: var(--bs-primary);
        }
        
        .user-row-header {
            cursor: pointer;
            transition: color 0.2s;
        }
        .user-row-header:hover {
            color: var(--bs-primary);
            text-decoration: underline;
        }

        .matrix-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .sticky-col {
            position: sticky;
            left: 0;
            background: white;
            z-index: 10;
            border-right: 2px solid #e2e8f0;
        }
        
        .sticky-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 20;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .sticky-corner {
            z-index: 30;
        }

        .table-responsive {
            max-height: calc(100vh - 200px);
        }
    </style>

    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-th me-2"></i>Panel Yetkilendirme</h1>
                <p class="text-muted mb-0">Tablo üzerinden hızlı yetkilendirme yapın.</p>
            </div>
            <div>
                <button type="submit" form="matrixForm" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                </button>
            </div>
        </div>

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

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white p-3">
                <div class="input-group" style="max-width: 400px;">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="tableSearch" placeholder="Kullanıcı ara..." onkeyup="filterTable()">
                </div>
            </div>
            <div class="card-body p-0">
                <form method="POST" id="matrixForm">
                    <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0 align-middle" id="permissionsTable">
                            <thead class="sticky-header">
                                <tr>
                                    <th class="sticky-col sticky-corner bg-white p-3" style="min-width: 250px;">
                                        <div class="fw-bold">Kullanıcılar</div>
                                        <div class="small text-muted fw-normal">Satır başlığına tıkla → Tümünü seç</div>
                                    </th>
                                    <?php foreach ($assignableModules as $key => $module): ?>
                                        <th class="text-center bg-white p-2" style="min-width: 100px;">
                                            <div class="matrix-icon-header" onclick="toggleColumn('<?php echo $key; ?>')" title="<?php echo htmlspecialchars($module['label']); ?>" data-bs-toggle="tooltip">
                                                <i class="<?php echo $module['icon'] ?? 'fas fa-cube'; ?>"></i>
                                                <div class="small fw-bold text-truncate" style="max-width: 90px;">
                                                    <?php echo htmlspecialchars($module['label']); ?>
                                                </div>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uyeler as $uye): ?>
                                    <?php 
                                        $userId = $uye['kullanici_id'];
                                        $fullName = $uye['ad'] . ' ' . $uye['soyad'];
                                        $gorev = $uye['gorev_adi'] ?? '-';
                                    ?>
                                    <tr class="user-row">
                                        <td class="sticky-col bg-white p-3">
                                            <div class="user-row-header" onclick="toggleRow(this)">
                                                <div class="fw-bold"><?php echo htmlspecialchars($fullName); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($gorev); ?></div>
                                            </div>
                                        </td>
                                        <?php foreach ($assignableModules as $key => $module): ?>
                                            <td class="text-center bg-light-subtle">
                                                <?php 
                                                    $default = (bool)($module['default'] ?? true);
                                                    $isChecked = isset($allPermissions[$userId][$key]) 
                                                        ? $allPermissions[$userId][$key] 
                                                        : $default;
                                                ?>
                                                <input type="checkbox" 
                                                       name="perm[<?php echo $userId; ?>][<?php echo $key; ?>]" 
                                                       class="matrix-checkbox col-check-<?php echo $key; ?>" 
                                                       value="1"
                                                       <?php echo $isChecked ? 'checked' : ''; ?>>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    function filterTable() {
        const query = document.getElementById('tableSearch').value.toLowerCase();
        const rows = document.querySelectorAll('#permissionsTable tbody tr');
        
        rows.forEach(row => {
            const text = row.querySelector('.sticky-col').textContent.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function toggleColumn(key) {
        const checkboxes = document.querySelectorAll(`.col-check-${key}`);
        // Check if all visible ones are checked
        let allChecked = true;
        let visibleCount = 0;
        
        checkboxes.forEach(cb => {
            if (cb.closest('tr').style.display !== 'none') {
                visibleCount++;
                if (!cb.checked) allChecked = false;
            }
        });
        
        if (visibleCount === 0) return;

        const newState = !allChecked;
        checkboxes.forEach(cb => {
            if (cb.closest('tr').style.display !== 'none') {
                cb.checked = newState;
            }
        });
    }

    function toggleRow(headerDiv) {
        const row = headerDiv.closest('tr');
        const checkboxes = row.querySelectorAll('input[type="checkbox"]');
        
        let allChecked = true;
        checkboxes.forEach(cb => {
            if (!cb.checked) allChecked = false;
        });
        
        const newState = !allChecked;
        checkboxes.forEach(cb => cb.checked = newState);
    }
</script>

