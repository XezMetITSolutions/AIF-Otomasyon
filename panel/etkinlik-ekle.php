<?php
/**
 * Başkan - Etkinlik Ekle
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';


Middleware::requireModulePermission('baskan_etkinlikler');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

$pageTitle = 'Yeni Etkinlik Ekle';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$csrfToken = Middleware::generateCSRF();
$message = null;
$messageType = 'success';

// Check if user is Super Admin
$isAdmin = $auth->isSuperAdmin();

// BYK listesi (Sadece Super Admin için)
$bykler = [];
if ($isAdmin) {
    try {
        $bykler = $db->fetchAll("SELECT id as byk_id, name as byk_adi, code as byk_kodu FROM byk_categories WHERE code IN ('AT', 'GT', 'KGT', 'KT') ORDER BY code");
    } catch (Exception $e) {
        $bykler = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk WHERE byk_kodu IN ('AT', 'GT', 'KGT', 'KT') ORDER BY byk_adi");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız.';
        $messageType = 'danger';
    } else {
        $baslik = trim($_POST['baslik'] ?? '');
        $aciklama = trim($_POST['aciklama'] ?? '');
        $baslangic = $_POST['baslangic_tarihi'] ?? '';
        $bitis = $_POST['bitis_tarihi'] ?? '';
        $konum = trim($_POST['konum'] ?? '');
        $renk = $_POST['renk_kodu'] ?? '#009872';

        if (!$baslik || !$baslangic || !$bitis) {
            $message = 'Başlık, başlangıç ve bitiş tarihleri zorunludur.';
            $messageType = 'danger';
        } elseif (strtotime($bitis) < strtotime($baslangic)) {
            $message = 'Bitiş tarihi başlangıç tarihinden önce olamaz.';
            $messageType = 'danger';
        } else {
            $byk_id = ($isAdmin && !empty($_POST['byk_id'])) ? $_POST['byk_id'] : $user['byk_id'];
            
            $db->query("
                INSERT INTO etkinlikler (byk_id, baslik, aciklama, baslangic_tarihi, bitis_tarihi, konum, renk_kodu, olusturan_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $byk_id,
                $baslik,
                $aciklama ?: null,
                $baslangic,
                $bitis,
                $konum ?: null,
                $renk,
                $user['id']
            ]);
            $message = 'Etkinlik başarıyla oluşturuldu.';
            header('Location: /panel/etkinlikler.php?success=1');
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="row justify-content-center">
             <div class="col-lg-8">
                 <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Yeni Etkinlik Oluştur</h5>
                            <a href="/panel/baskan_etkinlikler.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-2"></i>Listeye Dön
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                         <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                         <?php endif; ?>

                        <form method="post" class="row g-3">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Birim (BYK) <span class="text-danger">*</span></label>
                                <?php if ($isAdmin): ?>
                                    <select name="byk_id" class="form-select" required>
                                        <?php foreach ($bykler as $byk): ?>
                                            <option value="<?php echo $byk['byk_id']; ?>" <?php echo $byk['byk_id'] == $user['byk_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($byk['byk_adi']); ?> (<?php echo $byk['byk_kodu']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" class="form-control" value="Anateşkilat (AT)" readonly disabled>
                                    <input type="hidden" name="byk_id" value="<?php echo $user['byk_id']; ?>">
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Başlık <span class="text-danger">*</span></label>
                                <input type="text" name="baslik" class="form-control" required placeholder="Örn. Haftalık İstişare Toplantısı">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Açıklama</label>
                                <textarea name="aciklama" class="form-control" rows="3" placeholder="Etkinlik hakkında detaylı bilgi..."></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Konum</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" name="konum" class="form-control" placeholder="Adres veya Online Toplantı Linki">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Başlangıç Tarihi <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="baslangic_tarihi" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Bitiş Tarihi <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="bitis_tarihi" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Etkinlik Rengi</label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="color" name="renk_kodu" class="form-control form-control-color" value="#009872" title="Etkinlik rengini seçin">
                                    <span class="text-muted small">Takvimde görünecek rengi seçiniz.</span>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                    <i class="fas fa-check me-2"></i>Etkinliği Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                 </div>
             </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
