<?php
/**
 * Proje Detay Sayfası
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

// 1. Proje Detaylarını Çek
$proje = $db->fetch("
    SELECT p.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as sorumlu_adi
    FROM projeler p
    INNER JOIN byk b ON p.byk_id = b.byk_id
    LEFT JOIN kullanicilar u ON p.sorumlu_id = u.kullanici_id
    WHERE p.proje_id = ?
", [$id]);

if (!$proje) {
    header('Location: projeler.php?error=notfound');
    exit;
}

$pageTitle = 'Proje: ' . htmlspecialchars($proje['baslik']);

// NOT EKLEME İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_note') {
    $note = trim($_POST['note'] ?? '');
    if (!empty($note)) {
        // Tablo yoksa oluştur
        $db->query("CREATE TABLE IF NOT EXISTS `proje_notlari` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `proje_id` INT NOT NULL,
            `kullanici_id` INT NOT NULL,
            `not_icerik` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            `tarih` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`proje_id`) REFERENCES `projeler`(`proje_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->query("INSERT INTO proje_notlari (proje_id, kullanici_id, not_icerik) VALUES (?, ?, ?)", 
            [$id, $user['id'], $note]);
        header("Location: proje-detay.php?id=$id&tab=notes");
        exit;
    }
}

// GÖREV EKLEME İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_task') {
    $task_title = trim($_POST['task_title'] ?? '');
    $task_desc = trim($_POST['task_desc'] ?? '');
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if (!empty($task_title)) {
        // Tablo kontrolü (Genelde projeler.php'de oluşur ama garanti olsun)
        $db->query("CREATE TABLE IF NOT EXISTS `proje_gorevleri` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `proje_id` INT NOT NULL,
            `baslik` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            `aciklama` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            `atanan_kisi_id` INT DEFAULT NULL,
            `son_tarih` DATE DEFAULT NULL,
            `durum` ENUM('beklemede', 'devam_ediyor', 'tamamlandi') DEFAULT 'beklemede',
            `olusturma_tarihi` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`proje_id`) REFERENCES `projeler`(`proje_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->query("INSERT INTO proje_gorevleri (proje_id, baslik, aciklama, atanan_kisi_id, son_tarih, durum) VALUES (?, ?, ?, ?, ?, 'beklemede')", 
            [$id, $task_title, $task_desc, $assigned_to, $due_date]);
        header("Location: proje-detay.php?id=$id&tab=tasks");
        exit;
    }
}

// DOSYA/RAPOR YÜKLEME İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
    // upload logic here... (Basitleştirilmiş)
    // Gerçek bir upload mekanizması için file handling gerekir.
    // Şimdilik sadece DB kaydı simüle ediyorum.
}

// Verileri Çek
// 1. Görevler
try {
    $tasks = $db->fetchAll("
        SELECT t.*, CONCAT(u.ad, ' ', u.soyad) as atanan_ad
        FROM proje_gorevleri t
        LEFT JOIN kullanicilar u ON t.atanan_kisi_id = u.kullanici_id
        WHERE t.proje_id = ?
        ORDER BY t.son_tarih ASC
    ", [$id]);
} catch (Exception $e) { $tasks = []; }

// 2. Notlar
try {
    $notes = $db->fetchAll("
        SELECT n.*, CONCAT(u.ad, ' ', u.soyad) as yazar_adi
        FROM proje_notlari n
        LEFT JOIN kullanicilar u ON n.kullanici_id = u.kullanici_id
        WHERE n.proje_id = ?
        ORDER BY n.tarih DESC
    ", [$id]);
} catch (Exception $e) { $notes = []; }

// 3. Dosyalar (Placeholder)
try {
    $files = $db->fetchAll("SELECT * FROM proje_dosyalari WHERE proje_id = ? ORDER BY yukleme_tarihi DESC", [$id]);
} catch (Exception $e) { $files = []; }

// Kullanıcılar (Dropdownlar için)
$usersList = $db->fetchAll("SELECT kullanici_id, ad, soyad FROM kullanicilar WHERE aktif = 1 ORDER BY ad ASC");

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        
        <!-- Breadcrumb / Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="/admin/projeler.php">Projeler</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Proje Detayı</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">
                    <i class="fas fa-clipboard-list me-2"></i><?php echo htmlspecialchars($proje['baslik']); ?>
                </h1>
            </div>
            <div>
                <a href="/admin/proje-duzenle.php?id=<?php echo $id; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-2"></i>Düzenle
                </a>
            </div>
        </div>

        <div class="row">
            <!-- SOl KOLON: Özet Bilgiler -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light fw-bold">Proje Bilgileri</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small text-uppercase fw-bold">Durum</label>
                            <div>
                                <?php 
                                $statusClass = match($proje['durum']) {
                                    'tamamlandi' => 'success',
                                    'aktif' => 'primary',
                                    'iptal' => 'danger',
                                    default => 'warning text-dark'
                                };
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?> fs-6">
                                    <?php echo ucfirst($proje['durum']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small text-uppercase fw-bold">Sorumlu Kişi</label>
                            <div class="d-flex align-items-center mt-1">
                                <div class="avatar-circle-sm bg-primary text-white me-2">
                                    <?php echo $proje['sorumlu_adi'] ? strtoupper(substr($proje['sorumlu_adi'], 0, 1)) : '-'; ?>
                                </div>
                                <span class="fw-medium"><?php echo htmlspecialchars($proje['sorumlu_adi'] ?? 'Atanmamış'); ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small text-uppercase fw-bold">Birim / BYK</label>
                            <div><?php echo htmlspecialchars($proje['byk_adi']); ?></div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="text-muted small text-uppercase fw-bold">Başlangıç</label>
                                <div><?php echo $proje['baslangic_tarihi'] ? date('d.m.Y', strtotime($proje['baslangic_tarihi'])) : '-'; ?></div>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="text-muted small text-uppercase fw-bold">Bitiş</label>
                                <div><?php echo $proje['bitis_tarihi'] ? date('d.m.Y', strtotime($proje['bitis_tarihi'])) : '-'; ?></div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small text-uppercase fw-bold">Açıklama</label>
                            <p class="small text-secondary mb-0 p-2 bg-light rounded mt-1">
                                <?php echo nl2br(htmlspecialchars($proje['aciklama'] ?? 'Açıklama yok.')); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Notlar Widget -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Hızlı Notlar</span>
                        <span class="badge bg-secondary"><?php echo count($notes); ?></span>
                    </div>
                    <div class="card-body bg-light p-2" style="max-height: 300px; overflow-y: auto;">
                        <?php if(empty($notes)): ?>
                            <div class="text-center text-muted small py-3">Henüz not eklenmemiş.</div>
                        <?php else: ?>
                            <?php foreach($notes as $note): ?>
                                <div class="card border-0 shadow-sm mb-2">
                                    <div class="card-body p-2">
                                        <div class="small text-dark"><?php echo nl2br(htmlspecialchars($note['not_icerik'])); ?></div>
                                        <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($note['yazar_adi']); ?></small>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo date('d.m H:i', strtotime($note['tarih'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white p-2">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_note">
                            <div class="input-group input-group-sm">
                                <input type="text" name="note" class="form-control" placeholder="Bir not yazın..." required>
                                <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- SAĞ KOLON: Tablar (Görevler, Raporlar) -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom-0">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tasks" role="tab">
                                    <i class="fas fa-tasks me-2"></i>Alt Görevler
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#files" role="tab">
                                    <i class="fas fa-file-alt me-2"></i>Dosyalar & Raporlar
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body tab-content">
                        
                        <!-- GÖREVLER TAB -->
                        <div class="tab-pane fade show active" id="tasks" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Proje Görevleri</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                                    <i class="fas fa-plus me-1"></i>Görev Ekle
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Görev</th>
                                            <th>Atanan</th>
                                            <th>Son Tarih</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($tasks)): ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">Kayıtlı görev bulunamadı.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($tasks as $task): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($task['baslik']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($task['aciklama']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if($task['atanan_ad']): ?>
                                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($task['atanan_ad']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        if ($task['son_tarih']) {
                                                            $date = new DateTime($task['son_tarih']);
                                                            $now = new DateTime();
                                                            $class = ($date < $now && $task['durum'] != 'tamamlandi') ? 'text-danger fw-bold' : '';
                                                            echo "<span class='$class'>" . $date->format('d.m.Y') . "</span>";
                                                        } else {
                                                            echo "-";
                                                        }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $taskStatus = match($task['durum']) {
                                                        'tamamlandi' => ['success', 'check-circle'],
                                                        'devam_ediyor' => ['info', 'spinner'],
                                                        default => ['warning', 'clock'] // beklemede
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $taskStatus[0]; ?>">
                                                        <i class="fas fa-<?php echo $taskStatus[1]; ?> me-1"></i>
                                                        <?php echo ucfirst(str_replace('_', ' ', $task['durum'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- DOSYALAR TAB -->
                        <div class="tab-pane fade" id="files" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Proje Dosyaları</h5>
                                <!-- Dosya yükleme butonu (Demo) -->
                                <button class="btn btn-sm btn-secondary" disabled title="Hazırlanıyor...">
                                    <i class="fas fa-upload me-1"></i>Dosya Yükle
                                </button>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Rapor ve dosya yükleme modülü yakında aktif edilecektir.
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Yeni Görev Modal -->
<div class="modal fade" id="newTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Görev Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_task">
                <div class="mb-3">
                    <label class="form-label">Görev Başlığı</label>
                    <input type="text" class="form-control" name="task_title" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama</label>
                    <textarea class="form-control" name="task_desc" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Atanan Kişi</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">Seçiniz</option>
                            <?php foreach($usersList as $usr): ?>
                                <option value="<?php echo $usr['kullanici_id']; ?>"><?php echo htmlspecialchars($usr['ad'].' '.$usr['soyad']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Son Tarih</label>
                        <input type="date" class="form-control" name="due_date">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
