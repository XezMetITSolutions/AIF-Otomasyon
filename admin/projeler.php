<?php
/**
 * Ana Yönetici - Proje Takibi
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

// Projeleri Getir
$sql = "SELECT p.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as sorumlu
        FROM projeler p
        INNER JOIN byk b ON p.byk_id = b.byk_id
        LEFT JOIN kullanicilar u ON p.sorumlu_id = u.kullanici_id
        WHERE 1=1";

$params = [];

// Eğer süper admin değilse ve Yönetici yetkisi yoksa -> Sadece dahil olduğu projeler
// Not: Yönetici yetkisi ($canManage) varsa tümünü görür (veya BYK filtresi uygulanabilir ama şimdilik tümü)
if (!$isSuperAdmin && !$canManage) {
    // Sadece sorumlu olduğu, ekibinde olduğu projeler
    $sql .= " AND (
        p.sorumlu_id = :uid_sorumlu 
        OR EXISTS (
            SELECT 1 FROM proje_ekipleri pe 
            JOIN proje_ekip_uyeleri peu ON pe.id = peu.ekip_id 
            WHERE pe.proje_id = p.proje_id AND peu.kullanici_id = :uid_ekip
        )
    )";
    $params['uid_sorumlu'] = $user['id'];
    $params['uid_ekip'] = $user['id'];
}

$sql .= " ORDER BY p.olusturma_tarihi DESC LIMIT 50";

$projeler = $db->fetchAll($sql, $params);


// BYK Listesi (Form için)
$bykList = $db->fetchAll("SELECT * FROM byk ORDER BY byk_adi ASC");

// Kullanıcı Listesi (Sorumlu seçimi için) - Sadece aktif kullanıcılar
$usersList = $db->fetchAll("SELECT kullanici_id, ad, soyad FROM kullanicilar WHERE aktif = 1 ORDER BY ad ASC");

// Yeni Proje Ekleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_project') {
    try {
        if (!Middleware::verifyCSRF()) {
            throw new Exception('Güvenlik doğrulaması başarısız via CSRF.');
        }

        $baslik = trim($_POST['baslik'] ?? '');
        $byk_id = $_POST['byk_id'] ?? null;
        $sorumlu_id = $_POST['sorumlu_id'] ?: null; // Optional
        $aciklama = trim($_POST['aciklama'] ?? '');
        $baslangic = $_POST['baslangic_tarihi'] ?: null;
        $bitis = $_POST['bitis_tarihi'] ?: null;
        
        if (empty($baslik)) throw new Exception("Proje başlığı zorunludur.");
        if (empty($byk_id)) throw new Exception("Birim/BYK seçimi zorunludur.");

        // olusturan_id eklenmeli (Giriş yapan kullanıcı)
        $olusturan_id = $user['id'] ?? $user['kullanici_id'] ?? 0;

        $sql = "INSERT INTO projeler (baslik, aciklama, byk_id, sorumlu_id, baslangic_tarihi, bitis_tarihi, durum, olusturan_id, olusturma_tarihi) VALUES (?, ?, ?, ?, ?, ?, 'beklemede', ?, NOW())";
        $db->query($sql, [$baslik, $aciklama, $byk_id, $sorumlu_id, $baslangic, $bitis, $olusturan_id]);
        
        header("Location: projeler.php?success=created");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-project-diagram me-2"></i>Proje Takibi
                </h1>
                <?php if ($canManage): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                    <i class="fas fa-plus me-2"></i>Yeni Proje Ekle
                </button>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] === 'created'): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Proje başarıyla oluşturuldu.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    Toplam: <strong><?php echo count($projeler); ?></strong> proje
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Proje Adı</th>
                                    <th>BYK / Birim</th>
                                    <th>Sorumlu</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($projeler)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-folder-open fa-3x mb-3"></i><br>
                                            Henüz proje eklenmemiş.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($projeler as $proje): ?>
                                        <tr>
                                            <td class="fw-medium">
                                                <a href="/admin/proje-detay.php?id=<?php echo $proje['proje_id']; ?>" class="text-decoration-none fw-bold text-dark">
                                                    <?php echo htmlspecialchars($proje['baslik']); ?>
                                                </a>
                                            </td>
                                            <td><span class="badge bg-secondary text-light"><?php echo htmlspecialchars($proje['byk_adi']); ?></span></td>
                                            <td>
                                                <?php if($proje['sorumlu']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle-sm bg-primary text-white me-2">
                                                            <?php echo strtoupper(substr($proje['sorumlu'], 0, 1)); ?>
                                                        </div>
                                                        <?php echo htmlspecialchars($proje['sorumlu']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = match($proje['durum']) {
                                                    'tamamlandi' => 'success',
                                                    'aktif' => 'primary',
                                                    'iptal' => 'danger',
                                                    default => 'warning text-dark'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($proje['durum']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo $proje['baslangic_tarihi'] ? date('d.m.Y', strtotime($proje['baslangic_tarihi'])) : ''; ?>
                                                    <?php echo ($proje['baslangic_tarihi'] && $proje['bitis_tarihi']) ? ' - ' : ''; ?>
                                                    <?php echo $proje['bitis_tarihi'] ? date('d.m.Y', strtotime($proje['bitis_tarihi'])) : ''; ?>
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <a href="/admin/proje-detay.php?id=<?php echo $proje['proje_id']; ?>" class="btn btn-sm btn-outline-info me-1" title="Detaylar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/admin/proje-duzenle.php?id=<?php echo $proje['proje_id']; ?>" class="btn btn-sm btn-light border" title="Düzenle">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </a>
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

<!-- Yeni Proje Modal -->
<div class="modal fade" id="newProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Proje Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo Middleware::generateCSRF(); ?>">
                <input type="hidden" name="action" value="add_project">
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Proje Başlığı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="baslik" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Birim / BYK <span class="text-danger">*</span></label>
                        <select name="byk_id" class="form-select" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach($bykList as $byk): ?>
                                <option value="<?php echo $byk['byk_id']; ?>"><?php echo htmlspecialchars($byk['byk_adi']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Proje Sorumlusu</label>
                        <select name="sorumlu_id" class="form-select">
                            <option value="">Seçiniz...</option>
                            <?php foreach($usersList as $usr): ?>
                                <option value="<?php echo $usr['kullanici_id']; ?>">
                                    <?php echo htmlspecialchars($usr['ad'] . ' ' . $usr['soyad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="baslangic_tarihi">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="bitis_tarihi">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="aciklama" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary">Projeyi Oluştur</button>
            </div>
        </form>
    </div>
</div>

<?php
include __DIR__ . '/../includes/footer.php';
?>

