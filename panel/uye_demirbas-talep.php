<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireAuth();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Demirbaş Talep Formu';

$success = '';
$error = '';

// Form Gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $demirbas_id = $_POST['demirbas_id'] ?? null;
    $baslangic = $_POST['baslangic'] ?? null;
    $bitis = $_POST['bitis'] ?? null;
    $aciklama = $_POST['aciklama'] ?? '';
    
    // Demirbaş adını al (Başlık için)
    $demirbas = $db->fetch("SELECT ad FROM demirbaslar WHERE id = ?", [$demirbas_id]);
    $baslik = $demirbas ? $demirbas['ad'] . ' Talebi' : 'Genel Talep';

    if (empty($baslangic) || empty($bitis)) {
        $error = 'Lütfen başlangıç ve bitiş tarihlerini giriniz.';
    } else {
        try {
            $db->query(
                "INSERT INTO demirbas_talepleri (kullanici_id, demirbas_id, baslik, aciklama, baslangic_tarihi, bitis_tarihi) VALUES (?, ?, ?, ?, ?, ?)",
                [$user['id'], $demirbas_id, $baslik, $aciklama, $baslangic, $bitis]
            );
            $success = 'Talebiniz başarıyla oluşturuldu.';
        } catch (Exception $e) {
            $error = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

// Müsait Demirbaşları Listele
$demirbaslar = $db->fetchAll("
    SELECT d.*, CONCAT(u.ad, ' ', u.soyad) as sorumlu_adi 
    FROM demirbaslar d 
    LEFT JOIN kullanicilar u ON d.sorumlu_kisi_id = u.kullanici_id 
    WHERE d.durum = 'musait'
    ORDER BY d.kategori, d.ad
");

// Geçmiş Talepler
$talepler = $db->fetchAll(
    "SELECT t.*, d.ad as demirbas_adi 
     FROM demirbas_talepleri t 
     LEFT JOIN demirbaslar d ON t.demirbas_id = d.id
     WHERE t.kullanici_id = ? 
     ORDER BY t.created_at DESC",
    [$user['id']]
);

include __DIR__ . '/../includes/header.php';
?>

<!-- Modern Design Assets -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #009872;
        --primary-light: rgba(0, 152, 114, 0.1);
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --card-bg: rgba(255, 255, 255, 0.9);
        --glass-border: 1px solid rgba(255, 255, 255, 0.5);
    }

    body {
        font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at 0% 0%, rgba(0, 152, 114, 0.08) 0%, transparent 50%),
                    radial-gradient(circle at 100% 100%, rgba(0, 152, 114, 0.05) 0%, transparent 50%),
                    #f8fafc;
        color: var(--text-dark);
    }

    /* Glass Cards */
    .card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border: var(--glass-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border-radius: 1rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }

    .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1.25rem 1.5rem;
        font-size: 1rem;
        color: var(--text-dark);
    }

    /* CSS Overrides for Mobile Layout Fix */
    .dashboard-layout {
        display: block;
    }

    .sidebar-wrapper {
        display: none;
    }

    .content-wrapper {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        padding: 1rem !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .main-content {
        width: 100%;
    }

    /* Desktop View */
    @media (min-width: 992px) {
        .dashboard-layout {
            display: flex;
            flex-direction: row;
        }

        .sidebar-wrapper {
            display: block;
            width: 250px;
            flex-shrink: 0;
            z-index: 1000;
        }
        
        .main-content {
            flex-grow: 1;
            width: auto;
        }

        .content-wrapper {
            padding: 1.5rem 2rem !important;
            max-width: 1400px !important;
            margin: 0 auto !important;
        }
    }
</style>

<div class="dashboard-layout">
    <!-- Sidebar Wrapper -->
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-box me-2"></i>Demirbaş Talep Formu
            </h1>
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

        <!-- Demirbaş Listesi -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3">Müsait Demirbaşlar</h5>
            </div>
            <?php if (empty($demirbaslar)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Şu anda talep edilebilecek müsait demirbaş bulunmamaktadır.</div>
                </div>
            <?php else: ?>
                <?php foreach ($demirbaslar as $item): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm">
                            <?php if ($item['fotograf_yolu']): ?>
                                <img src="/<?php echo htmlspecialchars($item['fotograf_yolu']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['ad']); ?>" style="height: 180px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 180px;">
                                    <i class="fas fa-box fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['ad']); ?></h5>
                                <p class="card-text small text-muted mb-1">
                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item['kategori']); ?>
                                </p>
                                <p class="card-text small text-muted mb-1">
                                    <i class="fas fa-user me-1"></i>Sorumlu: <?php echo htmlspecialchars($item['sorumlu_adi'] ?? 'Belirtilmemiş'); ?>
                                </p>
                                <p class="card-text small text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($item['konum']); ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <button class="btn btn-primary w-100" onclick='requestItem(<?php echo json_encode($item); ?>)'>
                                    <i class="fas fa-hand-pointer me-2"></i>Talep Et
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Geçmiş Talepler -->
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
                                <th>Demirbaş</th>
                                <th>Talep Tarihleri</th>
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
                                        <td><?php echo date('d.m.Y', strtotime($talep['created_at'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($talep['demirbas_adi'] ?? $talep['baslik']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($talep['baslangic_tarihi']): ?>
                                                <?php echo date('d.m.Y H:i', strtotime($talep['baslangic_tarihi'])); ?> - <br>
                                                <?php echo date('d.m.Y H:i', strtotime($talep['bitis_tarihi'])); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($talep['durum'] === 'bekliyor'): ?>
                                                <span class="badge bg-warning text-dark">Bekliyor</span>
                                            <?php elseif ($talep['durum'] === 'onaylandi'): ?>
                                                <span class="badge bg-success">Onaylandı</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Reddedildi</span>
                                            <?php endif; ?>
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
    </main>
</div>

<!-- Talep Modal -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="demirbas_id" id="reqDemirbasId">
                <div class="modal-header">
                    <h5 class="modal-title">Demirbaş Talep Et: <span id="reqDemirbasAd"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="datetime-local" class="form-control" name="baslangic" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="datetime-local" class="form-control" name="bitis" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama / Not</label>
                        <textarea class="form-control" name="aciklama" rows="3" placeholder="Neden ihtiyacınız var?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Talebi Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function requestItem(item) {
    var modal = new bootstrap.Modal(document.getElementById('requestModal'));
    document.getElementById('reqDemirbasId').value = item.id;
    document.getElementById('reqDemirbasAd').innerText = item.ad;
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
