<?php
/**
 * Üye - Profilim
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

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-user-circle me-2"></i>Profilim
                </h1>
                <small class="text-muted">Kişisel bilgilerinizi güncelleyin ve şifrenizi değiştirin.</small>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success">
                <?php foreach ($messages as $message): ?>
                    <div><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>Kişisel Bilgiler</strong>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="profil_guncelle">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($profil['ad'] . ' ' . $profil['soyad']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-posta</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($profil['email']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="telefon" class="form-control" value="<?php echo htmlspecialchars($profil['telefon'] ?? ''); ?>" placeholder="+43 xxx xxx xx xx">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">BYK</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($profil['byk_adi'] ?? '-'); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alt Birim</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($profil['alt_birim_adi'] ?? '-'); ?>" disabled>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i>Bilgileri Güncelle
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>Şifre Değiştir</strong>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="sifre_degistir">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Mevcut Şifre</label>
                                <input type="password" name="mevcut_sifre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre</label>
                                <input type="password" name="yeni_sifre" class="form-control" required>
                                <small class="text-muted">En az <?php echo htmlspecialchars($appConfig['security']['password_min_length'] ?? 8); ?> karakter olmalıdır.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre (Tekrar)</label>
                                <input type="password" name="yeni_sifre_tekrar" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-key me-1"></i>Şifreyi Güncelle
                            </button>
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


