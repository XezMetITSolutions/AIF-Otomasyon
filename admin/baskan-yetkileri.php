<?php
/**
 * Ana Yönetici - Üye Yetki Yönetimi
 * Geliştirilmiş, birleştirilmiş yetki görünümü
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

// BULK ACTION: Grant ALL 'uye' permissions to ALL users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update_uye'])) {
    if (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız (Bulk).';
        $messageType = 'danger';
    } else {
        try {
             // 1. Identify "Uye" Modules
             $uyeModules = [];
             foreach ($moduleDefinitions as $key => $def) {
                 if (isset($def['category']) && $def['category'] === 'uye') {
                     $uyeModules[] = $key;
                 }
             }

             if (!empty($uyeModules)) {
                 // 2. Get All Users (reuse $baskans list which is already filtered != super_admin)
                 foreach ($baskans as $bUser) {
                     $uId = $bUser['kullanici_id'];
                     foreach ($uyeModules as $modKey) {
                         $db->query("
                            INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view)
                            VALUES (?, ?, 1)
                            ON DUPLICATE KEY UPDATE can_view = 1
                         ", [$uId, $modKey]);
                     }
                 }
                 $message = 'Tüm üyelere varsayılan Üye yetkileri tanımlandı.';
                 $messageType = 'success';
             } else {
                 $message = 'Üye modülü tanımı bulunamadı.';
                 $messageType = 'warning';
             }
        } catch (Exception $e) {
            $message = 'Hata: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
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
                // Check if user has explicitly checked this module
                $canView = in_array($moduleKey, $selectedModules, true) ? 1 : 0;
                $db->query("
                    INSERT INTO baskan_modul_yetkileri (kullanici_id, module_key, can_view)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE can_view = VALUES(can_view)
                ", [$selectedId, $moduleKey, $canView]);
            }
            $message = 'Yetkiler güncellendi.';
            
            // Reload permissions
            $existingPermissions = [];
            $rows = $db->fetchAll("SELECT module_key, can_view FROM baskan_modul_yetkileri WHERE kullanici_id = ?", [$selectedId]);
            foreach ($rows as $row) $existingPermissions[$row['module_key']] = (int)$row['can_view'];
            
        } catch (Exception $e) {
            $message = 'Hata: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// YENİ GÖRÜNÜM HARİTASI
// Modülleri mantıksal gruplara göre eşleştiriyoruz
$consolidatedFeatures = [
    'Organizasyon' => [
        [
            'name' => 'Kontrol Paneli (Dashboard)',
            'desc' => 'Genel bakış ekranı',
            'manager_key' => 'baskan_dashboard',
            'member_key' => null, // Dashboard usually everyone has, or managed elsewhere
        ],
        [
            'name' => 'Üye Yönetimi',
            'desc' => 'Üye listeleme ve detay görüntüleme',
            'manager_key' => 'baskan_uyeler',
            'member_key' => null, 
        ],
        [
            'name' => 'Raporlar ve İstatistikler',
            'desc' => 'Birim raporları',
            'manager_key' => 'baskan_raporlar',
            'member_key' => null,
        ],
        [
            'name' => 'Proje Yönetimi',
            'desc' => 'Projeler, görev takibi ve ekip yönetimi',
            'manager_key' => 'baskan_projeler',
            'member_key' => 'uye_projeler',
            'member_desc' => 'Projelerim',
        ],
        [
            'name' => 'Şube Ziyaretleri',
            'desc' => 'Ziyaret planlama ve raporlama (AT Birimi)',
            'manager_key' => 'baskan_sube_ziyaretleri',
            'member_key' => null,
        ],
    ],
    'İletişim & Faaliyetler' => [
        [
            'name' => 'Toplantı Yönetimi',
            'desc' => 'Toplantı oluşturma ve yönetme',
            'manager_key' => 'baskan_toplantilar',
            'member_key' => 'uye_toplantilar',
            'member_desc' => 'Takvimi görüntüleme',
        ],
        [
            'name' => 'Etkinlik Yönetimi',
            'desc' => 'Etkinlik düzenleme',
            'manager_key' => 'baskan_etkinlikler',
            'member_key' => 'uye_etkinlikler',
            'member_desc' => 'Etkinliklere katılım',
        ],
        [
            'name' => 'Duyuru Sistemi',
            'desc' => 'Duyuru yayınlama',
            'manager_key' => 'baskan_duyurular',
            'member_key' => 'uye_duyurular',
            'member_desc' => 'Duyuruları görüntüleme',
        ],
    ],
    'Finans & Talepler' => [
        [
            'name' => 'İzin Sistemi',
            'desc' => 'Üye izinlerini onaylama',
            'manager_key' => 'baskan_izin_talepleri',
            'member_key' => 'uye_izin_talepleri',
            'member_desc' => 'İzin talebi oluşturma',
        ],
        [
            'name' => 'Harcama Yönetimi',
            'desc' => 'Masraf onaylama ve raporlama',
            'manager_key' => 'baskan_harcama_talepleri',
            'member_key' => 'uye_harcama_talepleri',
            'member_desc' => 'Masraf girişi',
        ],
        [
            'name' => 'İade İşlemleri',
            'desc' => 'İade formu yönetimi',
            'manager_key' => 'baskan_iade_formlari',
            'member_key' => 'uye_iade_formu',
            'member_desc' => 'İade formu doldurma',
        ],
    ],
    'Tesis & Demirbaş' => [
        [
            'name' => 'Demirbaş Talepleri',
            'desc' => 'Demirbaş talebi onaylama',
            'manager_key' => 'baskan_demirbas_talepleri',
            'member_key' => 'uye_demirbas_talep',
            'member_desc' => 'Demirbaş talep etme',
        ],
         [
            'name' => 'Demirbaş Envanter Yönetimi',
            'desc' => 'Envantere malzeme ekleme/silme',
            'manager_key' => 'baskan_demirbas_yonetimi',
            'member_key' => null,
        ],
        [
            'name' => 'Raggal Rezervasyon',
            'desc' => 'Tesis rezervasyonlarını yönetme',
            'manager_key' => 'baskan_raggal_talepleri',
            'member_key' => 'uye_raggal_talep',
            'member_desc' => 'Tesis rezervasyon yapma',
        ],
    ]
];

include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="fas fa-users-cog me-2"></i>Kullanıcı Yetki Yönetimi</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Toplu İşlemler -->
        <div class="row mb-4">
            <div class="col-12 text-end">
                <form method="POST" class="d-inline-block" onsubmit="return confirm('Tüm üyelere varsayılan üye yetkileri tanımlanacak. Bu işlem geri alınamaz. Devam etmek istiyor musunuz?');">
                    <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="bulk_update_uye" value="1">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-users-cog me-2"></i>Tüm Üyelere Üye Yetkilerini Tanımla (Toplu)
                    </button>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Sol: Kullanıcı Listesi -->
            <div class="col-lg-3 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-light fw-bold text-uppercase small">
                        Üyeler
                    </div>
                    <div class="p-2 border-bottom">
                         <input type="text" id="baskanSearch" class="form-control form-control-sm" placeholder="İsim veya birim ara...">
                    </div>
                    <div class="list-group list-group-flush overflow-auto" id="baskanList" style="max-height: calc(100vh - 300px);">
                         <?php foreach ($baskans as $baskan): ?>
                            <a href="?id=<?php echo $baskan['kullanici_id']; ?>" 
                               class="list-group-item list-group-item-action baskan-item <?php echo $selectedId === (int)$baskan['kullanici_id'] ? 'active' : ''; ?>">
                                <div class="fw-bold small baskan-name"><?php echo htmlspecialchars($baskan['ad'] . ' ' . $baskan['soyad']); ?></div>
                                <div class="small opacity-75 baskan-byk"><?php echo htmlspecialchars($baskan['byk_adi'] ?? '-'); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sağ: Yetki Matrisi -->
            <div class="col-lg-9">
                <?php if ($selectedBaskan): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-uppercase small">
                            <?php echo htmlspecialchars($selectedBaskan['ad'] . ' ' . $selectedBaskan['soyad']); ?> - Yetki Ayarları
                        </span>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($selectedBaskan['byk_adi']); ?></span>
                    </div>
                    
                    <div class="card-body p-0">
                        <form method="POST">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr class="text-secondary small text-uppercase">
                                            <th class="ps-4" style="width: 35%;">Modül Adı</th>
                                            <th class="text-center" style="width: 30%;">Yönetici/Onay Yetkisi<br><span class="text-muted fw-normal text-lowercase">(Eskiden Başkan)</span></th>
                                            <th class="text-center" style="width: 35%;">Kullanıcı Yetkisi<br><span class="text-muted fw-normal text-lowercase">(Üye)</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($consolidatedFeatures as $groupName => $features): ?>
                                            <tr class="table-light">
                                                <td colspan="3" class="fw-bold ps-4 text-primary py-2 small text-uppercase">
                                                    <?php echo $groupName; ?>
                                                </td>
                                            </tr>
                                            <?php foreach ($features as $f): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($f['name']); ?></div>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($f['desc']); ?></div>
                                                    </td>
                                                    
                                                    <!-- Yönetici Yetkisi -->
                                                    <td class="text-center border-start">
                                                        <?php if ($f['manager_key']): ?>
                                                            <?php 
                                                                $mInfo = $moduleDefinitions[$f['manager_key']] ?? [];
                                                                $isDefault = (bool)($mInfo['default'] ?? true);
                                                                $isChecked = isset($existingPermissions[$f['manager_key']]) ? (bool)$existingPermissions[$f['manager_key']] : $isDefault;
                                                            ?>
                                                            <div class="form-check form-switch d-inline-block">
                                                                <input class="form-check-input" type="checkbox" role="switch"
                                                                       name="modules[]" 
                                                                       value="<?php echo $f['manager_key']; ?>" 
                                                                       id="chk_<?php echo $f['manager_key']; ?>"
                                                                       <?php echo $isChecked ? 'checked' : ''; ?>>
                                                                <label class="form-check-label small" for="chk_<?php echo $f['manager_key']; ?>">
                                                                    <?php echo $isChecked ? '<span class="text-success fw-bold">Aktif</span>' : '<span class="text-muted">Pasif</span>'; ?>
                                                                </label>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted small">-</span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Üye Yetkisi -->
                                                    <td class="text-center border-start">
                                                        <?php if ($f['member_key']): ?>
                                                            <?php 
                                                                $mInfo = $moduleDefinitions[$f['member_key']] ?? [];
                                                                $isDefault = (bool)($mInfo['default'] ?? false);
                                                                $isChecked = isset($existingPermissions[$f['member_key']]) ? (bool)$existingPermissions[$f['member_key']] : $isDefault;
                                                            ?>
                                                            <div class="form-check form-switch d-inline-block">
                                                                <input class="form-check-input" type="checkbox" role="switch"
                                                                       name="modules[]" 
                                                                       value="<?php echo $f['member_key']; ?>" 
                                                                       id="chk_<?php echo $f['member_key']; ?>"
                                                                       <?php echo $isChecked ? 'checked' : ''; ?>>
                                                                <label class="form-check-label small" for="chk_<?php echo $f['member_key']; ?>">
                                                                    <?php echo $isChecked ? '<span class="text-success fw-bold">Yetkili</span>' : '<span class="text-muted">Kısıtlı</span>'; ?>
                                                                </label>
                                                            </div>
                                                            <?php if (isset($f['member_desc'])): ?>
                                                                <div class="small text-muted fst-italic mt-1" style="font-size: 0.75rem;">
                                                                    (<?php echo htmlspecialchars($f['member_desc']); ?>)
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted small fst-italic">Üye tarafı yok</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="card-footer bg-white py-3 text-end sticky-bottom">
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Arama Scripti
    // Arama Scripti (Event Delegation for AJAX support)
    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'baskanSearch') {
            const val = e.target.value.toLowerCase();
            const baskanItems = document.querySelectorAll('.baskan-item');
            baskanItems.forEach(item => {
                const txt = item.innerText.toLowerCase();
                item.style.display = txt.includes(val) ? '' : 'none';
            });
        }
    });

    // Checkbox label değişimi
    const switches = document.querySelectorAll('.form-check-input[role="switch"]');
    switches.forEach(sw => {
        sw.addEventListener('change', function() {
            const label = this.parentElement.querySelector('label');
            if(label) {
                if(this.checked) {
                    label.innerHTML = '<span class="text-success fw-bold">Aktif</span>';
                } else {
                    label.innerHTML = '<span class="text-muted">Pasif</span>';
                }
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
