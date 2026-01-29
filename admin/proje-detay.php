<?php
/**
 * Proje Detay - Gelişmiş Yönetim
 * - Görevler, Ekipler, Dosyalar ve Notlar
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: projeler.php');
    exit;
}

// ------------------------------------------------------------------------------------------------
// 0. VERİTABANI HAZIRLIK (Eksik tabloları oluştur)
// ------------------------------------------------------------------------------------------------
$db->query("CREATE TABLE IF NOT EXISTS `proje_ekipleri` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proje_id` INT NOT NULL,
    `baslik` VARCHAR(100) NOT NULL,
    `aciklama` VARCHAR(255),
    `olusturma_tarihi` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`proje_id`) REFERENCES `projeler`(`proje_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$db->query("CREATE TABLE IF NOT EXISTS `proje_ekip_uyeleri` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ekip_id` INT NOT NULL,
    `kullanici_id` INT NOT NULL,
    FOREIGN KEY (`ekip_id`) REFERENCES `proje_ekipleri`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$db->query("CREATE TABLE IF NOT EXISTS `proje_gorevleri` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proje_id` INT NOT NULL,
    `baslik` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    `aciklama` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    `atanan_kisi_id` INT DEFAULT NULL,
    `ekip_id` INT DEFAULT NULL,
    `son_tarih` DATE DEFAULT NULL,
    `durum` ENUM('beklemede', 'devam_ediyor', 'tamamlandi') DEFAULT 'beklemede',
    `olusturma_tarihi` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`proje_id`) REFERENCES `projeler`(`proje_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->query("CREATE TABLE IF NOT EXISTS `proje_notlari` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proje_id` INT NOT NULL,
    `kullanici_id` INT NOT NULL,
    `not_icerik` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    `tarih` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`proje_id`) REFERENCES `projeler`(`proje_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Görevler tablosunu güncelle (ekip_id ekle)
try {
    $db->query("ALTER TABLE `proje_gorevleri` ADD COLUMN `ekip_id` INT DEFAULT NULL AFTER `proje_id`");
} catch (Exception $e) { /* Column exists */ }

$db->query("CREATE TABLE IF NOT EXISTS `proje_dosyalari` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proje_id` INT NOT NULL,
    `yukleyen_id` INT NOT NULL,
    `dosya_adi` VARCHAR(255) NOT NULL,
    `dosya_yolu` VARCHAR(255) NOT NULL,
    `aciklama` TEXT,
    `tarih` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`proje_id`) REFERENCES `projeler`(`proje_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");


// ------------------------------------------------------------------------------------------------
// 1. ACTION HANDLERS
// ------------------------------------------------------------------------------------------------
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- EKİP EKLEME ---
    if ($action === 'add_team') {
        $baslik = trim($_POST['team_name'] ?? '');
        $aciklama = trim($_POST['team_desc'] ?? '');
        if ($baslik) {
            $db->query("INSERT INTO proje_ekipleri (proje_id, baslik, aciklama) VALUES (?, ?, ?)", [$id, $baslik, $aciklama]);
            header("Location: ?id=$id&tab=teams&msg=team_added"); exit;
        }
    }

    // --- EKİBE ÜYE EKLEME ---
    elseif ($action === 'add_member_to_team') {
        $ekip_id = (int)$_POST['ekip_id'];
        $kullanici_id = (int)$_POST['kullanici_id'];
        if ($ekip_id && $kullanici_id) {
            // Zaten ekli mi?
            $exist = $db->fetch("SELECT id FROM proje_ekip_uyeleri WHERE ekip_id = ? AND kullanici_id = ?", [$ekip_id, $kullanici_id]);
            if (!$exist) {
                $db->query("INSERT INTO proje_ekip_uyeleri (ekip_id, kullanici_id) VALUES (?, ?)", [$ekip_id, $kullanici_id]);
                header("Location: ?id=$id&tab=teams&msg=member_added"); exit;
            }
        }
    }

    // --- GÖREV EKLEME ---
    elseif ($action === 'add_task') {
        $baslik = trim($_POST['task_title'] ?? '');
        $aciklama = trim($_POST['task_desc'] ?? '');
        $atanan_kisi = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
        $atanan_ekip = !empty($_POST['assigned_team']) ? $_POST['assigned_team'] : null;
        $son_tarih = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

        if ($baslik) {
            $db->query("INSERT INTO proje_gorevleri (proje_id, ekip_id, baslik, aciklama, atanan_kisi_id, son_tarih, durum) VALUES (?, ?, ?, ?, ?, ?, 'beklemede')", 
                [$id, $atanan_ekip, $baslik, $aciklama, $atanan_kisi, $son_tarih]);
            header("Location: ?id=$id&tab=tasks&msg=task_added"); exit;
        }
    }

    // --- NOT EKLEME ---
    elseif ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        if ($note) {
            $db->query("INSERT INTO proje_notlari (proje_id, kullanici_id, not_icerik) VALUES (?, ?, ?)", [$id, $user['id'], $note]);
            header("Location: ?id=$id&tab=notes&msg=note_added"); exit;
        }
    }

    // --- DOSYA YÜKLEME ---
    elseif ($action === 'upload_file') {
        $dosya_adi = trim($_POST['file_name'] ?? 'Dosya');
        $aciklama = trim($_POST['file_desc'] ?? '');

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/projeler/' . $id . '/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $originalName = $_FILES['file']['name'];
            $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
            // Benzersiz isim
            $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', substr($dosya_adi, 0, 10)) . '.' . $fileExt;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
                $webPath = '/uploads/projeler/' . $id . '/' . $fileName;
                
                $db->query("INSERT INTO proje_dosyalari (proje_id, yukleyen_id, dosya_adi, dosya_yolu, aciklama) VALUES (?, ?, ?, ?, ?)", 
                    [$id, $user['id'], $dosya_adi, $webPath, $aciklama]);
                
                header("Location: ?id=$id&tab=files&msg=file_uploaded"); exit;
            } else {
                header("Location: ?id=$id&tab=files&error=upload_failed"); exit;
            }
        } else {
             header("Location: ?id=$id&tab=files&error=no_file"); exit;
        }
    }
}

// ------------------------------------------------------------------------------------------------
// 2. VERİ ÇEKME
// ------------------------------------------------------------------------------------------------

// Proje Detay
$proje = $db->fetch("
    SELECT p.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as sorumlu_adi
    FROM projeler p
    INNER JOIN byk b ON p.byk_id = b.byk_id
    LEFT JOIN kullanicilar u ON p.sorumlu_id = u.kullanici_id
    WHERE p.proje_id = ?
", [$id]);

if (!$proje) die("Proje bulunamadı.");

// Ekipler
$teams = $db->fetchAll("SELECT * FROM proje_ekipleri WHERE proje_id = ?", [$id]);
// Her ekibin üyelerini çekelim
foreach ($teams as &$team) {
    $team['uyeler'] = $db->fetchAll("
        SELECT u.ad, u.soyad, u.kullanici_id
        FROM proje_ekip_uyeleri eu
        JOIN kullanicilar u ON eu.kullanici_id = u.kullanici_id
        WHERE eu.ekip_id = ?
    ", [$team['id']]);
}
unset($team);

// Görevler
$tasks = $db->fetchAll("
    SELECT t.*, 
           CONCAT(u.ad, ' ', u.soyad) as atanan_ad,
           pe.baslik as ekip_adi
    FROM proje_gorevleri t
    LEFT JOIN kullanicilar u ON t.atanan_kisi_id = u.kullanici_id
    LEFT JOIN proje_ekipleri pe ON t.ekip_id = pe.id
    WHERE t.proje_id = ?
    ORDER BY t.son_tarih ASC
", [$id]);

// Dosyalar (Proje Dosyaları + Görev Dosyaları)
$files = $db->fetchAll("
    SELECT 
        'proje' as tip,
        f.id, 
        f.dosya_adi, 
        f.dosya_yolu, 
        f.aciklama, 
        f.tarih,
        CONCAT(u.ad, ' ', u.soyad) as yukleyen,
        NULL as gorev_baslik,
        NULL as ekip_adi
    FROM proje_dosyalari f
    JOIN kullanicilar u ON f.yukleyen_id = u.kullanici_id
    WHERE f.proje_id = ?

    UNION ALL

    SELECT 
        'gorev' as tip,
        gd.id, 
        gd.dosya_adi, 
        gd.dosya_yolu, 
        gd.aciklama, 
        gd.tarih,
        CONCAT(u.ad, ' ', u.soyad) as yukleyen,
        g.baslik as gorev_baslik,
        pe.baslik as ekip_adi
    FROM gorev_dosyalari gd
    JOIN proje_gorevleri g ON gd.gorev_id = g.id
    JOIN kullanicilar u ON gd.yukleyen_id = u.kullanici_id
    LEFT JOIN proje_ekipleri pe ON g.ekip_id = pe.id
    WHERE g.proje_id = ?
    
    ORDER BY tarih DESC
", [$id, $id]);

// Notlar
$notes = $db->fetchAll("
    SELECT n.*, CONCAT(u.ad, ' ', u.soyad) as yazar_adi
    FROM proje_notlari n
    JOIN kullanicilar u ON n.kullanici_id = u.kullanici_id
    WHERE n.proje_id = ?
    ORDER BY n.tarih DESC
", [$id]);

// Genel Kullanıcı Listesi
$usersList = $db->fetchAll("SELECT kullanici_id, ad, soyad FROM kullanicilar WHERE aktif = 1 ORDER BY ad ASC");

$activeTab = $_GET['tab'] ?? 'tasks';

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="/admin/projeler.php">Projeler</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($proje['baslik']); ?></li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0"><i class="fas fa-tasks me-2"></i>Proje Yönetimi</h1>
            </div>
            <div>
                 <span class="badge bg-<?php echo ($proje['durum'] == 'tamamlandi' ? 'success' : 'primary'); ?> fs-6">
                    <?php echo ucfirst($proje['durum']); ?>
                </span>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar / Info Panel -->
            <div class="col-lg-3 col-md-12 mb-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                         <h6 class="text-uppercase text-muted fw-bold small">Proje Detayları</h6>
                         <h5 class="card-title mt-2"><?php echo htmlspecialchars($proje['baslik']); ?></h5>
                         <p class="card-text text-muted small mt-2">
                             <?php echo nl2br(htmlspecialchars($proje['aciklama'] ?? '')); ?>
                         </p>
                         <hr>
                         <div class="mb-2">
                             <small class="text-muted d-block">Birim / BYK</small>
                             <strong><?php echo htmlspecialchars($proje['byk_adi']); ?></strong>
                         </div>
                         <div class="mb-2">
                             <small class="text-muted d-block">Sorumlu</small>
                             <strong><?php echo htmlspecialchars($proje['sorumlu_adi'] ?? 'Belirtilmemiş'); ?></strong>
                         </div>
                         <div class="mb-2">
                             <small class="text-muted d-block">Tarih Aralığı</small>
                             <span>
                                 <?php echo $proje['baslangic_tarihi'] ? date('d.m.Y', strtotime($proje['baslangic_tarihi'])) : '?'; ?> - 
                                 <?php echo $proje['bitis_tarihi'] ? date('d.m.Y', strtotime($proje['bitis_tarihi'])) : '?'; ?>
                             </span>
                         </div>
                    </div>
                </div>

                <!-- Notlar Widget -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold mb-0">Hızlı Notlar</h6>
                    </div>
                    <div class="card-body p-3" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach($notes as $note): ?>
                            <div class="border rounded p-2 mb-2 bg-light">
                                <div class="small"><?php echo nl2br(htmlspecialchars($note['not_icerik'])); ?></div>
                                <div class="text-end text-muted mt-1" style="font-size: 10px;">
                                    <?php echo htmlspecialchars($note['yazar_adi']); ?> - <?php echo date('d.m H:i', strtotime($note['tarih'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="add_note">
                            <div class="input-group input-group-sm">
                                <input type="text" name="note" class="form-control" placeholder="Not ekle..." required>
                                <button class="btn btn-outline-primary" type="submit"><i class="fas fa-plus"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom-0">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab == 'tasks' ? 'active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=tasks">
                                    <i class="fas fa-check-square me-2"></i>Görevler
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab == 'teams' ? 'active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=teams">
                                    <i class="fas fa-users me-2"></i>Ekipler
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $activeTab == 'files' ? 'active' : ''; ?>" href="?id=<?php echo $id; ?>&tab=files">
                                    <i class="fas fa-folder-open me-2"></i>Dosyalar & Raporlar
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        
                        <!-- TAB: GÖREVLER -->
                        <?php if ($activeTab == 'tasks'): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Görev Listesi</h5>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                    <i class="fas fa-plus me-1"></i>Yeni Görev
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Görev</th>
                                            <th>Atanan (Kişi/Ekip)</th>
                                            <th>Son Tarih</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($tasks)): ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">Henüz görev eklenmemiş.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($tasks as $task): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">
                                                        <a href="gorev-detay.php?id=<?php echo $task['id']; ?>" class="text-decoration-none text-dark">
                                                            <?php echo htmlspecialchars($task['baslik']); ?>
                                                        </a>
                                                    </div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($task['aciklama']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($task['atanan_ad']): ?>
                                                        <span class="badge bg-light text-dark border"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($task['atanan_ad']); ?></span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($task['ekip_adi']): ?>
                                                        <span class="badge bg-info text-white"><i class="fas fa-users me-1"></i><?php echo htmlspecialchars($task['ekip_adi']); ?></span>
                                                    <?php endif; ?>

                                                    <?php if (!$task['atanan_ad'] && !$task['ekip_adi']): ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $task['son_tarih'] ? date('d.m.Y', strtotime($task['son_tarih'])) : '-'; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo ($task['durum']=='tamamlandi'?'success':'warning'); ?>">
                                                        <?php echo ucfirst($task['durum']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- TAB: EKİPLER -->
                        <?php if ($activeTab == 'teams'): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="card-title mb-0">Çalışma Ekipleri</h5>
                                    <p class="text-muted small mb-0">Fuar, organizasyon, stand vb. konular için alt ekipler oluşturun.</p>
                                </div>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                                    <i class="fas fa-plus me-1"></i>Yeni Ekip Kur
                                </button>
                            </div>

                            <div class="row g-3">
                                <?php if (empty($teams)): ?>
                                    <div class="col-12"><div class="alert alert-light text-center border">Henüz ekip oluşturulmamış.</div></div>
                                <?php else: ?>
                                    <?php foreach ($teams as $team): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100 border shadow-sm">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($team['baslik']); ?></h6>
                                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addMemberModal" onclick="setTeamId(<?php echo $team['id']; ?>)">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                            </div>
                                            <div class="card-body">
                                                <?php if ($team['aciklama'] && $team['aciklama'] !== 'Otomatik oluşturuldu'): ?>
                                                    <p class="small text-muted mb-2"><?php echo htmlspecialchars($team['aciklama']); ?></p>
                                                <?php endif; ?>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php if (empty($team['uyeler'])): ?>
                                                        <span class="small text-muted fs-italics">Henüz üye yok</span>
                                                    <?php else: ?>
                                                        <?php foreach ($team['uyeler'] as $uye): ?>
                                                            <span class="badge bg-secondary fw-normal">
                                                                <?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad']); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- TAB: DOSYALAR -->
                        <?php if ($activeTab == 'files'): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Proje Dosyaları</h5>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                                    <i class="fas fa-upload me-1"></i>Dosya Yükle
                                </button>
                            </div>
                            
                            <div class="list-group">
                                <?php if (empty($files)): ?>
                                    <div class="list-group-item text-center py-4 text-muted">Dosya yüklenmemiş.</div>
                                <?php else: ?>
                                    <?php foreach ($files as $file): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fas fa-file me-2 text-primary"></i>
                                                <a href="#" class="fw-bold text-decoration-none text-dark"><?php echo htmlspecialchars($file['dosya_adi']); ?></a>
                                                <?php if ($file['tip'] == 'gorev'): ?>
                                                    <span class="badge bg-light text-dark border ms-2" title="Bağlı Görev">
                                                        <i class="fas fa-tasks me-1 text-secondary"></i>
                                                        <?php echo htmlspecialchars($file['gorev_baslik']); ?>
                                                    </span>
                                                    <?php if ($file['ekip_adi']): ?>
                                                        <span class="badge bg-secondary ms-1" title="İlgili Ekip">
                                                            <?php echo htmlspecialchars($file['ekip_adi']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-primary ms-2">Proje Dosyası</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($file['aciklama']); ?></div>
                                            <div class="small text-muted mt-1">
                                                Yükleyen: <?php echo htmlspecialchars($file['yukleyen']); ?> | <?php echo date('d.m.Y H:i', strtotime($file['tarih'])); ?>
                                            </div>
                                        </div>
                                        <a href="#" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
<!-- MODALS -->

<!-- 1. Yeni Görev -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content no-ajax">
            <input type="hidden" name="action" value="add_task">
            <div class="modal-header">
                <h5 class="modal-title">Görev Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Başlık</label>
                    <input type="text" name="task_title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <textarea name="task_desc" class="form-control" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Atanan Ekip (Opsiyonel)</label>
                        <select name="assigned_team" class="form-select">
                            <option value="">Seçiniz</option>
                            <?php foreach($teams as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['baslik']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Atanan Kişi (Opsiyonel)</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">Seçiniz</option>
                            <?php foreach($usersList as $u): ?>
                                <option value="<?php echo $u['kullanici_id']; ?>"><?php echo htmlspecialchars($u['ad'].' '.$u['soyad']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Son Tarih</label>
                    <input type="date" name="due_date" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Yeni Ekip -->
<div class="modal fade" id="addTeamModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content no-ajax">
            <input type="hidden" name="action" value="add_team">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Ekip Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Ekip Adı (Örn: Stand Ekibi)</label>
                    <input type="text" name="team_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <textarea name="team_desc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary">Oluştur</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Ekibe Üye Ekle -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="add_member_to_team">
            <input type="hidden" name="ekip_id" id="modalEkipId">
            <div class="modal-header">
                <h5 class="modal-title">Ekibe Üye Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kullanıcı Seç</label>
                    <select name="kullanici_id" class="form-select" required>
                        <option value="">Seçiniz</option>
                        <?php foreach($usersList as $u): ?>
                            <option value="<?php echo $u['kullanici_id']; ?>"><?php echo htmlspecialchars($u['ad'].' '.$u['soyad']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary">Ekle</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Dosya Yükle -->
<div class="modal fade" id="uploadFileModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content no-ajax" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_file">
            <div class="modal-header">
                <h5 class="modal-title">Dosya / Rapor Yükle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Dosya Adı</label>
                    <input type="text" name="file_name" class="form-control" placeholder="Örn: Fuar Raporu.pdf" required>
                </div>
                <div class="mb-3">
                     <label class="form-label">Dosya Seç</label>
                     <input type="file" class="form-control" name="file" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <textarea name="file_desc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
window.setTeamId = function(id) {
    var el = document.getElementById('modalEkipId');
    if (el) el.value = id;
}

// Team Member Data
var teamMembers = <?php 
    $tmData = [];
    foreach($teams as $t) {
        $mems = [];
        if (!empty($t['uyeler'])) {
            foreach($t['uyeler'] as $u) {
                $mems[] = ['id' => $u['kullanici_id'], 'name' => $u['ad'] . ' ' . $u['soyad']];
            }
        }
        $tmData[$t['id']] = $mems;
    }
    echo json_encode($tmData);
?>;

var allUsers = <?php 
    $allU = [];
    foreach($usersList as $u) {
        $allU[] = ['id' => $u['kullanici_id'], 'name' => $u['ad'] . ' ' . $u['soyad']];
    }
    echo json_encode($allU);
?>;

document.addEventListener('DOMContentLoaded', function() {
    var teamSelect = document.querySelector('select[name="assigned_team"]');
    var userSelect = document.querySelector('select[name="assigned_to"]');
    
    if (teamSelect && userSelect) {
        teamSelect.addEventListener('change', function() {
            var teamId = this.value;
            
            // Clear current options
            userSelect.innerHTML = '<option value="">Seçiniz</option>';
            
            var usersToShow = [];
            
            if (teamId && teamMembers[teamId] && teamMembers[teamId].length > 0) {
                // Show only team members
                usersToShow = teamMembers[teamId];
            } else if (teamId && (!teamMembers[teamId] || teamMembers[teamId].length === 0)) {
                // Team selected but has no members -> Show empty or maybe all? 
                // Let's show all for flexibility, or maybe show none? 
                // Usually if I select a team, I want to assign to someone in it. 
                // If empty, maybe show all (fallback) or show none.
                // Let's stick to showing ONLY members if team is selected.
                usersToShow = []; 
            } else {
                // No team selected -> Show All
                usersToShow = allUsers;
            }
            
            // Populate options
            usersToShow.forEach(function(u) {
                var option = document.createElement('option');
                option.value = u.id;
                option.textContent = u.name;
                userSelect.appendChild(option);
            });
        });
    }
});
</script>

    </div><!-- /.content-wrapper -->
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
