<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireLoggedIn();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Demirbaş Talep ve Rezervasyon';

$success = '';
$error = '';

// Form Gönderimi (Rezervasyon/Talep)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $demirbas_id = $_POST['demirbas_id'] ?? null;
    $baslik = $_POST['baslik'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';
    $baslangic = $_POST['baslangic'] ?? null;
    $bitis = $_POST['bitis'] ?? null;

    if (empty($baslik)) {
        $error = 'Lütfen talep başlığını giriniz.';
    } else {
        try {
            // Demirbaş seçildiyse rezervasyon, değilse genel talep
            $sql = "INSERT INTO demirbas_talepleri (kullanici_id, demirbas_id, baslik, aciklama, baslangic_tarihi, bitis_tarihi) VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$user['id'], $demirbas_id ?: null, $baslik, $aciklama, $baslangic ?: null, $bitis ?: null];
            
            $db->query($sql, $params);
            $success = 'Talebiniz başarıyla oluşturuldu.';
        } catch (Exception $e) {
            $error = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

// Mevcut Demirbaşları Getir
$demirbaslar = $db->fetchAll("SELECT * FROM demirbaslar WHERE durum = 'kullanimda' OR durum = 'depoda' ORDER BY demirbas_adi ASC");

// Geçmiş Talepler
$talepler = $db->fetchAll(
    "SELECT dt.*, d.demirbas_adi 
     FROM demirbas_talepleri dt 
     LEFT JOIN demirbaslar d ON dt.demirbas_id = d.demirbas_id 
     WHERE dt.kullanici_id = ? 
     ORDER BY dt.created_at DESC",
    [$user['id']]
);

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-box me-2"></i>Demirbaş Talep ve Rezervasyon
            </h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#talepModal">
                <i class="fas fa-plus me-2"></i>Yeni Talep Oluştur
            </button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Demirbaş Listesi -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Mevcut Demirbaşlar</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Demirbaş Adı</th>
                                        <th>Kategori</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($demirbaslar as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['demirbas_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($item['kategori']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $item['durum'] === 'kullanimda' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($item['durum']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="openReserveModal(<?php echo $item['demirbas_id']; ?>, '<?php echo htmlspecialchars($item['demirbas_adi'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-calendar-check me-1"></i>Rezerve Et
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Geçmiş Talepler -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Taleplerim</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Başlık / Demirbaş</th>
                                        <th>Rezervasyon Tarihleri</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($talepler)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Henüz bir talebiniz bulunmuyor.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($talepler as $talep): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y H:i', strtotime($talep['created_at'])); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($talep['baslik']); ?></strong>
                                                    <?php if ($talep['demirbas_adi']): ?>
                                                        <br><small class="text-info"><i class="fas fa-box me-1"></i><?php echo htmlspecialchars($talep['demirbas_adi']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($talep['baslangic_tarihi']): ?>
                                                        <?php echo date('d.m.Y', strtotime($talep['baslangic_tarihi'])); ?> - 
                                                        <?php echo date('d.m.Y', strtotime($talep['bitis_tarihi'])); ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $badgeClass = match($talep['durum']) {
                                                        'onaylandi' => 'success',
                                                        'reddedildi' => 'danger',
                                                        default => 'warning text-dark'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo ucfirst($talep['durum']); ?></span>
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
        </div>
    </div>
</main>

<!-- Talep/Rezervasyon Modal -->
<div class="modal fade" id="talepModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Yeni Talep Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="demirbas_id" id="modal_demirbas_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Talep Başlığı</label>
                        <input type="text" class="form-control" name="baslik" id="modal_baslik" required placeholder="Örn: Laptop İhtiyacı">
                    </div>
                    
                    <div class="mb-3" id="item_display_group" style="display:none;">
                        <label class="form-label">Seçilen Demirbaş</label>
                        <input type="text" class="form-control" id="modal_item_name" readonly>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" name="baslangic">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" name="bitis">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="aciklama" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openReserveModal(id, name) {
    document.getElementById('modal_demirbas_id').value = id;
    document.getElementById('modal_item_name').value = name;
    document.getElementById('item_display_group').style.display = 'block';
    document.getElementById('modal_baslik').value = name + ' Rezervasyonu';
    document.getElementById('modalTitle').innerText = 'Demirbaş Rezerve Et';
    
    var modal = new bootstrap.Modal(document.getElementById('talepModal'));
    modal.show();
}

// Reset modal when closed
document.getElementById('talepModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modal_demirbas_id').value = '';
    document.getElementById('item_display_group').style.display = 'none';
    document.getElementById('modal_baslik').value = '';
    document.getElementById('modalTitle').innerText = 'Yeni Talep Oluştur';
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
