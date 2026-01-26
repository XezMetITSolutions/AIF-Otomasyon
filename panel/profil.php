<?php
/**
 * Profilim
 * Modern Minimal Tasarım
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireUye();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

$pageTitle = 'Profilim';
$csrfTokenName = $appConfig['security']['csrf_token_name'] ?? 'csrf_token';
$csrfToken = Middleware::generateCSRF();
$errors = [];
$messages = [];

function fetchUserDetails(Database $db, $userId) {
    return $db->fetch("
        SELECT k.*, 
               b.byk_adi, 
               ab.alt_birim_adi,
               r.rol_adi
        FROM kullanicilar k
        LEFT JOIN byk b ON k.byk_id = b.byk_id
        LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
        INNER JOIN roller r ON k.rol_id = r.rol_id
        WHERE k.kullanici_id = ?
    ", [$userId]);
}

$profil = fetchUserDetails($db, $user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Oturum doğrulaması başarısız oldu. Lütfen sayfayı yenileyin.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'profil_guncelle') {
            $telefon = trim($_POST['telefon'] ?? '');
            $db->query("
                UPDATE kullanicilar
                SET telefon = ?, guncelleme_tarihi = NOW()
                WHERE kullanici_id = ?
            ", [$telefon ?: null, $user['id']]);
            $messages[] = 'Profil bilgileriniz güncellendi.';
            $profil = fetchUserDetails($db, $user['id']);
        } elseif ($action === 'sifre_degistir') {
            $mevcutSifre = $_POST['mevcut_sifre'] ?? '';
            $yeniSifre = $_POST['yeni_sifre'] ?? '';
            $yeniSifreTekrar = $_POST['yeni_sifre_tekrar'] ?? '';
            $minLength = $appConfig['security']['password_min_length'] ?? 8;
            
            if (strlen($yeniSifre) < $minLength) {
                $errors[] = "Yeni şifre en az {$minLength} karakter olmalıdır.";
            }
            if ($yeniSifre !== $yeniSifreTekrar) {
                $errors[] = 'Yeni şifre ile tekrarı eşleşmiyor.';
            }
            if (empty($errors)) {
                if ($auth->changePassword($user['id'], $mevcutSifre, $yeniSifre)) {
                    $messages[] = 'Şifreniz başarıyla güncellendi.';
                } else {
                    $errors[] = 'Mevcut şifreniz hatalı.';
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
/* Modern Minimal Header */
.modern-header {
    background: #fff;
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0,0,0,0.03);
    position: relative;
    overflow: hidden;
}

.modern-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
}

.header-breadcrumb {
    font-size: 0.9rem;
    color: #0d6efd;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    display: block;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
}
</style>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        
        <!-- Modern Minimal Header -->
        <div class="modern-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="header-breadcrumb">
                        <i class="fas fa-layer-group me-1"></i><?php echo htmlspecialchars($profil['byk_adi'] ?? 'Yönetim'); ?>
                    </span>
                    <h1 class="page-title">Profilim</h1>
                    <p class="text-muted mb-0">
                        <i class="fas fa-user-circle me-1"></i>
                        Kişisel bilgilerinizi buradan yönetebilirsiniz.
                    </p>
                </div>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul class="mb-0 d-inline-block align-top ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php foreach ($messages as $message): ?>
                    <div><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row g-4 d-flex align-items-stretch">
            <div class="col-lg-7">
                <div class="card h-100 shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white border-bottom-0 py-3 d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <h5 class="mb-0 fw-bold text-dark">Kişisel Bilgiler</h5>
                    </div>
                    <div class="card-body pt-0">
                        <form method="post">
                            <input type="hidden" name="action" value="profil_guncelle">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">Ad Soyad</label>
                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($profil['ad'] . ' ' . $profil['soyad']); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">E-posta</label>
                                    <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($profil['email']); ?>" disabled>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label text-muted small fw-bold">Telefon</label>
                                    <input type="text" name="telefon" class="form-control" value="<?php echo htmlspecialchars($profil['telefon'] ?? ''); ?>" placeholder="+43 xxx xxx xx xx">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">BYK</label>
                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($profil['byk_adi'] ?? '-'); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">Alt Birim</label>
                                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($profil['alt_birim_adi'] ?? '-'); ?>" disabled>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-2"></i>Bilgileri Güncelle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-5">
                <div class="card h-100 shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white border-bottom-0 py-3 d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3">
                            <i class="fas fa-key"></i>
                        </div>
                        <h5 class="mb-0 fw-bold text-dark">Güvenlik</h5>
                    </div>
                    <div class="card-body pt-0">
                        <form method="post">
                            <input type="hidden" name="action" value="sifre_degistir">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Mevcut Şifre</label>
                                <input type="password" name="mevcut_sifre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Yeni Şifre</label>
                                <input type="password" name="yeni_sifre" class="form-control" required>
                                <div class="form-text">En az <?php echo htmlspecialchars($appConfig['security']['password_min_length'] ?? 8); ?> karakter.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">Yeni Şifre (Tekrar)</label>
                                <input type="password" name="yeni_sifre_tekrar" class="form-control" required>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-outline-dark w-100">
                                    <i class="fas fa-lock me-2"></i>Şifreyi Değiştir
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>
