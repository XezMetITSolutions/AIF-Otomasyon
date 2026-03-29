<?php
/**
 * İstişareler Listesi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireRole(['super_admin', 'uye']);

$db = Database::getInstance();
$auth = new Auth();
$user = $auth->getUser();

$message = '';
$error = '';

// Şubeleri Getir
$subelerList = $db->fetchAll("SELECT * FROM subeler ORDER BY sube_adi ASC");

// AT Üyelerini Getir (İstişare Kurulu için)
$atUyeleri = $db->fetchAll("
    SELECT k.kullanici_id, k.ad, k.soyad 
    FROM kullanicilar k 
    INNER JOIN byk b ON k.byk_id = b.byk_id 
    WHERE b.byk_kodu = 'AT' AND k.aktif = 1 
    ORDER BY k.ad ASC
");

// Yeni veya Düzenle İstişare Kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['baslik'])) {
    $baslik = trim($_POST['baslik']);
    $sube = trim($_POST['sube_ismi']);
    
    // Kurul üyelerini checkboxlardan birleştir
    $kurul_array = $_POST['kurul_secimi'] ?? [];
    $kurul = !empty($kurul_array) ? implode(', ', $kurul_array) : trim($_POST['kurul_uyeleri'] ?? '');
    
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
    
    if (!empty($baslik) && !empty($sube)) {
        try {
            if ($edit_id) {
                $db->query("UPDATE istisare_sessions SET baslik = ?, sube_ismi = ?, kurul_uyeleri = ? WHERE id = ?", 
                    [$baslik, $sube, $kurul, $edit_id]);
                $msg = 'İstişare başarıyla güncellendi.';
            } else {
                $db->query("INSERT INTO istisare_sessions (baslik, sube_ismi, kurul_uyeleri) VALUES (?, ?, ?)", 
                    [$baslik, $sube, $kurul]);
                $msg = 'Yeni istişare başarıyla oluşturuldu.';
            }
            header("Location: istisareler.php?msg=" . urlencode($msg));
            exit;
        } catch (Exception $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    } else {
        $error = 'Lütfen başlık ve şube ismini doldurunuz.';
    }
}

if (isset($_GET['msg'])) $message = $_GET['msg'];
if (isset($_GET['err'])) $error = $_GET['err'];

$sessions = $db->fetchAll("
    SELECT s.*, 
    (SELECT COUNT(DISTINCT voter_id) FROM istisare_oylama WHERE session_id = s.id) as voter_count
    FROM istisare_sessions s
    ORDER BY s.eklenme_tarihi DESC
");

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="fas fa-vote-yea me-2 text-primary"></i>İstişareler Listesi</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#yeniIstisareModal">
                <i class="fas fa-plus me-2"></i>Yeni İstişare Başlat
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="50" class="text-center">#</th>
                                <th>İstişare Başlığı</th>
                                <th>Şube</th>
                                <th>İstişare Kurulu</th>
                                <th class="text-center">Katılım</th>
                                <th>Başlangıç Tarihi</th>
                                <th class="text-center">Durum</th>
                                <th width="150" class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($sessions as $s): ?>
                                <tr>
                                    <td class="text-center text-muted fw-bold"><?php echo $no++; ?></td>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($s['baslik']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($s['sube_ismi']); ?></span>
                                    </td>
                                    <td class="small">
                                        <?php echo htmlspecialchars($s['kurul_uyeleri'] ?: '-'); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-info text-dark">
                                            <i class="fas fa-user-check me-1"></i><?php echo $s['voter_count']; ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($s['eklenme_tarihi'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($s['durum'] === 'aktif'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success px-3">AKTİF</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary px-3">KAPALI</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary shadow-sm edit-session-btn" 
                                                    data-id="<?php echo $s['id']; ?>"
                                                    data-baslik="<?php echo htmlspecialchars($s['baslik']); ?>"
                                                    data-sube="<?php echo htmlspecialchars($s['sube_ismi']); ?>"
                                                    data-kurul="<?php echo htmlspecialchars($s['kurul_uyeleri'] ?? ''); ?>">
                                                <i class="fas fa-edit me-1"></i> Düzenle
                                            </button>
                                            <a href="istisare-formu.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-primary shadow-sm ms-1">
                                                <i class="fas fa-external-link-alt me-1"></i> Yönet
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($sessions)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted italic">
                                        Henüz bir istişare oturumu başlatılmamış.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Yeni İstişare Modal -->
<div class="modal fade" id="yeniIstisareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni İstişare Oturumu Başlat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">İstişare Başlığı</label>
                        <input type="text" name="baslik" class="form-control" placeholder="Örn: 2026 Başkanlık Seçimi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Şube</label>
                        <select name="sube_ismi" class="form-select" required>
                            <option value="">Lütfen şube seçiniz...</option>
                            <?php foreach ($subelerList as $sube): ?>
                                <option value="<?php echo htmlspecialchars($sube['sube_adi']); ?>">
                                    <?php echo htmlspecialchars($sube['sube_adi']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">İstişare Kurulu (AT BYK Üyeleri)</label>
                        <div class="card bg-light border-0">
                            <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($atUyeleri as $atU): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="kurul_secimi[]" 
                                               value="<?php echo htmlspecialchars($atU['ad'] . ' ' . $atU['soyad']); ?>" 
                                               id="at_create_<?php echo $atU['kullanici_id']; ?>">
                                        <label class="form-check-label small" for="at_create_<?php echo $atU['kullanici_id']; ?>">
                                            <?php echo htmlspecialchars($atU['ad'] . ' ' . $atU['soyad']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-text small">Listeden seçim yapabilir veya aşağıya manuel girebilirsiniz.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Manuel Kurul Girişi (Virgülle ayırın)</label>
                        <textarea name="kurul_uyeleri" class="form-control" rows="2" placeholder="Alternatif olarak isimleri buraya yazabilirsiniz"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary px-4">İstişareyi Başlat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- İstişare Düzenle Modal -->
<div class="modal fade" id="editSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST">
                <input type="hidden" name="edit_id" id="edit_session_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>İstişare Düzenle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">İstişare Başlığı</label>
                        <input type="text" name="baslik" id="edit_baslik" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Şube</label>
                        <select name="sube_ismi" id="edit_sube" class="form-select" required>
                            <option value="">Lütfen şube seçiniz...</option>
                            <?php foreach ($subelerList as $sube): ?>
                                <option value="<?php echo htmlspecialchars($sube['sube_adi']); ?>">
                                    <?php echo htmlspecialchars($sube['sube_adi']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">İstişare Kurulu (AT BYK Üyeleri)</label>
                        <div class="card bg-light border-0">
                            <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($atUyeleri as $atU): ?>
                                    <div class="form-check">
                                        <?php $name = $atU['ad'] . ' ' . $atU['soyad']; ?>
                                        <input class="form-check-input check-kurul" type="checkbox" name="kurul_secimi[]" 
                                               value="<?php echo htmlspecialchars($name); ?>" 
                                               id="at_edit_<?php echo $atU['kullanici_id']; ?>">
                                        <label class="form-check-label small" for="at_edit_<?php echo $atU['kullanici_id']; ?>">
                                            <?php echo htmlspecialchars($name); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Manuel Kurul Girişi / Mevcut Üyeler</label>
                        <textarea name="kurul_uyeleri" id="edit_kurul" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-session-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const baslik = this.getAttribute('data-baslik');
        const sube = this.getAttribute('data-sube');
        const kurul = this.getAttribute('data-kurul');
        
        document.getElementById('edit_session_id').value = id;
        document.getElementById('edit_baslik').value = baslik;
        document.getElementById('edit_sube').value = sube;
        document.getElementById('edit_kurul').value = kurul;
        
        // Checkboxes check logic
        const kurulArr = kurul.split(',').map(s => s.trim());
        document.querySelectorAll('.check-kurul').forEach(cb => {
            cb.checked = kurulArr.includes(cb.value);
        });
        
        const modal = new bootstrap.Modal(document.getElementById('editSessionModal'));
        modal.show();
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
