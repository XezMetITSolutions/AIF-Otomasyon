<?php
/**
 * Görev Detay Sayfası
 * - Alt Adımlar (Checklist/Todo)
 * - Dosyalar
 * - Notlar
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$isSuperAdmin = $auth->isSuperAdmin();
$canManage = $auth->hasModulePermission('baskan_projeler');
$canView = $auth->hasModulePermission('uye_projeler');

if (!$canManage && !$canView) {
    Middleware::forbidden("Bu sayfayı görüntüleme yetkiniz yok.");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    $redirect = $canManage ? '/admin/projeler' : 'projelerim';
    header('Location: ' . $redirect);
    exit;
}

// ------------------------------------------------------------------------------------------------
// 0. TABLE SETUP
// ------------------------------------------------------------------------------------------------
$db->query("CREATE TABLE IF NOT EXISTS `gorev_checklist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `gorev_id` INT NOT NULL,
    `baslik` VARCHAR(255) NOT NULL,
    `tamamlandi` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`gorev_id`) REFERENCES `proje_gorevleri`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$db->query("CREATE TABLE IF NOT EXISTS `gorev_notlari` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `gorev_id` INT NOT NULL,
    `kullanici_id` INT NOT NULL,
    `not_icerik` TEXT,
    `tarih` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`gorev_id`) REFERENCES `proje_gorevleri`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$db->query("CREATE TABLE IF NOT EXISTS `gorev_dosyalari` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `gorev_id` INT NOT NULL,
    `yukleyen_id` INT NOT NULL,
    `dosya_adi` VARCHAR(255),
    `dosya_yolu` VARCHAR(255),
    `aciklama` TEXT,
    `tarih` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`gorev_id`) REFERENCES `proje_gorevleri`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// ------------------------------------------------------------------------------------------------
// PERMISSION HELPER FUNCTIONS
// ------------------------------------------------------------------------------------------------

/**
 * İçeriğin görünürlüğünü kontrol eder
 * @param string $gorulebilirlik - sadece_ben, ekip, herkes, ozel
 * @param int $olusturan_id - İçeriği oluşturan kullanıcı ID
 * @param int $ekip_id - Görevin ekip ID'si (varsa)
 * @param int $proje_id - Projenin ID'si
 * @param string $icerik_tipi - checklist, not, dosya
 * @param int $icerik_id - İçerik ID'si
 * @return bool
 */
function canViewContent($gorulebilirlik, $olusturan_id, $ekip_id, $proje_id, $icerik_tipi = null, $icerik_id = null) {
    global $user, $db, $isSuperAdmin;
    
    // Süper admin her şeyi görebilir
    if ($isSuperAdmin) return true;
    
    switch ($gorulebilirlik) {
        case 'sadece_ben':
            return $user['id'] == $olusturan_id;
            
        case 'ekip':
            // Oluşturan görebilir
            if ($user['id'] == $olusturan_id) return true;
            
            // Ekip üyesi mi kontrol et
            if ($ekip_id) {
                $isMember = $db->fetch(
                    "SELECT 1 FROM proje_ekip_uyeleri WHERE ekip_id = ? AND kullanici_id = ?",
                    [$ekip_id, $user['id']]
                );
                return (bool)$isMember;
            }
            return false;
            
        case 'herkes':
            // Projedeki herkes görebilir
            $isProjectMember = $db->fetch(
                "SELECT 1 FROM projeler p WHERE p.proje_id = ? AND (
                    p.sorumlu_id = ? OR 
                    EXISTS (
                        SELECT 1 FROM proje_ekipleri pe 
                        JOIN proje_ekip_uyeleri peu ON pe.id = peu.ekip_id 
                        WHERE pe.proje_id = p.proje_id AND peu.kullanici_id = ?
                    )
                )",
                [$proje_id, $user['id'], $user['id']]
            );
            return (bool)$isProjectMember;
            
        case 'ozel':
            // Oluşturan görebilir
            if ($user['id'] == $olusturan_id) return true;
            
            // Özel izin listesinde mi?
            if ($icerik_tipi && $icerik_id) {
                $hasPermission = $db->fetch(
                    "SELECT 1 FROM gorev_icerik_izinleri 
                     WHERE icerik_tipi = ? AND icerik_id = ? AND kullanici_id = ?",
                    [$icerik_tipi, $icerik_id, $user['id']]
                );
                return (bool)$hasPermission;
            }
            return false;
            
        default:
            return false;
    }
}

/**
 * Özel izinleri kaydet
 */
function saveCustomPermissions($icerik_tipi, $icerik_id, $kullanici_ids) {
    global $db;
    
    // Önce mevcut izinleri temizle
    $db->query(
        "DELETE FROM gorev_icerik_izinleri WHERE icerik_tipi = ? AND icerik_id = ?",
        [$icerik_tipi, $icerik_id]
    );
    
    // Yeni izinleri ekle
    if (!empty($kullanici_ids)) {
        foreach ($kullanici_ids as $uid) {
            $db->query(
                "INSERT INTO gorev_icerik_izinleri (icerik_tipi, icerik_id, kullanici_id) VALUES (?, ?, ?)",
                [$icerik_tipi, $icerik_id, (int)$uid]
            );
        }
    }
}

// ------------------------------------------------------------------------------------------------
// 1. ACTION HANDLERS
// ------------------------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD CHECKLIST ITEM
    if ($action === 'add_checklist') {
        $item = trim($_POST['item'] ?? '');
        $gorulebilirlik = $_POST['gorulebilirlik'] ?? 'ekip';
        $ozel_kullanicilar = $_POST['ozel_kullanicilar'] ?? [];
        
        if ($item) {
            $db->query(
                "INSERT INTO gorev_checklist (gorev_id, baslik, gorulebilirlik, olusturan_id) VALUES (?, ?, ?, ?)",
                [$id, $item, $gorulebilirlik, $user['id']]
            );
            
            // Özel izinler varsa kaydet
            if ($gorulebilirlik === 'ozel' && !empty($ozel_kullanicilar)) {
                $lastId = $db->fetch("SELECT LAST_INSERT_ID() as id")['id'];
                saveCustomPermissions('checklist', $lastId, $ozel_kullanicilar);
            }
        }
        header("Location: ?id=$id&tab=todo"); exit;
    }

    // TOGGLE CHECKLIST ITEM
    elseif ($action === 'toggle_checklist') {
        $itemId = (int)$_POST['item_id'];
        $status = (int)$_POST['status']; // 1 or 0
        $db->query("UPDATE gorev_checklist SET tamamlandi = ? WHERE id = ?", [$status, $itemId]);
        exit; // AJAX response usually
    }

    // ADD NOTE
    elseif ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        $gorulebilirlik = $_POST['note_gorulebilirlik'] ?? 'ekip';
        $ozel_kullanicilar = $_POST['note_ozel_kullanicilar'] ?? [];
        
        if ($note) {
            $db->query(
                "INSERT INTO gorev_notlari (gorev_id, kullanici_id, not_icerik, gorulebilirlik) VALUES (?, ?, ?, ?)",
                [$id, $user['id'], $note, $gorulebilirlik]
            );
            
            if ($gorulebilirlik === 'ozel' && !empty($ozel_kullanicilar)) {
                $lastId = $db->fetch("SELECT LAST_INSERT_ID() as id")['id'];
                saveCustomPermissions('not', $lastId, $ozel_kullanicilar);
            }
        }
        header("Location: ?id=$id&tab=notes"); exit;
    }

    // UPDATE STATUS
    elseif ($action === 'update_status') {
        $status = $_POST['status'] ?? 'beklemede';
        $db->query("UPDATE proje_gorevleri SET durum = ? WHERE id = ?", [$status, $id]);
        
        // Görev detayını çek
        $taskInfo = $db->fetch("SELECT atanan_kisi_id, baslik FROM proje_gorevleri WHERE id = ?", [$id]);

        if ($taskInfo && $taskInfo['atanan_kisi_id'] && $taskInfo['atanan_kisi_id'] != $user['id']) {
            Notification::add(
                $taskInfo['atanan_kisi_id'],
                'Görev Durumu: ' . ucfirst($status),
                "'{$taskInfo['baslik']}' görevinin durumu güncellendi.",
                'bilgi',
                "/panel/gorev-detay.php?id=$id"
            );
        }
        header("Location: ?id=$id"); exit;
    }

    // UPLOAD FILE
    elseif ($action === 'upload_file') {
        $filename = trim($_POST['file_name'] ?? 'Dosya');
        $desc = trim($_POST['file_desc'] ?? '');
        
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/gorevler/' . $id . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $originalName = $_FILES['file']['name'];
            $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
            // Benzersiz isim
            $storedName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', substr($filename, 0, 10)) . '.' . $fileExt;
            $uploadPath = $uploadDir . $storedName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
                chmod($uploadPath, 0644); 
                $webPath = '/uploads/gorevler/' . $id . '/' . $storedName;
                
                $gorulebilirlik = $_POST['file_gorulebilirlik'] ?? 'ekip';
                $ozel_kullanicilar = $_POST['file_ozel_kullanicilar'] ?? [];
                
                $db->query(
                    "INSERT INTO gorev_dosyalari (gorev_id, yukleyen_id, dosya_adi, dosya_yolu, aciklama, gorulebilirlik) VALUES (?, ?, ?, ?, ?, ?)", 
                    [$id, $user['id'], $filename, $webPath, $desc, $gorulebilirlik]
                );
                
                if ($gorulebilirlik === 'ozel' && !empty($ozel_kullanicilar)) {
                    $lastId = $db->fetch("SELECT LAST_INSERT_ID() as id")['id'];
                    saveCustomPermissions('dosya', $lastId, $ozel_kullanicilar);
                }
                
                header("Location: ?id=$id&tab=files&msg=uploaded"); exit;
            } else {
                 header("Location: ?id=$id&tab=files&error=upload_failed"); exit;
            }
        }
        header("Location: ?id=$id&tab=files&error=no_file"); exit;
    }
}

// ------------------------------------------------------------------------------------------------
// 2. FETCH DATA
// ------------------------------------------------------------------------------------------------
$task = $db->fetch("
    SELECT t.*, p.baslik as proje_adi, p.proje_id,
           CONCAT(u.ad, ' ', u.soyad) as atanan_ad,
           pe.baslik as ekip_adi
    FROM proje_gorevleri t
    JOIN projeler p ON t.proje_id = p.proje_id
    LEFT JOIN kullanicilar u ON t.atanan_kisi_id = u.kullanici_id
    LEFT JOIN proje_ekipleri pe ON t.ekip_id = pe.id
    WHERE t.id = ?
", [$id]);

if (!$task) die("Görev bulunamadı.");

// Proje ekip üyelerini ve sorumluyu çek (dropdown için)
$projectUsers = $db->fetchAll("
    SELECT DISTINCT u.kullanici_id, u.ad, u.soyad
    FROM kullanicilar u
    WHERE u.kullanici_id IN (
        SELECT sorumlu_id FROM projeler WHERE proje_id = ?
        UNION
        SELECT peu.kullanici_id 
        FROM proje_ekipleri pe 
        JOIN proje_ekip_uyeleri peu ON pe.id = peu.ekip_id 
        WHERE pe.proje_id = ?
    )
    ORDER BY u.ad, u.soyad
", [$task['proje_id'], $task['proje_id']]);

// Checklist - izinlere göre filtrele
$allChecklist = $db->fetchAll("
    SELECT c.*, CONCAT(u.ad, ' ', u.soyad) as olusturan_adi
    FROM gorev_checklist c
    LEFT JOIN kullanicilar u ON c.olusturan_id = u.kullanici_id
    WHERE c.gorev_id = ? 
    ORDER BY c.id ASC
", [$id]);

$checklist = [];
foreach ($allChecklist as $item) {
    $gorulebilirlik = $item['gorulebilirlik'] ?? 'ekip';
    $olusturan_id = $item['olusturan_id'] ?? null;
    
    if (canViewContent($gorulebilirlik, $olusturan_id, $task['ekip_id'], $task['proje_id'], 'checklist', $item['id'])) {
        $checklist[] = $item;
    }
}

// Notlar - izinlere göre filtrele
$allNotes = $db->fetchAll("
    SELECT n.*, CONCAT(u.ad, ' ', u.soyad) as yazar
    FROM gorev_notlari n
    JOIN kullanicilar u ON n.kullanici_id = u.kullanici_id
    WHERE n.gorev_id = ? 
    ORDER BY n.tarih DESC
", [$id]);

$notes = [];
foreach ($allNotes as $note) {
    $gorulebilirlik = $note['gorulebilirlik'] ?? 'ekip';
    $olusturan_id = $note['kullanici_id'];
    
    if (canViewContent($gorulebilirlik, $olusturan_id, $task['ekip_id'], $task['proje_id'], 'not', $note['id'])) {
        $notes[] = $note;
    }
}

// Dosyalar - izinlere göre filtrele
$allFiles = $db->fetchAll("
    SELECT f.*, CONCAT(u.ad, ' ', u.soyad) as yukleyen
    FROM gorev_dosyalari f
    JOIN kullanicilar u ON f.yukleyen_id = u.kullanici_id
    WHERE f.gorev_id = ? 
    ORDER BY f.tarih DESC
", [$id]);

$files = [];
foreach ($allFiles as $file) {
    $gorulebilirlik = $file['gorulebilirlik'] ?? 'ekip';
    $olusturan_id = $file['yukleyen_id'];
    
    if (canViewContent($gorulebilirlik, $olusturan_id, $task['ekip_id'], $task['proje_id'], 'dosya', $file['id'])) {
        $files[] = $file;
    }
}

$activeTab = $_GET['tab'] ?? 'todo';
$pageTitle = 'Görev: ' . $task['baslik'];

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?php echo $canManage ? '/admin/projeler' : 'projelerim'; ?>">Projeler</a></li>
                <li class="breadcrumb-item"><a href="proje-detay.php?id=<?php echo $task['proje_id']; ?>"><?php echo htmlspecialchars($task['proje_adi']); ?></a></li>
                <li class="breadcrumb-item active">Görev Detayı</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="h3 mb-1"><?php echo htmlspecialchars($task['baslik']); ?></h1>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($task['aciklama']); ?></p>
            </div>
            
            <form method="POST" class="d-flex align-items-center gap-2 no-ajax">
                <input type="hidden" name="action" value="update_status">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="beklemede" <?php echo $task['durum'] == 'beklemede' ? 'selected' : ''; ?>>Beklemede</option>
                    <option value="devam_ediyor" <?php echo $task['durum'] == 'devam_ediyor' ? 'selected' : ''; ?>>Devam Ediyor</option>
                    <option value="tamamlandi" <?php echo $task['durum'] == 'tamamlandi' ? 'selected' : ''; ?>>Tamamlandı</option>
                </select>
            </form>
        </div>

        <div class="row">
            <!-- Left Info -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light fw-bold">Bilgiler</div>
                    <div class="card-body">
                         <div class="mb-3">
                             <small class="text-muted d-block text-uppercase fw-bold">Atanan</small>
                             <?php if ($task['atanan_ad']): ?>
                                 <div><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($task['atanan_ad']); ?></div>
                             <?php endif; ?>
                             <?php if ($task['ekip_adi']): ?>
                                 <div><i class="fas fa-users me-2"></i><?php echo htmlspecialchars($task['ekip_adi']); ?></div>
                             <?php endif; ?>
                             <?php if (!$task['atanan_ad'] && !$task['ekip_adi']): ?>
                                 <span class="text-muted">-</span>
                             <?php endif; ?>
                         </div>
                         <div class="mb-3">
                             <small class="text-muted d-block text-uppercase fw-bold">Son Tarih</small>
                             <div><i class="fas fa-calendar me-2"></i><?php echo $task['son_tarih'] ? date('d.m.Y', strtotime($task['son_tarih'])) : '-'; ?></div>
                         </div>
                         <div class="mb-0">
                             <small class="text-muted d-block text-uppercase fw-bold">Oluşturulma</small>
                             <div><?php echo date('d.m.Y H:i', strtotime($task['olusturma_tarihi'])); ?></div>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Right Tabs -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom-0">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab == 'todo' ? 'active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=todo">
                                    <i class="fas fa-list-ul me-2"></i>Yapılacaklar (Checklist)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab == 'files' ? 'active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=files">
                                    <i class="fas fa-paperclip me-2"></i>Dosyalar
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab == 'notes' ? 'active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=notes">
                                    <i class="fas fa-sticky-note me-2"></i>Notlar
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        
                        <!-- TAB: TODO -->
                        <?php if ($activeTab == 'todo'): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Yapılacaklar Listesi</h6>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addChecklistModal">
                                    <i class="fas fa-plus me-1"></i>Yeni Madde
                                </button>
                            </div>
                            
                            <ul class="list-group list-group-flush" id="checklist-container">
                                <?php if(empty($checklist)): ?>
                                    <div class="text-muted text-center py-3">Henüz madde eklenmemiş.</div>
                                <?php else: ?>
                                    <?php foreach($checklist as $item): ?>
                                        <li class="list-group-item d-flex align-items-center gap-2">
                                            <input class="form-check-input flex-shrink-0" type="checkbox" 
                                                   onclick="toggleChecklist(<?php echo $item['id']; ?>, this)"
                                                   <?php echo $item['tamamlandi'] ? 'checked' : ''; ?>>
                                            <span class="<?php echo $item['tamamlandi'] ? 'text-decoration-line-through text-muted' : ''; ?>">
                                                <?php echo htmlspecialchars($item['baslik']); ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                            
                            <script>
                            function toggleChecklist(id, el) {
                                const status = el.checked ? 1 : 0;
                                const span = el.nextElementSibling;
                                
                                // UI update immediately
                                if(status) {
                                    span.classList.add('text-decoration-line-through', 'text-muted');
                                } else {
                                    span.classList.remove('text-decoration-line-through', 'text-muted');
                                }

                                fetch('gorev-detay.php?id=<?php echo $id; ?>', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    body: 'action=toggle_checklist&item_id=' + id + '&status=' + status
                                });
                            }
                            </script>
                        <?php endif; ?>

                        <!-- TAB: FILES -->
                        <?php if ($activeTab == 'files'): ?>
                             <div class="text-end mb-3">
                                 <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                     <i class="fas fa-upload me-1"></i> Dosya Yükle
                                 </button>
                             </div>
                             
                             <div class="list-group">
                                 <?php if(empty($files)): ?>
                                     <div class="text-center text-muted py-3">Dosya yok.</div>
                                 <?php else: ?>
                                     <?php foreach($files as $f): ?>
                                         <div class="list-group-item d-flex justify-content-between align-items-center">
                                             <div>
                                                 <a href="dosya-indir.php?path=<?php echo urlencode($f['dosya_yolu']); ?>" target="_blank" class="fw-bold text-decoration-none"><?php echo htmlspecialchars($f['dosya_adi']); ?></a>
                                                 <div class="small text-muted"><?php echo htmlspecialchars($f['aciklama']); ?></div>
                                                 <div class="small text-secondary" style="font-size:11px;">
                                                     <?php echo htmlspecialchars($f['yukleyen']); ?> - <?php echo date('d.m.Y H:i', strtotime($f['tarih'])); ?>
                                                 </div>
                                             </div>
                                             <a href="dosya-indir.php?path=<?php echo urlencode($f['dosya_yolu']); ?>&download=1" target="_blank" class="text-muted"><i class="fas fa-download"></i></a>
                                         </div>
                                     <?php endforeach; ?>
                                 <?php endif; ?>
                             </div>
                        <?php endif; ?>

                        <!-- TAB: NOTES -->
                        <?php if ($activeTab == 'notes'): ?>
                            <div class="mb-3">
                                <?php if(empty($notes)): ?>
                                    <div class="text-center text-muted py-3">Henüz not yok.</div>
                                <?php else: ?>
                                    <?php foreach($notes as $n): ?>
                                        <div class="card mb-2 bg-light border-0">
                                            <div class="card-body p-2">
                                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($n['not_icerik'])); ?></p>
                                                <div class="text-end text-muted small" style="font-size:11px;">
                                                    <?php echo htmlspecialchars($n['yazar']); ?> - <?php echo date('d.m H:i', strtotime($n['tarih'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST" class="card p-3 bg-light border no-ajax">
                                <input type="hidden" name="action" value="add_note">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Yeni Not</label>
                                    <textarea name="note" class="form-control" rows="3" placeholder="Not yaz..." required></textarea>
                                </div>
                                
                                <?php $prefix = 'note_'; include __DIR__ . '/../includes/gorulebilirlik-selector.php'; ?>
                                
                                <div class="text-end">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-paper-plane me-1"></i>Gönder
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

<!-- Modals -->

<!-- 1. Checklist Modal -->
<div class="modal fade" id="addChecklistModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content no-ajax">
            <input type="hidden" name="action" value="add_checklist">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Madde Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Madde</label>
                    <input type="text" name="item" class="form-control" required>
                </div>
                
                <?php $prefix = ''; include __DIR__ . '/../includes/gorulebilirlik-selector.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary">Ekle</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content no-ajax" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_file">
            <div class="modal-header">
                <h5 class="modal-title">Dosya Yükle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Dosya Adı</label>
                    <input type="text" name="file_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Dosya</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Açıklama</label>
                    <textarea name="file_desc" class="form-control"></textarea>
                </div>
                
                <?php $prefix = 'file_'; include __DIR__ . '/../includes/gorulebilirlik-selector.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

    </div><!-- /.content-wrapper -->
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
