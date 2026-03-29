<?php
/**
 * Ana Yönetici - Şube Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Şube Listesi';

$subeler = [];
$error = null;

try {
    $subeler = $db->fetchAll("SELECT * FROM subeler ORDER BY sube_adi ASC");
} catch (Exception $e) {
    $error = "Şube listesi yüklenirken bir hata oluştu. Lütfen veritabanı tablosunun oluşturulduğundan emin olun.";
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-map-marked-alt me-2 text-primary"></i>Şube Listesi
            </h1>
            <div class="btn-group">
                <button class="btn btn-outline-primary shadow-sm" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Yazdır
                </button>
                <button class="btn btn-primary shadow-sm ms-2" data-bs-toggle="modal" data-bs-target="#subeEkleModal">
                    <i class="fas fa-plus me-2"></i>Yeni Şube Ekle
                </button>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger shadow-sm border-left-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <div class="mt-2 text-sm">
                    <code>database/create_subeler_table.php</code> dosyasını çalıştırarak tabloyu oluşturabilirsiniz.
                </div>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-0">
                <div class="row align-items-center">
                    <div class="col">
                        <h6 class="m-0 font-weight-bold text-primary">Kayıtlı Şubeler (<?php echo count($subeler); ?>)</h6>
                    </div>
                    <div class="col-auto">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" id="subeSearch" class="form-control" placeholder="Şube ara...">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="subelerTable">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4" style="width: 50px;">#</th>
                                <th>Şube Adı</th>
                                <th>Adres</th>
                                <th>Şehir</th>
                                <th>Posta Kodu</th>
                                <th class="text-center">Durum</th>
                                <th class="text-end pe-4">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subeler)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-map-marker-alt fa-3x mb-3 d-block opacity-25"></i>
                                        Henüz şube kaydı bulunmuyor.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subeler as $index => $sube): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?php echo $index + 1; ?></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($sube['sube_adi']); ?></td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 300px;" title="<?php echo htmlspecialchars($sube['adres']); ?>">
                                                <i class="fas fa-location-dot me-1 text-muted small"></i>
                                                <?php echo htmlspecialchars($sube['adres']); ?>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($sube['sehir'] ?? '-'); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($sube['posta_kodu'] ?? '-'); ?></code></td>
                                        <td class="text-center">
                                            <?php if ($sube['aktif']): ?>
                                                <span class="badge bg-success-soft text-success border border-success px-2 py-1">
                                                    <i class="fas fa-check-circle me-1"></i>Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-soft text-danger border border-danger px-2 py-1">
                                                    <i class="fas fa-times-circle me-1"></i>Pasif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($sube['adres']); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Haritada Gör">
                                                    <i class="fas fa-map"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-primary edit-sube" data-id="<?php echo $sube['id']; ?>" title="Düzenle">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-sube" data-id="<?php echo $sube['id']; ?>" title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Sube Ekle Modal -->
<div class="modal fade" id="subeEkleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <form action="api/subeler_kaydet.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Yeni Şube Ekle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Şube Adı</label>
                        <input type="text" name="sube_adi" class="form-control form-control-lg" placeholder="AIF ..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Tam Adres</label>
                        <textarea name="adres" class="form-control shadow-none" rows="3" placeholder="Sokak, No, Posta Kodu, Şehir" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase">Şehir</label>
                                <input type="text" name="sehir" class="form-control" placeholder="Örn: Feldkirch">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase">Posta Kodu</label>
                                <input type="text" name="posta_kodu" class="form-control" placeholder="6800">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(40, 167, 69, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .border-left-danger { border-left: 5px solid #e74a3b !important; }
    #subelerTable tr:hover { background-color: rgba(78, 115, 223, 0.03); }
    .modal-content { border-radius: 12px; }
    .modal-header { border-radius: 12px 12px 0 0; }
</style>

<script>
document.getElementById('subeSearch')?.addEventListener('keyup', function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll('#subelerTable tbody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(value) ? '' : 'none';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
